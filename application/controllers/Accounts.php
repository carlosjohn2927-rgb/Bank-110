<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Accounts extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){$this->render('customer/accounts',array('title'=>'My accounts','accounts'=>$this->Bank_model->accounts($this->user['id'])));}
}
