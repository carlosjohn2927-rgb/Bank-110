<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Support extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){$this->render('customer/support',array('title'=>'Support','tickets'=>$this->Bank_model->tickets($this->user['id'])));}
 public function create(){if($this->input->method()!=='post')redirect('support');$this->form_validation->set_rules('subject','Subject','required|trim|max_length[180]');$this->form_validation->set_rules('message','Message','required|trim|max_length[5000]');if($this->form_validation->run()){$ref=$this->Bank_model->create_ticket($this->user['id'],array('subject'=>$this->input->post('subject',TRUE),'category'=>$this->input->post('category',TRUE) ?: 'general','message'=>$this->input->post('message',TRUE)));$this->session->set_flashdata('success','Support request created: '.$ref);}else $this->session->set_flashdata('error',validation_errors('',' '));redirect('support');}
}
