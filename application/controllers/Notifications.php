<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Notifications extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}

 public function index(){
  $filter=$this->input->get('filter',TRUE);
  $per_page=25;
  $total=$this->Bank_model->count_notifications($this->user['id'],$filter);
  $this->load->library('pagination');$this->config->load('pagination_custom',TRUE);
  $cfg=$this->config->item('pagination_custom')?:array();
  $cfg['base_url']=site_url('notifications');$cfg['total_rows']=$total;$cfg['per_page']=$per_page;
  $cfg['reuse_query_string']=TRUE;$cfg['first_url']=$cfg['base_url'];
  $this->pagination->initialize($cfg);
  $page=max(1,(int)$this->input->get('page'));$offset=($page-1)*$per_page;
  $this->render('customer/notifications',array(
   'title'=>'Notifications',
   'notifs'=>$this->Bank_model->notifications($this->user['id'],$per_page,FALSE,$filter,$offset),
   'unread_total'=>$this->Bank_model->unread_notification_count($this->user['id']),
   'unread_by_type'=>$this->Bank_model->unread_counts_by_type($this->user['id']),
   'filter'=>$filter,'total'=>$total,'pagination'=>$this->pagination->create_links(),
  ));
 }

 /** Mark a single notification read (and follow its link). */
 public function read($id=NULL){
  $n=$this->Bank_model->notification((int)$id,$this->user['id']);
  if($n){
   $this->Bank_model->mark_notification_read($n['id'],$this->user['id']);
   $this->Bank_model->audit('notification_read','Opened notification #'.$n['id'],$this->user['id']);
   if($this->input->is_ajax_request()){return $this->json(array('ok'=>TRUE,'unread'=>$this->Bank_model->unread_notification_count($this->user['id'])));}
   if($n['link']){redirect($n['link']);return;}
  }
  if($this->input->is_ajax_request())return $this->json(array('ok'=>FALSE),404);
  redirect('notifications');
 }

 public function mark_all(){
  if($this->input->method()!=='post')redirect('notifications');
  $this->Bank_model->mark_notifications_read($this->user['id']);
  $this->session->set_flashdata('success','All notifications marked as read.');
  redirect('notifications');
 }

 public function delete($id=NULL){
  if($this->input->method()!=='post')redirect('notifications');
  $this->Bank_model->delete_notification((int)$id,$this->user['id']);
  if($this->input->is_ajax_request())return $this->json(array('ok'=>TRUE));
  $this->session->set_flashdata('success','Notification deleted.');
  redirect($this->input->server('HTTP_REFERER') ?: 'notifications');
 }

 public function delete_all(){
  if($this->input->method()!=='post')redirect('notifications');
  $this->Bank_model->delete_all_notifications($this->user['id']);
  $this->session->set_flashdata('success','All notifications cleared.');
  redirect('notifications');
 }
}
