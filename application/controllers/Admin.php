<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Admin extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_admin();}
 private function page($view,$data=array()){$this->render('admin/'.$view,$data,'layouts/admin');}
 public function dashboard(){
  $range=$this->dashboard_range();
  $this->page('dashboard',array(
   'title'=>'Operations overview',
   'metrics'=>$this->Bank_model->admin_metrics(),
   'range'=>$range,
   'kpis'=>$this->Bank_model->admin_kpis_range($range),
   'volume'=>$this->Bank_model->transaction_volume_range($range),
   'signups'=>$this->Bank_model->signups_range($range),
   'categories'=>$this->Bank_model->spending_by_category_range($range),
   'distribution'=>$this->Bank_model->account_distribution(),
   'customers'=>$this->Bank_model->customers(5),
   'transactions'=>$this->Bank_model->all_transactions(NULL,6),
  ));
 }
 /** AJAX analytics endpoint for the dashboard range selector. */
 public function dashboard_data(){
  $range=$this->dashboard_range();
  return $this->json(array(
   'ok'=>TRUE,'range'=>$range,
   'kpis'=>$this->Bank_model->admin_kpis_range($range),
   'volume'=>$this->Bank_model->transaction_volume_range($range),
   'signups'=>$this->Bank_model->signups_range($range),
   'categories'=>$this->Bank_model->spending_by_category_range($range),
  ));
 }
 private function dashboard_range(){
  $allowed=array(7,30,90);
  $d=(int)$this->input->get('range',TRUE);
  return in_array($d,$allowed,TRUE)?$d:7;
 }
 public function customers(){$q=$this->input->get('q',TRUE);$per_page=25;$total=$this->Bank_model->count_customers($q);$this->load->library('pagination');$this->config->load('pagination_custom',TRUE);$cfg=$this->config->item('pagination_custom')?:array();$cfg['base_url']=site_url('admin/customers');$cfg['total_rows']=$total;$cfg['per_page']=$per_page;$cfg['reuse_query_string']=TRUE;$cfg['first_url']=$cfg['base_url'];$this->pagination->initialize($cfg);$page=$this->input->get('page');$offset=((int)max(1,$page)-1)*$per_page;$this->page('customers',array('title'=>'Customers','customers'=>$this->Bank_model->customers($per_page,$q,$offset),'q'=>$q,'pagination'=>$this->pagination->create_links()));}
 public function customer($id){$customer=$this->Bank_model->customer_detail((int)$id);if(!$customer)show_404();$this->page('customer_detail',array('title'=>$customer['first_name'].' '.$customer['last_name'],'customer'=>$customer));}
 public function kyc($id){if($this->input->method()!=='post')show_404();$status=$this->input->post('kyc_status',TRUE);if($this->Bank_model->update_kyc_status((int)$id,$status)){$this->Bank_model->audit('kyc_updated','Customer #'.$id.' KYC set to '.$status,$this->user['id']);$this->session->set_flashdata('success','KYC status updated.');}else $this->session->set_flashdata('error','Invalid KYC status.');redirect('admin/customers/'.$id);}
 public function kyc_documents(){
  $status=$this->input->get('status',TRUE)?:'pending';
  $per_page=25;$total=$this->Bank_model->count_kyc_documents($status);
  $this->load->library('pagination');$this->config->load('pagination_custom',TRUE);
  $cfg=$this->config->item('pagination_custom')?:array();
  $cfg['base_url']=site_url('admin/kyc-documents');$cfg['total_rows']=$total;$cfg['per_page']=$per_page;$cfg['reuse_query_string']=TRUE;$cfg['first_url']=$cfg['base_url'];
  $this->pagination->initialize($cfg);
  $page=max(1,(int)$this->input->get('page'));$offset=($page-1)*$per_page;
  $this->page('kyc_documents',array('title'=>'KYC review','documents'=>$this->Bank_model->all_kyc_documents($status,$per_page,$offset),'status'=>$status,'counts'=>$this->Bank_model->kyc_document_counts_by_status(),'pagination'=>$this->pagination->create_links()));
 }
 public function kyc_document($id){
  $doc=$this->Bank_model->kyc_document((int)$id);if(!$doc)show_404();
  $this->page('kyc_document',array('title'=>'Review document','doc'=>$doc));
 }
 public function kyc_review($id){
  if($this->input->method()!=='post')show_404();
  $approve=$this->input->post('action')==='approve';
  $note=$this->input->post('note',TRUE);
  list($ok,$msg)=$this->Bank_model->review_kyc_document((int)$id,$approve,$note,$this->user['id']);
  if($ok){$this->Bank_model->audit('kyc_document_'.$approve?'approved':'rejected','KYC document #'.$id.' '.$msg,$this->user['id']);$this->session->set_flashdata('success','Document '.$msg.'.');}
  else $this->session->set_flashdata('error',$msg);
  redirect('admin/kyc-documents');
 }
 public function account_status($id){if($this->input->method()!=='post')show_404();$status=$this->input->post('status',TRUE);list($ok,$m)=$this->Bank_model->admin_update_account_status((int)$id,$status);if($ok){$this->Bank_model->audit('account_status','Account #'.$id.' set to '.$m,$this->user['id']);$this->session->set_flashdata('success','Account is now '.$m.'.');}else $this->session->set_flashdata('error',$m);redirect($this->input->server('HTTP_REFERER') ?: 'admin/customers');}
 public function customer_create(){
  if($this->input->method()==='post'){
   $this->form_validation->set_rules('first_name','First name','required|trim|max_length[80]');$this->form_validation->set_rules('last_name','Last name','required|trim|max_length[80]');$this->form_validation->set_rules('username','Username','required|trim|is_unique[users.username]');$this->form_validation->set_rules('email','Email','required|valid_email|is_unique[users.email]');$this->form_validation->set_rules('password','Temporary password','required|min_length[8]');$this->form_validation->set_rules('opening_balance','Opening balance','required|numeric|greater_than_equal_to[0]');
   if($this->form_validation->run()){$id=$this->Bank_model->create_customer(array('first_name'=>$this->input->post('first_name',TRUE),'last_name'=>$this->input->post('last_name',TRUE),'username'=>$this->input->post('username',TRUE),'email'=>$this->input->post('email',TRUE),'password'=>$this->input->post('password'),'phone'=>$this->input->post('phone',TRUE),'country'=>$this->input->post('country',TRUE),'opening_balance'=>$this->input->post('opening_balance')));if($id){$this->Bank_model->audit('customer_created','Customer #'.$id.' created',$this->user['id']);if(function_exists('notify_user')){try{notify_user($id,'Welcome to NorthWest','<p>Hi '.htmlspecialchars($this->input->post('first_name',TRUE)).',</p><p>Your NorthWest account has been created. You can now sign in to online banking and manage your money.</p>','');}catch(Exception $e){}}$this->session->set_flashdata('success','Customer account created.');redirect('admin/customers/'.$id);}}
   $this->session->set_flashdata('error',validation_errors('',' '));redirect('admin/customers/create');
  }
  $this->page('customer_form',array('title'=>'Create customer'));
 }
 public function customer_edit($id){
  $id=(int)$id; $customer=$this->Bank_model->customer_detail($id);
  if(!$customer) show_404();
  if($this->input->method()==='post'){
   $this->form_validation->set_rules('first_name','First name','required|trim|max_length[80]');
   $this->form_validation->set_rules('last_name','Last name','required|trim|max_length[80]');
   $this->form_validation->set_rules('username','Username','required|trim|max_length[80]');
   $this->form_validation->set_rules('email','Email','required|valid_email|max_length[190]');
   $this->form_validation->set_rules('status','Status','required|in_list[active,pending,suspended,closed]');
   $password=(string)$this->input->post('password');
   if($password!=='') $this->form_validation->set_rules('password','New password','min_length[8]');
   $valid=$this->form_validation->run();
   $username=$this->input->post('username',TRUE); $email=$this->input->post('email',TRUE);
   if($valid && !$this->Bank_model->username_available($username,$id)){$valid=FALSE;$this->session->set_flashdata('error','That username is already in use.');}
   if($valid && !$this->Bank_model->email_available($email,$id)){$valid=FALSE;$this->session->set_flashdata('error','That email address is already in use.');}
   if($valid){
    $ok=$this->Bank_model->update_customer($id,array('first_name'=>$this->input->post('first_name',TRUE),'last_name'=>$this->input->post('last_name',TRUE),'username'=>$username,'email'=>$email,'status'=>$this->input->post('status',TRUE)),array('phone'=>$this->input->post('phone',TRUE),'address'=>$this->input->post('address',TRUE),'city'=>$this->input->post('city',TRUE),'country'=>$this->input->post('country',TRUE),'date_of_birth'=>$this->input->post('date_of_birth',TRUE)),$password);
    if($ok){$this->Bank_model->audit('customer_updated','Customer #'.$id.' profile updated by administrator',$this->user['id']);if($password!=='')$this->Bank_model->audit('customer_password_reset','Customer #'.$id.' password reset by administrator',$this->user['id']);$this->session->set_flashdata('success','Customer account updated.');redirect('admin/customers/'.$id);}
    $this->session->set_flashdata('error','Unable to update this customer account.');
   }elseif(!$this->session->flashdata('error')) $this->session->set_flashdata('error',validation_errors('',' '));
   redirect('admin/customers/'.$id.'/edit');
  }
  $this->page('customer_edit',array('title'=>'Edit customer','customer'=>$customer));
 }

 /** Start a signed, auditable customer-view session without exposing credentials. */
 public function login_as($id){
  if($this->input->method()!=='post') show_404();
  $customer=$this->Bank_model->user_by_id((int)$id,'customer');
  if(!$customer || $customer['status']!=='active'){
   $this->session->set_flashdata('error','Only active customer accounts can be opened from the dashboard.');
   redirect($this->input->server('HTTP_REFERER') ?: 'admin/customers');
  }
  $administrator=$this->user;
  $this->session->sess_regenerate(TRUE);
  unset($customer['password_hash']);
  $this->session->set_userdata('user',$customer);
  $this->session->set_userdata('impersonation_admin',array('id'=>(int)$administrator['id'],'username'=>$administrator['username'],'email'=>$administrator['email'],'first_name'=>$administrator['first_name'],'last_name'=>$administrator['last_name'],'role'=>'admin','created_at'=>$administrator['created_at']));
  $secret=(string)$this->config->item('auth_secret');
  $this->session->set_userdata('auth_signature',hash_hmac('sha256',$customer['id'].'|'.$customer['role'].'|'.$customer['created_at'],$secret));
  $this->Bank_model->audit('admin_impersonation_start','Administrator opened customer #'.$customer['id'].' dashboard as '.$customer['email'],$administrator['id']);
  redirect('dashboard');
 }

 /** Update the signed-in administrator's own contact details. */
 public function profile(){
  if($this->input->method()==='post'){
   $this->form_validation->set_rules('first_name','First name','required|trim|max_length[80]');
   $this->form_validation->set_rules('last_name','Last name','required|trim|max_length[80]');
   $this->form_validation->set_rules('email','Email','required|valid_email|max_length[190]');
   $valid=$this->form_validation->run();
   $email=$this->input->post('email',TRUE);
   if($valid && !$this->Bank_model->email_available($email,$this->user['id'])){$valid=FALSE;$this->session->set_flashdata('error','That email address is already in use.');}
   if($valid && $this->Bank_model->update_user_details($this->user['id'],array('first_name'=>$this->input->post('first_name',TRUE),'last_name'=>$this->input->post('last_name',TRUE),'email'=>$email),'admin')){
    $updated=$this->user;$updated['first_name']=$this->input->post('first_name',TRUE);$updated['last_name']=$this->input->post('last_name',TRUE);$updated['email']=$email;$this->user=$updated;$this->session->set_userdata('user',$updated);
    $this->Bank_model->audit('admin_profile_updated','Administrator profile updated',$this->user['id']);$this->session->set_flashdata('success','Your profile has been updated.');
   }elseif(!$this->session->flashdata('error')) $this->session->set_flashdata('error',validation_errors('',' '));
   redirect('admin/profile');
  }
  $profile=$this->Bank_model->user_by_id($this->user['id'],'admin');
  $this->page('profile',array('title'=>'My profile','profile'=>$profile?:$this->user));
 }

 /** Change the administrator password from the administrator dashboard. */
 public function profile_password(){
  if($this->input->method()!=='post') redirect('admin/profile');
  $this->form_validation->set_rules('current_password','Current password','required');
  $this->form_validation->set_rules('new_password','New password','required|min_length[8]');
  $this->form_validation->set_rules('confirm_new','Confirm password','required|matches[new_password]');
  if($this->form_validation->run() && $this->Bank_model->change_password($this->user['id'],$this->input->post('current_password'),$this->input->post('new_password'))){
   $this->Bank_model->audit('admin_password_changed','Administrator password changed',$this->user['id']);$this->session->set_flashdata('success','Your password has been updated.');
  }else $this->session->set_flashdata('error',validation_errors('',' ') ?: 'Your current password is incorrect.');
  redirect('admin/profile');
 }

 public function customer_status($id){if($this->input->method()!=='post')show_404();$status=$this->input->post('status',TRUE);if($this->Bank_model->update_customer_status((int)$id,$status)){$this->Bank_model->audit('customer_status','Customer #'.$id.' set to '.$status,$this->user['id']);$this->session->set_flashdata('success','Customer status updated.');}else $this->session->set_flashdata('error','Invalid customer status.');redirect('admin/customers/'.$id);}
 public function customer_adjust($id){if($this->input->method()!=='post')show_404();$this->form_validation->set_rules('account_id','Account','required|integer');$this->form_validation->set_rules('amount','Amount','required|numeric|greater_than[0]');$this->form_validation->set_rules('direction','Direction','required|in_list[credit,debit]');if($this->form_validation->run()){list($ok,$message)=$this->Bank_model->adjust_balance($this->input->post('account_id'),(int)$id,$this->input->post('amount'),$this->input->post('direction',TRUE),$this->input->post('note',TRUE));if($ok){$this->Bank_model->audit('balance_adjustment',$message.' on customer #'.$id,$this->user['id']);$this->session->set_flashdata('success','Balance adjusted. Reference: '.$message);}else $this->session->set_flashdata('error',$message);}else $this->session->set_flashdata('error',validation_errors('',' '));redirect('admin/customers/'.$id);}
 public function transactions(){$q=$this->input->get('q',TRUE);$this->admin_tx_page(NULL,$q,'All transactions','');}
 public function transfers(){$q=$this->input->get('q',TRUE);$this->admin_tx_page('transfers',$q,'Transfer operations','transfers');}
 public function deposits(){$q=$this->input->get('q',TRUE);$this->admin_tx_page('deposits',$q,'Deposit operations','deposits');}
 public function check_deposits(){
  $status=$this->input->get('status',TRUE)?:'pending';
  $per_page=25;$total=$this->Bank_model->count_check_deposits($status);
  $this->load->library('pagination');$this->config->load('pagination_custom',TRUE);
  $cfg=$this->config->item('pagination_custom')?:array();
  $cfg['base_url']=site_url('admin/check-deposits');$cfg['total_rows']=$total;$cfg['per_page']=$per_page;$cfg['reuse_query_string']=TRUE;$cfg['first_url']=$cfg['base_url'];
  $this->pagination->initialize($cfg);
  $page=$this->input->get('page');$offset=((int)max(1,$page)-1)*$per_page;
  $counts=array('pending'=>$this->Bank_model->count_check_deposits('pending'),'approved'=>$this->Bank_model->count_check_deposits('approved'),'rejected'=>$this->Bank_model->count_check_deposits('rejected'));
  $this->page('check_deposits',array('title'=>'Check deposits','deposits'=>$this->Bank_model->all_check_deposits($status,$per_page,$offset),'status'=>$status,'counts'=>$counts,'pagination'=>$this->pagination->create_links()));
 }
 public function check_deposit($id){$d=$this->Bank_model->check_deposit($id);if(!$d)show_404();$this->page('check_deposit',array('title'=>'Deposit '.$d['reference'],'deposit'=>$d));}
 public function check_deposit_review($id){
  if($this->input->method()!=='post')show_404();
  $approve=$this->input->post('action')==='approve';
  $note=$this->input->post('note',TRUE);
  list($ok,$msg)=$this->Bank_model->review_check_deposit((int)$id,$approve,$note);
  if($ok){$this->Bank_model->audit('check_deposit_'.($approve?'approved':'rejected'),'Check deposit #'.$id.' '.$msg,$this->user['id']);$this->session->set_flashdata('success','Deposit '.$msg.'.');}
  else $this->session->set_flashdata('error',$msg);
  redirect('admin/check-deposits');
 }
 private function admin_tx_page($kind,$q,$heading,$kindkey){
  $per_page=25; $total=$this->Bank_model->count_all_transactions($kind,$q);
  $this->load->library('pagination');$this->config->load('pagination_custom',TRUE);$cfg=$this->config->item('pagination_custom')?:array();
  $cfg['base_url']=site_url($kindkey===''?'admin/transactions':'admin/'.$kindkey);$cfg['total_rows']=$total;$cfg['per_page']=$per_page;$cfg['reuse_query_string']=TRUE;$cfg['first_url']=$cfg['base_url'];
  $this->pagination->initialize($cfg);$page=$this->input->get('page');$offset=((int)max(1,$page)-1)*$per_page;
  $this->page('transactions',array('title'=>ucfirst($kindkey===''?'transactions':$kindkey),'heading'=>$heading,'transactions'=>$this->Bank_model->all_transactions($kind,$per_page,$q,$offset),'kind'=>$kindkey,'pagination'=>$this->pagination->create_links()));
 }
 public function export_transactions($kind=NULL){$rows=$this->Bank_model->all_transactions($kind,5000);$name='northwest-transactions-'.date('Y-m-d').'.csv';$this->output->set_content_type('text/csv')->set_header('Content-Disposition: attachment; filename="'.$name.'"');$out=fopen('php://output','w');fputcsv($out,array('Date','Reference','Customer','Category','Type','Amount','Currency','Status'));foreach($rows as $t){fputcsv($out,array($t['transaction_date'],$t['reference'],($t['first_name']??'').' '.($t['last_name']??''),$t['category'],$t['type'],$t['amount'],$t['currency'],$t['status']));}fclose($out);}
 public function transaction_status($id){if($this->input->method()!=='post')show_404();$status=$this->input->post('status',TRUE);if($this->Bank_model->set_transaction_status((int)$id,$status)){$this->Bank_model->audit('transaction_status','Transaction #'.$id.' set to '.$status,$this->user['id']);$this->session->set_flashdata('success','Transaction updated.');}redirect($this->input->server('HTTP_REFERER') ?: 'admin/transactions');}
 public function transaction($id){$tx=$this->Bank_model->transaction((int)$id);if(!$tx)show_404();$this->page('transaction_detail',array('title'=>'Transaction '.$tx['reference'],'tx'=>$tx));}
 public function loans(){if($this->input->method()==='post'){$this->form_validation->set_rules('user_id','Customer','required|integer');$this->form_validation->set_rules('amount','Amount','required|numeric|greater_than[0]');$this->form_validation->set_rules('term_months','Term','required|integer');if($this->form_validation->run()){list($ok,$message)=$this->Bank_model->admin_issue_loan(array('user_id'=>$this->input->post('user_id'),'type'=>$this->input->post('type',TRUE),'amount'=>$this->input->post('amount'),'term_months'=>$this->input->post('term_months'),'interest_rate'=>$this->input->post('interest_rate')));if($ok){$this->Bank_model->audit('loan_issued','Loan '.$message.' issued to customer #'.$this->input->post('user_id'),$this->user['id']);$this->session->set_flashdata('success','Loan issued. Reference: '.$message);}else $this->session->set_flashdata('error',$message);}else $this->session->set_flashdata('error',validation_errors('',' '));redirect('admin/loans');}$this->page('loans',array('title'=>'Loan management','loans'=>$this->Bank_model->all_loans(),'customers'=>$this->Bank_model->customers(200)));}
 public function cards(){if($this->input->method()==='post'){$this->form_validation->set_rules('account_id','Account','required|integer');$this->form_validation->set_rules('card_type','Card type','required|in_list[virtual,physical]');$this->form_validation->set_rules('daily_limit','Daily limit','numeric|greater_than[0]');if($this->form_validation->run()){list($ok,$message)=$this->Bank_model->admin_create_card(array('account_id'=>$this->input->post('account_id'),'card_type'=>$this->input->post('card_type',TRUE),'daily_limit'=>$this->input->post('daily_limit')?:10000));if($ok){$this->Bank_model->audit('card_issued',$message,$this->user['id']);$this->session->set_flashdata('success',$message);}else $this->session->set_flashdata('error',$message);}else $this->session->set_flashdata('error',validation_errors('',' '));redirect('admin/cards');}$this->page('cards',array('title'=>'Card management','cards'=>$this->Bank_model->all_cards(),'accounts'=>$this->Bank_model->customers_with_accounts()));}
 public function tickets(){$per_page=25;$total=$this->Bank_model->count_tickets();$this->load->library('pagination');$this->config->load('pagination_custom',TRUE);$cfg=$this->config->item('pagination_custom')?:array();$cfg['base_url']=site_url('admin/tickets');$cfg['total_rows']=$total;$cfg['per_page']=$per_page;$cfg['reuse_query_string']=TRUE;$cfg['first_url']=$cfg['base_url'];$this->pagination->initialize($cfg);$page=$this->input->get('page');$offset=((int)max(1,$page)-1)*$per_page;$this->page('tickets',array('title'=>'Support tickets','tickets'=>$this->Bank_model->tickets(NULL,$per_page,$offset),'pagination'=>$this->pagination->create_links()));}
 public function ticket($id){$ticket=$this->Bank_model->ticket((int)$id);if(!$ticket)show_404();$this->page('ticket_detail',array('title'=>$ticket['subject'],'ticket'=>$ticket));}
 public function ticket_reply($id){if($this->input->method()!=='post')show_404();$this->form_validation->set_rules('message','Reply','required|trim|max_length[5000]');if($this->form_validation->run() && $this->Bank_model->reply_ticket((int)$id,$this->user['id'],$this->input->post('message',TRUE),$this->input->post('status',TRUE))){$this->Bank_model->audit('ticket_reply','Replied to ticket #'.$id,$this->user['id']);$this->session->set_flashdata('success','Reply added.');}else $this->session->set_flashdata('error',validation_errors('',' ') ?: 'Unable to add reply.');redirect('admin/tickets/'.$id);}
 public function tutorial(){$this->page('tutorial',array('title'=>'Platform tutorial'));}
 public function audit_logs(){$q=$this->input->get('q',TRUE);$this->page('audit_logs',array('title'=>'Audit log','logs'=>$this->Bank_model->audit_log(200,$q),'q'=>$q));}
 public function exchange_rates(){if($this->input->method()==='post'){$pairs=$this->input->post('pairs',TRUE);$n=0;if(is_array($pairs))foreach($pairs as $pair=>$rate){$parts=explode('|',$pair);if(count($parts)===2 && $this->Bank_model->save_exchange_rate($parts[0],$parts[1],$rate))$n++;}if($n){$this->Bank_model->audit('exchange_rates_updated','Updated '.$n.' exchange rates',$this->user['id']);$this->session->set_flashdata('success',$n.' exchange rate(s) updated.');}else $this->session->set_flashdata('error','No rates were updated.');redirect('admin/exchange_rates');}$this->page('exchange_rates',array('title'=>'Exchange rates','rates'=>$this->Bank_model->exchange_rates()));}
 public function export_customers(){$q=$this->input->get('q',TRUE);$rows=$this->Bank_model->customers(5000,$q);$name='northwest-customers-'.date('Y-m-d').'.csv';$this->output->set_content_type('text/csv')->set_header('Content-Disposition: attachment; filename="'.$name.'"');$out=fopen('php://output','w');fputcsv($out,array('ID','First name','Last name','Email','Username','Status','Total balance','Accounts','Last login'));foreach($rows as $c){fputcsv($out,array($c['id'],$c['first_name'],$c['last_name'],$c['email'],$c['username'],$c['status'],$c['total_balance'],$c['account_count'],$c['last_login_at']));}fclose($out);}
 public function admin_users(){
  if($this->input->method()==='post'){
   $this->form_validation->set_rules('first_name','First name','required|trim|max_length[80]');$this->form_validation->set_rules('last_name','Last name','required|trim|max_length[80]');
   $this->form_validation->set_rules('username','Username','required|trim|is_unique[users.username]');$this->form_validation->set_rules('email','Email','required|valid_email|is_unique[users.email]');$this->form_validation->set_rules('password','Password','required|min_length[8]');
   if($this->form_validation->run()){$this->Bank_model->create_admin(array('first_name'=>$this->input->post('first_name',TRUE),'last_name'=>$this->input->post('last_name',TRUE),'username'=>$this->input->post('username',TRUE),'email'=>$this->input->post('email',TRUE),'password'=>$this->input->post('password')));$this->Bank_model->audit('admin_created','Administrator '.$this->input->post('username',TRUE).' created',$this->user['id']);$this->session->set_flashdata('success','Administrator created.');}
   else $this->session->set_flashdata('error',validation_errors('',' '));redirect('admin/admin_users');
  }
  $this->page('admin_users',array('title'=>'Admin users','admins'=>$this->Bank_model->admins()));
 }
 public function settings(){if($this->input->method()==='post'){$keys=array('institution_name','support_email','default_currency','daily_transfer_limit','session_timeout','registration_enabled','routing_number','international_fee_percent','international_fee_flat','announcement_text','seo_site_name','seo_title','seo_description','seo_keywords');$values=array();foreach($keys as $k){$v=$this->input->post($k,TRUE);if($v!==NULL)$values[$k]=$v;}if($values){$this->Bank_model->save_settings($values);$this->Bank_model->audit('settings_updated','System settings updated',$this->user['id']);$this->session->set_flashdata('success','System settings saved.');}else $this->session->set_flashdata('error','No settings were submitted.');redirect('admin/settings');}$this->page('settings',array('title'=>'System settings','settings'=>$this->Bank_model->settings()));}
}
