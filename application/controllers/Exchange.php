<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Exchange extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){
  if($this->input->method()==='post'){
   $this->form_validation->set_rules('from_account_id','From account','required|integer');
   $this->form_validation->set_rules('to_account_id','To account','required|integer');
   $this->form_validation->set_rules('amount','Amount','required|numeric|greater_than[0]');
   if($this->form_validation->run()){
    list($ok,$result)=$this->Bank_model->exchange_convert($this->user['id'],$this->input->post('from_account_id'),$this->input->post('to_account_id'),$this->input->post('amount'));
    if($ok){$this->Bank_model->audit('exchange','Currency exchange '.$result['from_currency'].'→'.$result['to_currency'].' of '.$result['converted'].' '.$result['to_currency'],$this->user['id']);$this->session->set_flashdata('success','Exchanged '.$this->input->post('amount').' '.$result['from_currency'].' to '.$result['converted'].' '.$result['to_currency'].' (rate '.number_format($result['rate'],4).'). Reference: '.$result['reference']);redirect('exchange');}
    $this->session->set_flashdata('error',$result);
   } else $this->session->set_flashdata('error',validation_errors('',' '));
   redirect('exchange');
  }
  $accounts=$this->Bank_model->accounts($this->user['id']);
  $this->render('customer/exchange',array('title'=>'Currency exchange','accounts'=>$accounts,'rates'=>$this->Bank_model->exchange_rates()));
 }
}
