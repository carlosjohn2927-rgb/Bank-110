<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Loans extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){$this->render('customer/loans',array('title'=>'Loans & credit','loans'=>$this->Bank_model->loans($this->user['id']),'accounts'=>$this->Bank_model->accounts($this->user['id']),'total_balance'=>$this->Bank_model->total_balance($this->user['id'])));}
 public function pay($id){
  if($this->input->method()!=='post')redirect('loans');
  $this->form_validation->set_rules('account_id','Account','required|integer');
  if($this->form_validation->run()){
   list($ok,$message)=$this->Bank_model->pay_loan((int)$id,$this->user['id'],(int)$this->input->post('account_id'));
   if($ok){$this->Bank_model->audit('loan_payment','Loan payment '.$message,$this->user['id']);$this->session->set_flashdata('success','Loan payment made. Reference: '.$message);}
   else $this->session->set_flashdata('error',$message);
  } else $this->session->set_flashdata('error',validation_errors('',' '));
  redirect('loans');
 }
 public function create(){
  if($this->input->method()!=='post')redirect('loans');
  $this->form_validation->set_rules('type','Loan type','required|trim|max_length[80]');
  $this->form_validation->set_rules('amount','Amount','required|numeric|greater_than_equal_to[100]|less_than_equal_to[250000]');
  $this->form_validation->set_rules('term_months','Term','required|integer|greater_than_equal_to[6]|less_than_equal_to[120]');
  if($this->form_validation->run()){
   list($ok,$message)=$this->Bank_model->create_loan($this->user['id'],array('type'=>$this->input->post('type',TRUE),'amount'=>$this->input->post('amount'),'term_months'=>$this->input->post('term_months'),'interest_rate'=>$this->input->post('interest_rate')));
   if($ok){$this->Bank_model->audit('loan_applied','Loan application '.$message.' submitted',$this->user['id']);$this->session->set_flashdata('success','Loan approved. Reference: '.$message);}
   else $this->session->set_flashdata('error',$message);
  } else $this->session->set_flashdata('error',validation_errors('',' '));
  redirect('loans');
 }
}
