<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Transactions extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){
  $filters=array('search'=>$this->input->get('q',TRUE),'type'=>$this->input->get('type',TRUE));
  $per_page=25;
  $total=$this->Bank_model->count_transactions_for_user($this->user['id'],$filters);
  $this->load->library('pagination');
  $this->config->load('pagination_custom',TRUE);
  $cfg=$this->config->item('pagination_custom') ?: array();
  $cfg['base_url']=site_url('transactions');
  $cfg['total_rows']=$total;
  $cfg['per_page']=$per_page;
  $cfg['reuse_query_string']=TRUE;
  $cfg['first_url']=$cfg['base_url'];
  $this->pagination->initialize($cfg);
  $page=$this->input->get('page');
  $offset=((int)max(1,$page)-1)*$per_page;
  $this->render('customer/transactions',array('title'=>'Transactions','transactions'=>$this->Bank_model->transactions_for_user($this->user['id'],$per_page,$filters,$offset),'filters'=>$filters,'pagination'=>$this->pagination->create_links(),'total'=>$total,'per_page'=>$per_page));
 }
 public function statement(){
  $filters=array('search'=>$this->input->get('q',TRUE),'type'=>$this->input->get('type',TRUE));
  $rows=$this->Bank_model->transactions_for_user($this->user['id'],1000,$filters);
  $name=preg_replace('/[^a-z0-9]+/i','-',strtolower($this->user['first_name'].'-'.$this->user['last_name'])).'-statement-'.date('Y-m-d').'.csv';
  $this->output->set_content_type('text/csv')->set_header('Content-Disposition: attachment; filename="'.$name.'"');
  $out=fopen('php://output','w');
  fputcsv($out,array('Date','Reference','Description','Category','Type','Amount','Currency','Balance after','Status'));
  foreach($rows as $t){fputcsv($out,array($t['transaction_date'],$t['reference'],$t['description'],$t['category'],$t['type'],$t['amount'],$t['currency'],$t['balance_after'],$t['status']));}
  fclose($out);
 }
}
