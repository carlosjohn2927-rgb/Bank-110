<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Notifications extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){
  if($this->input->method()==='post'){$this->Bank_model->mark_notifications_read($this->user['id']);redirect('notifications');}
  $this->render('customer/notifications',array('title'=>'Notifications','notifs'=>$this->Bank_model->notifications($this->user['id'],100)));
 }
}
