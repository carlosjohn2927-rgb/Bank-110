<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Profile extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){
  if($this->input->method()==='post'){
   $this->form_validation->set_rules('first_name','First name','required|trim|max_length[80]');
   $this->form_validation->set_rules('last_name','Last name','required|trim|max_length[80]');
   $this->form_validation->set_rules('email','Email','required|valid_email|max_length[190]');
   $valid=$this->form_validation->run(); $email=$this->input->post('email',TRUE);
   if($valid && !$this->Bank_model->email_available($email,$this->user['id'])){$valid=FALSE;$this->session->set_flashdata('error','That email address is already in use.');}
   if($valid){
    $profile_data=array('phone'=>$this->input->post('phone',TRUE),'address'=>$this->input->post('address',TRUE),'city'=>$this->input->post('city',TRUE),'country'=>$this->input->post('country',TRUE),'date_of_birth'=>$this->input->post('date_of_birth') ?: NULL,'updated_at'=>date('Y-m-d H:i:s'));
    if(!empty($_FILES['avatar']['name'])){
     $relative=trim($this->config->item('upload_path'),'/\\\\');$upload_path=preg_match('#^([A-Za-z]:)?[\\\\\\\\/]#',$relative)?$relative:FCPATH.$relative;
     $this->load->library('upload',array('upload_path'=>$upload_path,'allowed_types'=>'jpg|jpeg|png|webp','max_size'=>$this->config->item('max_upload_kb'),'encrypt_name'=>TRUE,'remove_spaces'=>TRUE));
     if(!$this->upload->do_upload('avatar')){$this->session->set_flashdata('error',strip_tags($this->upload->display_errors('',' ')));redirect('settings');}
     $file=$this->upload->data();$relative=trim($relative,'/\\\\');$profile_data['avatar_path']=$relative.'/'.$file['file_name'];
    }
    if($this->Bank_model->update_profile($this->user['id'],array('first_name'=>$this->input->post('first_name',TRUE),'last_name'=>$this->input->post('last_name',TRUE),'email'=>$email,'updated_at'=>date('Y-m-d H:i:s')),$profile_data)){
     $updated=$this->user;$updated['first_name']=$this->input->post('first_name',TRUE);$updated['last_name']=$this->input->post('last_name',TRUE);$updated['email']=$email;$this->user=$updated;$this->session->set_userdata('user',$updated);
     $this->session->set_flashdata('success','Profile updated.');
    }else $this->session->set_flashdata('error','Unable to update your profile.');
   }elseif(!$this->session->flashdata('error')) $this->session->set_flashdata('error',validation_errors('',' '));
   redirect('settings');
  }
  $this->render('customer/settings',array('title'=>'Settings','profile'=>$this->Bank_model->profile($this->user['id']),'preferences'=>$this->Bank_model->preferences($this->user['id'])));
 }
 public function preferences(){if($this->input->method()!=='post')redirect('settings');$keys=array('notify_transfers','notify_tickets','notify_security','notify_loans','notify_cards','notify_general','email_alerts','language','region');foreach($keys as $k){$v=$this->input->post($k,TRUE);$this->Bank_model->set_preference($this->user['id'],$k,$v);}if($this->input->post('language',TRUE))$this->session->set_userdata('language',$this->input->post('language',TRUE));$this->Bank_model->audit('preferences_updated','Notification and region preferences updated',$this->user['id']);$this->session->set_flashdata('success','Preferences saved.');redirect('settings');}
 public function password(){if($this->input->method()!=='post')redirect('settings');$this->form_validation->set_rules('current_password','Current password','required');$this->form_validation->set_rules('new_password','New password','required|min_length[8]');$this->form_validation->set_rules('confirm_new','Confirm password','required|matches[new_password]');if($this->form_validation->run() && $this->Bank_model->change_password($this->user['id'],$this->input->post('current_password'),$this->input->post('new_password'))){$this->Bank_model->audit('password_changed','Password changed by user',$this->user['id']);$this->session->set_flashdata('success','Your password has been updated.');}else $this->session->set_flashdata('error',validation_errors('',' ') ?: 'Your current password is incorrect.');redirect('settings');}
 public function twofa(){if($this->input->method()!=='post')redirect('settings');$enabled=$this->input->post('twofa_enabled')==='1';$this->Bank_model->set_twofa($this->user['id'],$enabled);$this->Bank_model->audit('twofa_updated','Two-factor authentication '.($enabled?'enabled':'disabled'),$this->user['id']);$this->session->set_flashdata('success','Two-factor authentication '.($enabled?'enabled':'disabled').'.');redirect('settings');}
 /**
  * TOTP authenticator-app setup, step 1: generate a secret and show QR code.
  */
 public function totp_setup(){
  $secret=$this->Bank_model->totp_begin_enrollment($this->user['id']);
  $this->load->library(array('Totp','Qr_code'));
  $account=$this->user['email'];
  $uri=Totp::provisioning_uri($secret,$account,'NorthWest');
  $qr=Qr_code::svg($uri,5,3);
  // Group the secret in 4-char chunks for easier manual entry.
  $chunked=trim(chunk_split($secret,4,' '));
  $this->render('customer/totp_setup',array('title'=>'Set up authenticator app','secret'=>$secret,'secret_chunked'=>$chunked,'qr'=>$qr,'uri'=>$uri));
 }
 /**
  * TOTP setup, step 2: confirm the code, enable 2FA, show backup codes.
  */
 public function totp_confirm(){
  if($this->input->method()!=='post')redirect('settings/twofa/setup');
  $this->form_validation->set_rules('code','Authentication code','required|trim|min_length[6]|max_length[6]|numeric');
  if(!$this->form_validation->run()){$this->session->set_flashdata('error',validation_errors(' ',' '));redirect('settings/twofa/setup');}
  list($ok,$data)=$this->Bank_model->totp_confirm_enrollment($this->user['id'],$this->input->post('code'));
  if(!$ok){$this->session->set_flashdata('error',$data);redirect('settings/twofa/setup');}
  $this->Bank_model->audit('totp_enabled','Authenticator app 2FA enabled',$this->user['id']);
  $this->session->set_flashdata('totp_backup_codes',$data);
  $this->session->set_flashdata('success','Authenticator app enabled. Save your backup codes somewhere safe.');
  redirect('settings/twofa/backup');
 }
 /** One-time display of backup codes after TOTP enrollment. */
 public function totp_backup(){
  $codes=$this->session->flashdata('totp_backup_codes');
  if(!$codes)redirect('settings');
  $this->render('customer/totp_backup',array('title'=>'Backup codes','codes'=>$codes));
 }
 /** Disable TOTP (requires password). */
 public function totp_disable(){
  if($this->input->method()!=='post')redirect('settings');
  $this->form_validation->set_rules('current_password','Password','required');
  $user=$this->db->select('password_hash')->where('id',$this->user['id'])->get('users')->row_array();
  if(!$user || !password_verify($this->input->post('current_password'),$user['password_hash'])){
   $this->session->set_flashdata('error','Your password was incorrect. Authenticator app was not disabled.');redirect('settings');
  }
  $this->Bank_model->totp_disable($this->user['id']);
  $this->Bank_model->audit('totp_disabled','Authenticator app 2FA disabled',$this->user['id']);
  $this->session->set_flashdata('success','Authenticator app has been disabled.');redirect('settings');
 }
 public function kyc(){if($this->input->method()!=='post')redirect('settings');if($this->Bank_model->request_kyc($this->user['id'])){$this->Bank_model->audit('kyc_requested','Customer requested KYC verification',$this->user['id']);$this->session->set_flashdata('success','Your verification request has been submitted for review.');}else $this->session->set_flashdata('error','Unable to submit your request.');redirect('settings');}
}
