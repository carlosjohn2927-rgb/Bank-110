<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Transactions extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){
  $filters=array('search'=>$this->input->get('q',TRUE),'type'=>$this->input->get('type',TRUE));
  $this->render('customer/transactions',array('title'=>'Transactions','transactions'=>$this->Bank_model->transactions_for_user($this->user['id'],100,$filters),'filters'=>$filters));
 }
}
