<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mobile Check Deposit — customers photograph the front and back of a
 * paper check, submit it for review, and track approval/rejection.
 */
class Deposits extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_customer();
    }

    public function index()
    {
        $deposits = $this->Bank_model->check_deposits_for_user($this->user['id'], 50);
        $total_pending = 0;
        $total_approved = 0;
        foreach ($deposits as $d) {
            if ($d['status'] === 'pending') $total_pending += (float)$d['amount'];
            if ($d['status'] === 'approved') $total_approved += (float)$d['amount'];
        }
        $this->render('customer/deposits', array(
            'title'          => 'Deposit a check',
            'accounts'       => $this->Bank_model->accounts($this->user['id']),
            'deposits'       => $deposits,
            'total_pending'  => $total_pending,
            'total_approved' => $total_approved,
        ));
    }

    public function create()
    {
        if ($this->input->method() !== 'post') redirect('deposits');
        $this->form_validation->set_rules('account_id', 'Deposit to account', 'required|integer');
        $this->form_validation->set_rules('amount', 'Check amount', 'required|numeric|greater_than[0]|less_than[25000]');
        $this->form_validation->set_rules('check_number', 'Check number', 'trim|max_length[40]');

        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors(' ', ' '));
            redirect('deposits');
        }

        if (empty($_FILES['front']['name']) || empty($_FILES['back']['name'])) {
            $this->session->set_flashdata('error', 'Please upload photos of both the front and back of your check.');
            redirect('deposits');
        }

        $upload_path = $this->resolve_upload_path();
        $config = array(
            'upload_path'   => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size'      => (int)$this->config->item('max_upload_kb') ?: 5120,
            'encrypt_name'  => TRUE,
            'remove_spaces' => TRUE,
        );
        $this->load->library('upload', $config);

        $paths = array();
        foreach (array('front', 'back') as $side) {
            if (!$this->upload->do_upload($side)) {
                $this->cleanup_partial($paths);
                $this->session->set_flashdata('error', strip_tags($this->upload->display_errors(' ', ' ')));
                redirect('deposits');
            }
            $file = $this->upload->data();
            $paths[$side] = 'assets/uploads/checks/'.$file['file_name'];
        }

        list($ok, $message) = $this->Bank_model->create_check_deposit(
            $this->user['id'],
            (int)$this->input->post('account_id'),
            $this->input->post('amount'),
            $paths['front'],
            $paths['back'],
            $this->input->post('check_number', TRUE)
        );

        if (!$ok) {
            $this->cleanup_partial($paths);
            $this->session->set_flashdata('error', $message);
            redirect('deposits');
        }

        $this->Bank_model->audit('check_deposit_submitted', 'Check deposit '.$message.' submitted', $this->user['id']);
        $this->session->set_flashdata('success', 'Check deposit submitted. Reference: '.$message.'. We will review it shortly.');
        redirect('deposits');
    }

    public function view($id = NULL)
    {
        $deposit = $this->Bank_model->check_deposit((int)$id, $this->user['id']);
        if (!$deposit) show_404();
        $this->render('customer/deposit_detail', array(
            'title'   => 'Deposit '.$deposit['reference'],
            'deposit' => $deposit,
        ));
    }

    private function resolve_upload_path()
    {
        $relative = trim((string)$this->config->item('upload_path'), '/\\');
        $base = preg_match('#^([A-Za-z]:)?[\\/]#', $relative) ? $relative : FCPATH.$relative;
        $path = rtrim($base, '/\\').DIRECTORY_SEPARATOR.'checks';
        if (!is_dir($path)) @mkdir($path, 0775, TRUE);
        return $path;
    }

    private function cleanup_partial(array $paths)
    {
        foreach ($paths as $p) {
            $full = FCPATH.ltrim($p, '/\\');
            if (is_file($full)) @unlink($full);
        }
    }
}
