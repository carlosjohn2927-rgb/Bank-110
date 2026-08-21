<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Transfers extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){redirect('transfer');}
 public function create(){
  if($this->input->method()==='post'){
   $this->form_validation->set_rules('from_account_id','From account','required|integer');
   $this->form_validation->set_rules('recipient_name','Recipient name','required|trim|max_length[120]');
   $this->form_validation->set_rules('recipient_account','Recipient account','required|trim|max_length[50]');
   $this->form_validation->set_rules('recipient_bank','Recipient bank','required|trim|max_length[120]');
   $this->form_validation->set_rules('amount','Amount','required|numeric|greater_than[0]');
   if($this->form_validation->run()){
    $data=array('from_account_id'=>$this->input->post('from_account_id'),'beneficiary_id'=>$this->input->post('beneficiary_id') ?: NULL,'recipient_name'=>$this->input->post('recipient_name',TRUE),'recipient_account'=>$this->input->post('recipient_account',TRUE),'recipient_bank'=>$this->input->post('recipient_bank',TRUE),'recipient_routing'=>$this->input->post('swift_code',TRUE),'transfer_type'=>$this->input->post('transfer_type',TRUE) ?: 'domestic','amount'=>$this->input->post('amount'),'note'=>$this->input->post('note',TRUE),'scheduled_for'=>$this->input->post('scheduled_for') ?: date('Y-m-d'));
    list($ok,$message)=$this->Bank_model->create_transfer($this->user['id'],$data);
    if($ok){$this->Bank_model->audit('transfer_created','Transfer '.$message.' submitted',$this->user['id']);$this->session->set_flashdata('success','Transfer submitted. Reference: '.$message);redirect('transactions');}
    $this->session->set_flashdata('error',$message);
   } else $this->session->set_flashdata('error',validation_errors('',' '));
   redirect('transfer');
  }
  $settings=$this->Bank_model->settings();
  $this->render('customer/transfer',array('title'=>'Send money','accounts'=>$this->Bank_model->accounts($this->user['id']),'beneficiaries'=>$this->Bank_model->beneficiaries($this->user['id']),'daily_limit'=>(float)($settings['daily_transfer_limit']??25000),'used_today'=>$this->Bank_model->transfer_usage_today($this->user['id']),'intl_fee_pct'=>(float)($settings['international_fee_percent']??1.5),'intl_fee_flat'=>(float)($settings['international_fee_flat']??0),'scheduled'=>$this->Bank_model->scheduled_transfers($this->user['id'])));
 }
 public function beneficiaries(){
  if($this->input->method()==='post'){
   $this->form_validation->set_rules('name','Name','required|trim|max_length[120]');$this->form_validation->set_rules('account_number','Account number','required|trim|max_length[50]');$this->form_validation->set_rules('bank_name','Bank','required|trim|max_length[120]');
   if($this->form_validation->run()){$this->Bank_model->create_beneficiary($this->user['id'],array('name'=>$this->input->post('name',TRUE),'account_number'=>$this->input->post('account_number',TRUE),'bank_name'=>$this->input->post('bank_name',TRUE),'routing_code'=>$this->input->post('routing_code',TRUE),'currency'=>$this->input->post('currency',TRUE) ?: 'USD','status'=>'verified'));$this->session->set_flashdata('success','Beneficiary added.');}
   else $this->session->set_flashdata('error',validation_errors('',' ')); redirect('beneficiaries');
  }
  $this->render('customer/beneficiaries',array('title'=>'Beneficiaries','beneficiaries'=>$this->Bank_model->beneficiaries($this->user['id'])));
 }
 public function beneficiary_update($id){
  if($this->input->method()!=='post')redirect('beneficiaries');
  $this->form_validation->set_rules('name','Name','required|trim|max_length[120]');$this->form_validation->set_rules('account_number','Account number','required|trim|max_length[50]');$this->form_validation->set_rules('bank_name','Bank','required|trim|max_length[120]');
  if($this->form_validation->run()){if($this->Bank_model->update_beneficiary((int)$id,$this->user['id'],array('name'=>$this->input->post('name',TRUE),'account_number'=>$this->input->post('account_number',TRUE),'bank_name'=>$this->input->post('bank_name',TRUE),'routing_code'=>$this->input->post('routing_code',TRUE),'currency'=>$this->input->post('currency',TRUE)?:'USD'))){$this->session->set_flashdata('success','Beneficiary updated.');}else $this->session->set_flashdata('error','Beneficiary not found.');}
  else $this->session->set_flashdata('error',validation_errors('',' '));redirect('beneficiaries');
 }
 public function beneficiary_delete($id){
  if($this->input->method()!=='post')redirect('beneficiaries');
  if($this->Bank_model->delete_beneficiary((int)$id,$this->user['id'])){$this->session->set_flashdata('success','Beneficiary removed.');}else $this->session->set_flashdata('error','Beneficiary not found.');redirect('beneficiaries');
 }
 public function cancel($id){if($this->input->method()!=='post')redirect('transfer');list($ok,$m)=$this->Bank_model->cancel_transfer((int)$id,$this->user['id']);if($ok){$this->Bank_model->audit('transfer_cancelled','Scheduled transfer '.$m.' cancelled',$this->user['id']);$this->session->set_flashdata('success','Scheduled transfer '.$m.' cancelled.');}else $this->session->set_flashdata('error',$m);redirect('transfer');}
}
