<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Bills extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){$this->render('customer/bills',array('title'=>'Pay a bill','accounts'=>$this->Bank_model->accounts($this->user['id']),'beneficiaries'=>$this->Bank_model->beneficiaries($this->user['id'])));}
 public function pay(){
  if($this->input->method()!=='post')redirect('bills');
  $this->form_validation->set_rules('from_account_id','From account','required|integer');
  $this->form_validation->set_rules('beneficiary_id','Payee','required|integer');
  $this->form_validation->set_rules('amount','Amount','required|numeric|greater_than[0]');
  if($this->form_validation->run()){
   $b=$this->db->where(array('id'=>(int)$this->input->post('beneficiary_id'),'user_id'=>$this->user['id']))->get('beneficiaries')->row_array();
   if(!$b){$this->session->set_flashdata('error','Select a valid payee.');redirect('bills');}
   $data=array('from_account_id'=>$this->input->post('from_account_id'),'beneficiary_id'=>$b['id'],'recipient_name'=>$b['name'],'recipient_account'=>$b['account_number'],'recipient_bank'=>$b['bank_name'],'transfer_type'=>'domestic','category'=>'Bill payment','amount'=>$this->input->post('amount'),'note'=>'Bill payment','scheduled_for'=>$this->input->post('scheduled_for')?:date('Y-m-d'));
   list($ok,$message)=$this->Bank_model->create_transfer($this->user['id'],$data);
   if($ok){$this->Bank_model->audit('bill_paid','Bill payment '.$message.' to '.$b['name'],$this->user['id']);$this->session->set_flashdata('success','Bill paid. Reference: '.$message);redirect('transactions');}
   $this->session->set_flashdata('error',$message);
  } else $this->session->set_flashdata('error',validation_errors('',' '));
  redirect('bills');
 }
}
