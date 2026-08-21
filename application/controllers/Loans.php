<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Loans extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){$this->render('customer/loans',array('title'=>'Loans & credit','loans'=>$this->Bank_model->loans($this->user['id'])));}
}
