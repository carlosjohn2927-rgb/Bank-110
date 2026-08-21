<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Cards extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){$this->render('customer/cards',array('title'=>'Your cards','cards'=>$this->Bank_model->cards($this->user['id'])));}
 public function toggle($id){if($this->input->method()!=='post')show_404();$field=$this->input->post('field',TRUE);if($this->Bank_model->toggle_card((int)$id,$this->user['id'],$field))$this->session->set_flashdata('success','Card setting updated.');else $this->session->set_flashdata('error','Unable to update this card.');redirect('cards');}
}
