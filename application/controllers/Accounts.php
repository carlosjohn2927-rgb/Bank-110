<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Accounts extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){$id=$this->user['id'];$s=$this->Bank_model->monthly_summary($id);$settings=$this->Bank_model->settings();$this->render('customer/accounts',array('title'=>'My accounts','accounts'=>$this->Bank_model->accounts($id),'summary'=>$s,'routing_number'=>$settings['routing_number']??'021000021'));}
 public function create(){
  if($this->input->method()==='post'){
   $this->form_validation->set_rules('name','Account name','trim|max_length[120]');
   $this->form_validation->set_rules('type','Account type','required|in_list[checking,savings,investment]');
   $this->form_validation->set_rules('opening_balance','Opening balance','numeric|greater_than_equal_to[0]|less_than[100000]');
   if($this->form_validation->run()){
    $id=$this->Bank_model->create_account($this->user['id'],array('name'=>$this->input->post('name',TRUE),'type'=>$this->input->post('type',TRUE),'currency'=>$this->input->post('currency',TRUE)?:'USD','opening_balance'=>$this->input->post('opening_balance')));
    if($id){$this->Bank_model->audit('account_created','New '.$this->input->post('type',TRUE).' account opened',$this->user['id']);$this->session->set_flashdata('success','Your new account is ready.');redirect('accounts');}
    $this->session->set_flashdata('error','Unable to open the account. Please try again.');
   } else $this->session->set_flashdata('error',validation_errors('',' '));
   redirect('accounts');
  }
  redirect('accounts');
 }
 public function status($id){if($this->input->method()!=='post')show_404();$status=$this->input->post('status',TRUE);list($ok,$m)=$this->Bank_model->update_account_status((int)$id,$this->user['id'],$status);if($ok){$this->Bank_model->audit('account_status','Account #'.$id.' set to '.$m,$this->user['id']);$this->session->set_flashdata('success','Account is now '.$m.'.');}else $this->session->set_flashdata('error',$m);redirect('accounts');}
 public function deposit(){
  if($this->input->method()!=='post')show_404();
  $this->form_validation->set_rules('account_id','Account','required|integer');
  $this->form_validation->set_rules('amount','Amount','required|numeric|greater_than[0]|less_than[100000]');
  if($this->form_validation->run()){
   $account=$this->Bank_model->account((int)$this->input->post('account_id'),$this->user['id']);
   if(!$account || $account['status']!=='active'){$this->session->set_flashdata('error','The selected account is unavailable.');redirect('accounts');}
   $amount=round((float)$this->input->post('amount'),2);
   $reference='DEP-'.date('ymd').'-'.random_int(10000,99999);$now=date('Y-m-d H:i:s');
   $this->db->trans_start();
   $this->db->where('id',$account['id'])->set('balance','balance+'.$amount,FALSE)->set('available_balance','available_balance+'.$amount,FALSE)->update('accounts');
   $this->db->insert('transactions',array('account_id'=>$account['id'],'reference'=>$reference,'type'=>'credit','category'=>'Deposit','description'=>'Cash or transfer deposit','amount'=>$amount,'currency'=>$account['currency'],'balance_after'=>(float)$account['available_balance']+$amount,'status'=>'completed','transaction_date'=>date('Y-m-d'),'created_at'=>$now));
   $this->db->trans_complete();
   if($this->db->trans_status()){$this->Bank_model->audit('deposit','Deposit of '.$amount.' '.$account['currency'].' to account #'.$account['id'],$this->user['id']);$this->Bank_model->add_notification($this->user['id'],'deposit','Deposit of '.money($amount,$account['currency']).' completed','Reference: '.$reference,'accounts');$this->session->set_flashdata('success','Deposit completed. Reference: '.$reference);}
   else $this->session->set_flashdata('error','The deposit could not be completed.');
  } else $this->session->set_flashdata('error',validation_errors('',' '));
  redirect('accounts');
 }
}
