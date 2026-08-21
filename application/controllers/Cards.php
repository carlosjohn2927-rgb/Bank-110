<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Cards extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){$this->render('customer/cards',array('title'=>'Your cards','cards'=>$this->Bank_model->cards($this->user['id']),'accounts'=>$this->Bank_model->accounts($this->user['id'])));}
 public function toggle($id){if($this->input->method()!=='post')show_404();$field=$this->input->post('field',TRUE);if($this->Bank_model->toggle_card((int)$id,$this->user['id'],$field))$this->session->set_flashdata('success','Card setting updated.');else $this->session->set_flashdata('error','Unable to update this card.');redirect('cards');}
 public function report_lost($id){if($this->input->method()!=='post')redirect('cards');list($ok,$m)=$this->Bank_model->report_lost_card((int)$id,$this->user['id']);if($ok){$this->Bank_model->audit('card_reported_lost','Card #'.$id.' reported lost/stolen',$this->user['id']);$this->session->set_flashdata('success',$m.' A replacement can be issued from support.');}else $this->session->set_flashdata('error',$m);redirect('cards');}
 public function create(){
  if($this->input->method()!=='post')redirect('cards');
  $this->form_validation->set_rules('account_id','Account','required|integer');
  $this->form_validation->set_rules('card_type','Card type','required|in_list[virtual,physical]');
  $this->form_validation->set_rules('daily_limit','Daily limit','numeric|greater_than[0]|less_than[100000]');
  if($this->form_validation->run()){
   list($ok,$message)=$this->Bank_model->create_card($this->user['id'],array('account_id'=>$this->input->post('account_id'),'card_type'=>$this->input->post('card_type',TRUE),'daily_limit'=>$this->input->post('daily_limit')?:10000,'cardholder_name'=>$this->input->post('cardholder_name',TRUE)));
   if($ok){$this->Bank_model->audit('card_created',$message,$this->user['id']);$this->session->set_flashdata('success',$message);}
   else $this->session->set_flashdata('error',$message);
  } else $this->session->set_flashdata('error',validation_errors('',' '));
  redirect('cards');
 }
}
