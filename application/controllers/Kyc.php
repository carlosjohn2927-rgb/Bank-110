<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kyc — customer identity document upload.
 *
 * Customers upload photos of identity documents (passport, driver's licence,
 * national ID, proof of address, selfie). Each document is reviewed by an
 * admin; once all submitted documents are approved, the customer's KYC
 * status flips to "verified". Files are stored outside the web root and
 * served only through the authenticated download() method.
 */
class Kyc extends MY_Controller {

    const DOC_TYPES = array(
        'passport'         => 'Passport',
        'drivers_license'  => "Driver's licence",
        'national_id'      => 'National ID card',
        'proof_of_address' => 'Proof of address',
        'selfie'           => 'Selfie / portrait',
        'other'            => 'Other document',
    );

    public function __construct()
    {
        parent::__construct();
        $this->require_customer();
    }

    public function index()
    {
        $profile = $this->Bank_model->profile($this->user['id']);
        $this->render('customer/kyc', array(
            'title'    => 'Identity verification',
            'profile'  => $profile,
            'documents'=> $this->Bank_model->kyc_documents($this->user['id']),
            'doc_types'=> self::DOC_TYPES,
        ));
    }

    public function upload()
    {
        if ($this->input->method() !== 'post') redirect('kyc');
        $this->form_validation->set_rules('doc_type', 'Document type', 'required|in_list[passport,drivers_license,national_id,proof_of_address,selfie,other]');
        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors(' ',' '));
            redirect('kyc');
        }
        if (empty($_FILES['document']['name'])) {
            $this->session->set_flashdata('error', 'Please choose a file to upload.');
            redirect('kyc');
        }

        $upload_path = $this->resolve_upload_path();
        $config = array(
            'upload_path'   => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|webp|pdf',
            'max_size'      => (int) $this->config->item('max_upload_kb') ?: 5120,
            'encrypt_name'  => TRUE,
            'remove_spaces' => TRUE,
        );
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('document')) {
            $this->session->set_flashdata('error', strip_tags($this->upload->display_errors(' ',' ')));
            redirect('kyc');
        }
        $file = $this->upload->data();
        $relative = 'assets/uploads/kyc/'.$file['file_name'];
        $this->Bank_model->add_kyc_document($this->user['id'], array(
            'doc_type'      => $this->input->post('doc_type', TRUE),
            'file_path'     => $relative,
            'original_name' => $file['orig_name'],
            'mime_type'     => $file['file_type'],
            'file_size'     => $file['file_size'] * 1024,
        ));
        $this->Bank_model->audit('kyc_uploaded', 'KYC document uploaded: '.$this->input->post('doc_type', TRUE), $this->user['id']);
        $this->session->set_flashdata('success', 'Document uploaded and submitted for review.');
        redirect('kyc');
    }

    public function delete($id = NULL)
    {
        if ($this->input->method() !== 'post') redirect('kyc');
        $doc = $this->Bank_model->delete_kyc_document((int)$id, $this->user['id']);
        if ($doc) {
            $full = FCPATH.ltrim($doc['file_path'], '/\\');
            if (is_file($full)) @unlink($full);
            $this->session->set_flashdata('success', 'Document removed.');
        } else {
            $this->session->set_flashdata('error', 'Only pending documents can be deleted.');
        }
        redirect('kyc');
    }

    /**
     * Serve a KYC document — only to its owner or an admin. Files live in a
     * .htaccess-protected directory so this is the only way to read them.
     */
    public function download($id = NULL)
    {
        $doc = $this->Bank_model->kyc_document((int)$id);
        if (!$doc) show_404();
        $isOwner = (int)$doc['user_id'] === (int)$this->user['id'];
        $isAdmin = $this->user['role'] === 'admin';
        if (!$isOwner && !$isAdmin) show_403();
        $full = FCPATH.ltrim($doc['file_path'], '/\\');
        if (!is_file($full)) show_404();
        $mime = $doc['mime_type'] ?: 'application/octet-stream';
        header('Content-Type: '.$mime);
        header('Content-Disposition: inline; filename="'.($doc['original_name'] ?: 'document').'"');
        header('Content-Length: '.filesize($full));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($full);
        exit;
    }

    private function resolve_upload_path()
    {
        $relative = trim((string)$this->config->item('upload_path'), '/\\');
        $base = preg_match('#^([A-Za-z]:)?[\\/]#', $relative) ? $relative : FCPATH.$relative;
        $path = rtrim($base, '/\\').DIRECTORY_SEPARATOR.'kyc';
        if (!is_dir($path)) @mkdir($path, 0775, TRUE);
        return $path;
    }
}
