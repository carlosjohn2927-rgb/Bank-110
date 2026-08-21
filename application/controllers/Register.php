<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Register extends MY_Controller {
 public function __construct(){parent::__construct();if($this->user)redirect('dashboard');}
 public function index(){
  if($this->input->method()==='post'){
   if(!$this->registration_open()){$this->session->set_flashdata('error','Online registration is currently disabled. Please contact support.');redirect('register');}
   $this->form_validation->set_rules('first_name','First name','required|trim|max_length[80]');
   $this->form_validation->set_rules('last_name','Last name','required|trim|max_length[80]');
   $this->form_validation->set_rules('email','Email','required|valid_email|is_unique[users.email]');
   $this->form_validation->set_rules('phone','Phone number','trim|max_length[40]');
   $this->form_validation->set_rules('password','Password','required|min_length[8]');
   $this->form_validation->set_rules('confirm','Confirm password','required|matches[password]');
   if($this->form_validation->run()){
    $uid=$this->Bank_model->register_customer(array('first_name'=>$this->input->post('first_name',TRUE),'last_name'=>$this->input->post('last_name',TRUE),'username'=>$this->input->post('email',TRUE),'email'=>$this->input->post('email',TRUE),'password'=>$this->input->post('password'),'phone'=>$this->input->post('phone',TRUE),'country'=>$this->input->post('country',TRUE)));
    if($uid){$this->Bank_model->audit('customer_registered','New customer registered via web',$uid);if(function_exists('notify_user')){try{notify_user($uid,'Welcome to NorthWest','<p>Hi '.htmlspecialchars($this->input->post('first_name',TRUE)).',</p><p>Thanks for registering. Your account is pending review and you\'ll be able to sign in once an administrator activates it.</p>','');}catch(Exception $e){}}$this->session->set_flashdata('success','Your registration was submitted. An administrator will activate your account shortly.');redirect('register?done=1');}
    $this->session->set_flashdata('error','Unable to complete your registration.');
   } else $this->session->set_flashdata('error',validation_errors('',' '));
   redirect('register');
  }
  $this->load->view('auth/register',array('open'=>$this->registration_open()));
 }
 private function registration_open(){
  try{$settings=$this->Bank_model->settings();return (($settings['registration_enabled']??'0')==='1');}catch(Exception $e){return FALSE;}
 }
}
