<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Exchange extends MY_Controller {
 public function __construct(){parent::__construct();$this->require_customer();}
 public function index(){
  if($this->input->method()==='post'){
   $this->form_validation->set_rules('from_account_id','From account','required|integer');
   $this->form_validation->set_rules('to_account_id','To account','required|integer');
   $this->form_validation->set_rules('amount','Amount','required|numeric|greater_than[0]');
   if($this->form_validation->run()){
    list($ok,$result)=$this->Bank_model->exchange_convert($this->user['id'],$this->input->post('from_account_id'),$this->input->post('to_account_id'),$this->input->post('amount'));
    if($ok){$this->Bank_model->audit('exchange','Currency exchange '.$result['from_currency'].'→'.$result['to_currency'].' of '.$result['converted'].' '.$result['to_currency'],$this->user['id']);$this->session->set_flashdata('success','Exchanged '.$this->input->post('amount').' '.$result['from_currency'].' to '.$result['converted'].' '.$result['to_currency'].' (rate '.number_format($result['rate'],4).'). Reference: '.$result['reference']);redirect('exchange');}
    $this->session->set_flashdata('error',$result);
   } else $this->session->set_flashdata('error',validation_errors('',' '));
   redirect('exchange');
  }
  $accounts=$this->Bank_model->accounts($this->user['id']);
  $this->render('customer/exchange',array(
   'title'=>'Currency exchange',
   'accounts'=>$accounts,
   'rates'=>$this->Bank_model->exchange_rates(),
   'history'=>$this->rate_history_for_first_pair(),
  ));
 }

 /**
  * AJAX endpoint returning 30-day history for a currency pair.
  * GET /exchange/history?from=USD&to=EUR&days=30  → {labels:[...], rates:[...]}
  */
 public function history(){
  $from=strtoupper(substr((string)$this->input->get('from',TRUE),0,3));
  $to=strtoupper(substr((string)$this->input->get('to',TRUE),0,3));
  $days=min(365,max(7,(int)$this->input->get('days')?:30));
  if(!preg_match('/^[A-Z]{3}$/',$from)||!preg_match('/^[A-Z]{3}$/',$to)){
    return $this->json(array('ok'=>FALSE,'error'=>'Invalid currency pair.'),422);
  }
  if($from===$to)return $this->json(array('ok'=>TRUE,'labels'=>array(),'rates'=>array(),'current'=>1.0));
  $rows=$this->Bank_model->exchange_rate_history($from,$to,$days);
  $current=$this->Bank_model->exchange_rate($from,$to);
  if($current===NULL)$current=end($rows)['rate']??NULL;
  return $this->json(array(
   'ok'=>TRUE,
   'pair'=>$from.'/'.$to,
   'current'=>$current!==NULL?(float)$current:NULL,
   'days'=>$days,
   'labels'=>array_map(function($r){return date('M j',strtotime($r['date']));},$rows),
   'rates'=>array_map(function($r){return (float)$r['rate'];},$rows),
  ));
 }

 /**
  * AJAX quick-convert endpoint used by the public calculator and the
  * in-app converter. Returns the current rate + converted amount.
  */
 public function convert(){
  $from=strtoupper(substr((string)$this->input->get('from',TRUE),0,3));
  $to=strtoupper(substr((string)$this->input->get('to',TRUE),0,3));
  $amount=round((float)$this->input->get('amount'),2);
  if(!preg_match('/^[A-Z]{3}$/',$from)||!preg_match('/^[A-Z]{3}$/',$to)){
    return $this->json(array('ok'=>FALSE,'error'=>'Invalid currency pair.'),422);
  }
  $rate=$this->Bank_model->exchange_rate($from,$to);
  if($rate===NULL||$rate<=0)return $this->json(array('ok'=>FALSE,'error'=>'No rate for '.$from.'/'.$to),404);
  return $this->json(array(
   'ok'=>TRUE,'from'=>$from,'to'=>$to,'rate'=>(float)$rate,
   'amount'=>$amount,'converted'=>round($amount*$rate,2),
  ));
 }

 /** First available currency pair for the initial chart render. */
 private function rate_history_for_first_pair(){
  $rates=$this->Bank_model->exchange_rates();
  if(empty($rates))return array('from'=>'USD','to'=>'EUR','labels'=>array(),'rates'=>array());
  $first=$rates[0];
  $rows=$this->Bank_model->exchange_rate_history($first['from_currency'],$first['to_currency'],30);
  return array(
   'from'=>$first['from_currency'],'to'=>$first['to_currency'],
   'labels'=>array_map(function($r){return date('M j',strtotime($r['date']));},$rows),
   'rates'=>array_map(function($r){return (float)$r['rate'];},$rows),
  );
 }
}
