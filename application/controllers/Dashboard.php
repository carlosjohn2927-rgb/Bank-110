<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Dashboard extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){
  $id=$this->user['id']; $accounts=$this->Bank_model->accounts($id);
  $this->render('customer/dashboard',array('title'=>'Dashboard','accounts'=>$accounts,'total_balance'=>$this->Bank_model->total_balance($id),'transactions'=>$this->Bank_model->transactions_for_user($id,6)));
 }
}
