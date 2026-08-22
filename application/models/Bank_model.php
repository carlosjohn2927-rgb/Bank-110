<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bank_model extends CI_Model
{
    public function authenticate($identity, $password, $role)
    {
        if ($role === 'customer') {
            $this->db->select('u.*')->from('users u')->join('accounts a', 'a.user_id=u.id', 'left');
            $this->db->group_start()->where('u.email', $identity)->or_where('u.username', $identity)->or_where('a.account_number', $identity)->group_end();
            $user = $this->db->where('u.role', $role)->where('u.status', 'active')->limit(1)->get()->row_array();
        } else {
            $this->db->group_start()->where('email', $identity)->or_where('username', $identity)->group_end();
            $user = $this->db->where('role', $role)->where('status', 'active')->get('users')->row_array();
        }
        return ($user && password_verify($password, $user['password_hash'])) ? $user : NULL;
    }

    public function record_login($user_id)
    {
        return $this->db->where('id', $user_id)->update('users', array('last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip' => $this->input->ip_address()));
    }

    public function accounts($user_id)
    {
        return $this->db->where('user_id', $user_id)->order_by('is_primary', 'DESC')->get('accounts')->result_array();
    }

    public function account($id, $user_id = NULL)
    {
        $this->db->where('id', $id);
        if ($user_id !== NULL) $this->db->where('user_id', $user_id);
        return $this->db->get('accounts')->row_array();
    }

    public function update_account_status($id, $user_id, $status)
    {
        $status=in_array($status,array('active','frozen','closed'),TRUE)?$status:'active';
        $a=$this->account((int)$id,(int)$user_id);
        if(!$a || $a['is_primary'])return array(FALSE,'Account unavailable, or a primary account cannot be closed.');
        $this->db->where(array('id'=>(int)$id,'user_id'=>(int)$user_id))->update('accounts',array('status'=>$status,'updated_at'=>date('Y-m-d H:i:s')));
        return array(TRUE,ucfirst($status));
    }

    public function admin_update_account_status($id, $status)
    {
        $status=in_array($status,array('active','frozen','closed'),TRUE)?$status:'active';
        $a=$this->db->where('id',(int)$id)->get('accounts')->row_array();
        if(!$a)return array(FALSE,'Account not found.');
        $this->db->where('id',(int)$id)->update('accounts',array('status'=>$status,'updated_at'=>date('Y-m-d H:i:s')));
        return array(TRUE,ucfirst($status));
    }

    public function total_balance($user_id)
    {
        $row = $this->db->select_sum('available_balance')->where('user_id', $user_id)->where('status', 'active')->get('accounts')->row_array();
        return (float) ($row['available_balance'] ?? 0);
    }

    public function scheduled_transfers($user_id)
    {
        return $this->db->where('user_id',$user_id)->where('status','pending')->order_by('scheduled_for','ASC')->get('transfers')->result_array();
    }

    public function cancel_transfer($id, $user_id)
    {
        $t=$this->db->where(array('id'=>(int)$id,'user_id'=>(int)$user_id,'status'=>'pending'))->get('transfers')->row_array();
        if(!$t)return array(FALSE,'Scheduled transfer not found or already processed.');
        $this->db->trans_start();
        $this->db->where('id',$t['id'])->update('transfers',array('status'=>'cancelled','updated_at'=>date('Y-m-d H:i:s')));
        $this->db->where('transfer_id',$t['id'])->update('transactions',array('status'=>'cancelled'));
        $this->db->trans_complete();
        return $this->db->trans_status()?array(TRUE,$t['reference']):array(FALSE,'Unable to cancel the transfer.');
    }

    public function transfer_usage_today($user_id)
    {
        $row=$this->db->select('COALESCE(SUM(amount),0) total')->where('user_id',$user_id)->where('DATE(created_at)',date('Y-m-d'))->where('status !=','cancelled')->where('status !=','failed')->get('transfers')->row_array();
        return (float)($row['total'] ?? 0);
    }

    public function count_transactions_for_user($user_id, $filters = array())
    {
        $this->db->select('t.id')->from('transactions t')->join('accounts a', 'a.id=t.account_id')->where('a.user_id', $user_id);
        if (!empty($filters['search'])) $this->db->group_start()->like('t.description', $filters['search'])->or_like('t.reference', $filters['search'])->group_end();
        if (!empty($filters['type'])) $this->db->where('t.type', $filters['type']);
        return $this->db->count_all_results();
    }

    public function transaction_for_user($id, $user_id)
    {
        $this->db->select('t.*, a.account_number, a.name account_name, a.type account_type')->from('transactions t')->join('accounts a','a.id=t.account_id')->where('t.id',(int)$id)->where('a.user_id',(int)$user_id);
        $t=$this->db->get()->row_array();
        if($t) $t['transfer']=$this->db->where('id',$t['transfer_id'])->get('transfers')->row_array();
        return $t;
    }

    public function transactions_for_user($user_id, $limit = 50, $filters = array(), $offset = 0)
    {
        $this->db->select('t.*, a.account_number, a.name account_name')->from('transactions t')->join('accounts a', 'a.id=t.account_id')->where('a.user_id', $user_id);
        if (!empty($filters['search'])) $this->db->group_start()->like('t.description', $filters['search'])->or_like('t.reference', $filters['search'])->group_end();
        if (!empty($filters['type'])) $this->db->where('t.type', $filters['type']);
        if ($offset > 0) $this->db->offset((int)$offset);
        return $this->db->order_by('t.created_at', 'DESC')->limit($limit)->get()->result_array();
    }

    public function beneficiaries($user_id)
    {
        return $this->db->where('user_id', $user_id)->order_by('name')->get('beneficiaries')->result_array();
    }

    public function create_beneficiary($user_id, $data)
    {
        $data['user_id'] = $user_id; $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('beneficiaries', $data);
    }

    public function beneficiary($id, $user_id)
    {
        return $this->db->where(array('id'=>(int)$id,'user_id'=>(int)$user_id))->get('beneficiaries')->row_array();
    }

    public function update_beneficiary($id, $user_id, $data)
    {
        if(!$this->beneficiary($id,$user_id))return FALSE;
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->where(array('id'=>(int)$id,'user_id'=>(int)$user_id))->update('beneficiaries', $data);
    }

    public function delete_beneficiary($id, $user_id)
    {
        return $this->db->where(array('id'=>(int)$id,'user_id'=>(int)$user_id))->delete('beneficiaries');
    }

    public function create_transfer($user_id, $data)
    {
        $account = $this->account($data['from_account_id'], $user_id);
        $amount = round((float) $data['amount'], 2);
        if (!$account || $account['status'] !== 'active') return array(FALSE, 'The selected account is unavailable.');
        if ($amount <= 0) return array(FALSE, 'Enter a valid transfer amount.');
        if ($amount > (float) $account['available_balance']) return array(FALSE, 'Insufficient available balance.');
        try{$settings=$this->settings();$daily=(float)($settings['daily_transfer_limit']??25000);}catch(Exception $e){$daily=25000;}
        if ($amount > $daily) return array(FALSE, 'This transfer exceeds your daily limit of '.number_format($daily).'.');
        if (($amount + $this->transfer_usage_today($user_id)) > $daily) return array(FALSE, 'This transfer would exceed your remaining daily limit.');

        // International transfers carry a fee (percentage + optional flat).
        $type=$data['transfer_type'] ?? 'domestic';
        $fee=0;
        if($type==='international'){
            try{$settings=$this->settings();$fee_pct=(float)($settings['international_fee_percent']??1.5);$fee_flat=(float)($settings['international_fee_flat']??0);}catch(Exception $e){$fee_pct=1.5;$fee_flat=0;}
            $fee=round($fee_flat + $amount*$fee_pct/100,2);
        }
        if($amount+$fee>(float)$account['available_balance'])return array(FALSE,'Insufficient available balance to cover the transfer and fees.');

        $reference = 'NW-'.date('ymd').'-'.random_int(100000, 999999);
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        // Money is NOT deducted here. The transfer stays "pending" until it is
        // completed (admin approval or the scheduled-transfer cron), at which
        // point complete_transfer() debits the account. This lets approve/decline
        // genuinely reconcile the balance.
        $routing=$data['recipient_routing'] ?? '';
        if($routing==='' && !empty($data['beneficiary_id'])){
            $b=$this->db->select('routing_code')->where('id',(int)$data['beneficiary_id'])->get('beneficiaries')->row_array();
            if($b)$routing=$b['routing_code'];
        }
        $this->db->insert('transfers', array(
            'reference'=>$reference, 'user_id'=>$user_id, 'from_account_id'=>$account['id'], 'beneficiary_id'=>$data['beneficiary_id'] ?: NULL,
            'recipient_name'=>$data['recipient_name'], 'recipient_account'=>$data['recipient_account'], 'recipient_bank'=>$data['recipient_bank'],
            'recipient_routing'=>$routing ?: NULL, 'transfer_type'=>$type, 'amount'=>$amount, 'currency'=>$account['currency'], 'fee'=>$fee, 'note'=>$data['note'],
            'scheduled_for'=>$data['scheduled_for'], 'status'=>'pending', 'created_at'=>$now, 'updated_at'=>$now
        ));
        $transfer_id = $this->db->insert_id();
        $category=in_array(($data['category'] ?? 'Transfer'), array('Transfer','Bill payment','Payment'), TRUE)?$data['category']:'Transfer';
        $this->db->insert('transactions', array(
            'account_id'=>$account['id'], 'transfer_id'=>$transfer_id, 'reference'=>$reference, 'type'=>'debit', 'category'=>$category,
            'description'=>(($data['category'] ?? 'Transfer')==='Bill payment'?'Bill payment to ':'Transfer to ').$data['recipient_name'], 'amount'=>$amount, 'currency'=>$account['currency'],
            'balance_after'=>(float)$account['available_balance']-$amount, 'status'=>'pending', 'transaction_date'=>$data['scheduled_for'], 'created_at'=>$now
        ));
        $this->db->trans_complete();
        if ($this->db->trans_status() && function_exists('notify_user')) {
            try { notify_user($user_id, 'Transfer '.$reference.' submitted', '<p>Your transfer of '.$this->money_local($amount, $account['currency']).' to '.htmlspecialchars($data['recipient_name']).' has been submitted and is being processed.</p><p>Reference: <b>'.$reference.'</b></p>','','notify_transfers'); } catch (Exception $e) {}
            try { $this->notify_user($user_id,'transfer','Transfer '.$reference.' submitted','To '.$data['recipient_name'].' · '.$this->money_local($amount, $account['currency']),'transactions'); } catch (Exception $e) {}
        }
        return $this->db->trans_status() ? array(TRUE, $reference) : array(FALSE, 'The transfer could not be submitted.');
    }

    private function money_local($amount, $currency = 'USD')
    {
        $symbols = array('USD' => '$', 'EUR' => '€', 'GBP' => '£');
        return ($symbols[$currency] ?? $currency.' ').number_format((float) $amount, 2);
    }

    public function cards($user_id)
    {
        return $this->db->select('c.*, a.account_number, a.available_balance')->from('cards c')->join('accounts a','a.id=c.account_id')->where('c.user_id',$user_id)->get()->result_array();
    }

    public function create_card($user_id, $data)
    {
        $account=$this->account((int)$data['account_id'],$user_id);
        if(!$account || $account['status']!=='active')return array(FALSE,'The selected account is unavailable.');
        $now=date('Y-m-d H:i:s');
        $number=str_pad((string)random_int(0,9999999999999999),16,'0',STR_PAD_LEFT);
        $last_four=substr($number,-4);
        $expiry_month=random_int(1,12);$expiry_year=(int)date('Y')+random_int(3,5);
        $cardholder=$data['cardholder_name'];
        if(!trim((string)$cardholder)){ $u=$this->db->select('first_name,last_name')->where('id',$user_id)->get('users')->row_array(); $cardholder=($u['first_name']??'Card').' '.($u['last_name']??'Holder'); }
        $this->db->trans_start();
        $this->db->insert('cards',array(
            'user_id'=>$user_id,'account_id'=>$account['id'],'cardholder_name'=>trim($cardholder),
            'masked_number'=>'•••• •••• •••• '.$last_four,'last_four'=>$last_four,'expiry_month'=>$expiry_month,'expiry_year'=>$expiry_year,
            'card_type'=>in_array($data['card_type'],array('virtual','physical'),TRUE)?$data['card_type']:'virtual','network'=>'Visa','status'=>'active',
            'is_frozen'=>0,'online_enabled'=>1,'international_enabled'=>0,'daily_limit'=>round((float)($data['daily_limit']?:10000),2),'created_at'=>$now,'updated_at'=>$now
        ));
        $this->db->trans_complete();
        if($this->db->trans_status()){ try{ $this->notify_user($user_id,'card','New card issued','A new card ending in '.$last_four.' has been added to your account.','cards'); }catch(Exception $e){} }
        return $this->db->trans_status()?array(TRUE,'Card ending in '.$last_four.' issued.'):array(FALSE,'Unable to issue the card.');
    }

    public function report_lost_card($id, $user_id)
    {
        $card=$this->db->where(array('id'=>(int)$id,'user_id'=>(int)$user_id))->get('cards')->row_array();
        if(!$card)return array(FALSE,'Card not found.');
        $this->db->trans_start();
        $this->db->where('id',$card['id'])->update('cards',array('status'=>'blocked','is_frozen'=>1,'online_enabled'=>0,'international_enabled'=>0,'updated_at'=>date('Y-m-d H:i:s')));
        $this->db->trans_complete();
        return $this->db->trans_status()?array(TRUE,'Card blocked.'):array(FALSE,'Unable to block the card.');
    }

    public function toggle_card($id, $user_id, $field)
    {
        $allowed = array('is_frozen','online_enabled','international_enabled');
        if (!in_array($field, $allowed, TRUE)) return FALSE;
        $card = $this->db->where(array('id'=>$id,'user_id'=>$user_id))->get('cards')->row_array();
        if (!$card) return FALSE;
        return $this->db->where('id',$id)->update('cards', array($field => $card[$field] ? 0 : 1, 'updated_at'=>date('Y-m-d H:i:s')));
    }

    public function loans($user_id)
    {
        return $this->db->where('user_id',$user_id)->order_by('created_at','DESC')->get('loans')->result_array();
    }

    public function recent_activity($user_id, $limit = 6)
    {
        $items=array();
        // Recent transactions
        foreach($this->transactions_for_user((int)$user_id,5) as $t){
            $items[]=array('icon'=>$t['type']==='credit'?'+':'−','label'=>htmlspecialchars($t['description']),'sub'=>$t['category'],'amount'=>$t['amount'],'currency'=>$t['currency'],'when'=>$t['created_at'],'url'=>'transactions','credit'=>$t['type']==='credit');
        }
        // Recent support tickets
        foreach($this->tickets((int)$user_id) as $t){
            if(count($items)>=($limit*3))break;
            $items[]=array('icon'=>'?','label'=>'Support: '.htmlspecialchars($t['subject']),'sub'=>'Ticket '.$t['reference'],'amount'=>0,'currency'=>'','when'=>$t['updated_at'],'url'=>'support/'.$t['id'],'credit'=>false);
        }
        usort($items,function($a,$b){return strtotime($b['when'])-strtotime($a['when']);});
        return array_slice($items,0,$limit);
    }

    public function tickets($user_id = NULL, $limit = 100, $offset = 0)
    {
        $this->db->select('st.*, u.first_name, u.last_name')->from('support_tickets st')->join('users u','u.id=st.user_id');
        if ($user_id !== NULL) $this->db->where('st.user_id',$user_id);
        if ($offset > 0) $this->db->offset((int)$offset);
        return $this->db->order_by('st.updated_at','DESC')->limit($limit)->get()->result_array();
    }

    public function count_tickets($user_id = NULL)
    {
        $this->db->from('support_tickets st');
        if ($user_id !== NULL) $this->db->where('st.user_id',$user_id);
        return $this->db->count_all_results();
    }

    public function create_ticket($user_id, $data)
    {
        $now = date('Y-m-d H:i:s'); $reference='TKT-'.date('ym').'-'.random_int(10000,99999);
        $this->db->trans_start();
        $this->db->insert('support_tickets', array('reference'=>$reference,'user_id'=>$user_id,'subject'=>$data['subject'],'category'=>$data['category'],'priority'=>'normal','status'=>'open','created_at'=>$now,'updated_at'=>$now));
        $id=$this->db->insert_id();
        $this->db->insert('ticket_messages', array('ticket_id'=>$id,'user_id'=>$user_id,'message'=>$data['message'],'is_staff'=>0,'created_at'=>$now));
        $this->db->trans_complete(); return $reference;
    }

    public function profile($user_id)
    {
        return $this->db->select('u.*, cp.phone, cp.address, cp.city, cp.country, cp.date_of_birth, cp.kyc_status')->from('users u')->join('customer_profiles cp','cp.user_id=u.id','left')->where('u.id',$user_id)->get()->row_array();
    }

    public function update_profile($user_id, $user, $profile)
    {
        $this->db->trans_start();
        $this->db->where('id',$user_id)->update('users',$user);
        if ($this->db->where('user_id',$user_id)->count_all_results('customer_profiles')) $this->db->where('user_id',$user_id)->update('customer_profiles',$profile);
        else { $profile['user_id']=$user_id; $this->db->insert('customer_profiles',$profile); }
        $this->db->trans_complete(); return $this->db->trans_status();
    }

    public function spending_breakdown($user_id,$limit=4)
    {
        $this->db->select('a.user_id, t.category, SUM(t.amount) total')->from('transactions t')->join('accounts a','a.id=t.account_id')->where('a.user_id',$user_id)->where('t.type','debit')->where('t.status','completed');
        $rows=$this->db->group_by('t.category')->order_by('total','DESC')->limit($limit)->get()->result_array();
        return $rows;
    }

    public function monthly_summary($user_id)
    {
        $ym=date('Y-m');
        $rows=$this->db->select('a.user_id, t.type, SUM(t.amount) total')->from('transactions t')->join('accounts a','a.id=t.account_id')->where('a.user_id',$user_id)->where('t.status','completed')->like('t.created_at',$ym.'%')->group_by('t.type')->get()->result_array();
        $income=0;$expenses=0;foreach($rows as $r){if($r['type']==='credit')$income+=(float)$r['total'];else $expenses+=(float)$r['total'];}
        return array('income'=>$income,'expenses'=>$expenses);
    }

    public function update_kyc_status($user_id, $status)
    {
        if (!in_array($status, array('pending','verified','rejected'), TRUE)) return FALSE;
        return $this->db->where('user_id',(int)$user_id)->update('customer_profiles', array('kyc_status'=>$status,'updated_at'=>date('Y-m-d H:i:s')));
    }

    public function request_kyc($user_id)
    {
        return $this->db->where('user_id',(int)$user_id)->update('customer_profiles', array('kyc_status'=>'pending','updated_at'=>date('Y-m-d H:i:s')));
    }

    public function transaction_volume_7d()
    {
        $out=array();
        for($i=6;$i>=0;$i--){
            $day=date('Y-m-d',strtotime("-{$i} days"));
            $count=$this->db->where('DATE(created_at)',$day)->count_all_results('transactions');
            $out[]=array('label'=>date('D',strtotime($day)),'value'=>(int)$count);
        }
        return $out;
    }

    public function account_distribution()
    {
        $rows=$this->db->select('type, COUNT(*) c')->group_by('type')->get('accounts')->result_array();
        $out=array('checking'=>0,'savings'=>0,'investment'=>0);
        foreach($rows as $r){if(isset($out[$r['type']]))$out[$r['type']]=(int)$r['c'];else $out[$r['type']]=(int)$r['c'];}
        $total=array_sum($out)?:1;
        $pct=function($k)use($out,$total){return round($out[$k]/$total*100);};
        return array('checking'=>$pct('checking'),'savings'=>$pct('savings'),'investment'=>$pct('investment'),'total'=>array_sum($out));
    }

    public function admin_metrics()
    {
        $metrics = array();
        $metrics['customers'] = $this->db->where('role','customer')->count_all_results('users');
        $metrics['deposits'] = (float)($this->db->select_sum('available_balance')->get('accounts')->row()->available_balance ?? 0);
        $metrics['transactions_today'] = $this->db->where('DATE(created_at)',date('Y-m-d'))->count_all_results('transactions');
        $metrics['pending'] = $this->db->where('status','pending')->count_all_results('transfers');
        $metrics['scheduled'] = $this->db->where('status','pending')->where('scheduled_for >',date('Y-m-d'))->count_all_results('transfers');
        $metrics['pending_deposits'] = $this->db->where('status','pending')->count_all_results('check_deposits');
        $metrics['cards'] = $this->db->count_all_results('cards');
        $metrics['active_loans'] = $this->db->where('status','active')->count_all_results('loans');
        return $metrics;
    }

    public function count_customers($search=NULL)
    {
        $this->db->from('users u')->where('u.role','customer');
        if($search!==NULL && $search!=='')$this->db->group_start()->like('u.first_name',$search)->or_like('u.last_name',$search)->or_like('u.email',$search)->or_like('u.username',$search)->group_end();
        return $this->db->count_all_results();
    }

    public function customers($limit=100,$search=NULL,$offset=0)
    {
        $this->db->select('u.*, COALESCE(SUM(a.available_balance),0) total_balance, COUNT(a.id) account_count')->from('users u')->join('accounts a','a.user_id=u.id','left')->where('u.role','customer');
        if($search!==NULL && $search!=='')$this->db->group_start()->like('u.first_name',$search)->or_like('u.last_name',$search)->or_like('u.email',$search)->or_like('u.username',$search)->group_end();
        if ($offset > 0) $this->db->offset((int)$offset);
        return $this->db->group_by('u.id')->order_by('u.created_at','DESC')->limit($limit)->get()->result_array();
    }

    public function customer_detail($id)
    {
        $user=$this->profile($id); if(!$user || $user['role']!=='customer') return NULL;
        $user['accounts']=$this->accounts($id); $user['transactions']=$this->transactions_for_user($id,20);
        $user['cards']=$this->cards($id); $user['loans']=$this->loans($id); $user['tickets']=$this->tickets($id);
        return $user;
    }

    public function count_all_transactions($kind=NULL, $search=NULL)
    {
        $this->db->select('t.id')->from('transactions t')->join('accounts a','a.id=t.account_id')->join('users u','u.id=a.user_id');
        if ($kind === 'deposits') $this->db->where('t.type','credit');
        if ($kind === 'transfers') $this->db->where('t.category','Transfer');
        if($search!==NULL && $search!=='')$this->db->group_start()->like('t.reference',$search)->or_like('t.description',$search)->or_like('t.category',$search)->or_like('u.first_name',$search)->or_like('u.last_name',$search)->group_end();
        return $this->db->count_all_results();
    }

    public function all_transactions($kind=NULL, $limit=100, $search=NULL, $offset=0)
    {
        $this->db->select('t.*, a.account_number, u.first_name, u.last_name')->from('transactions t')->join('accounts a','a.id=t.account_id')->join('users u','u.id=a.user_id');
        if ($kind === 'deposits') $this->db->where('t.type','credit');
        if ($kind === 'transfers') $this->db->where('t.category','Transfer');
        if($search!==NULL && $search!=='')$this->db->group_start()->like('t.reference',$search)->or_like('t.description',$search)->or_like('t.category',$search)->or_like('u.first_name',$search)->or_like('u.last_name',$search)->group_end();
        if ($offset > 0) $this->db->offset((int)$offset);
        return $this->db->order_by('t.created_at','DESC')->limit($limit)->get()->result_array();
    }

    public function process_scheduled()
    {
        // Complete pending transfers whose scheduled date has arrived.
        $due=$this->db->select('id')->where('scheduled_for <=',date('Y-m-d'))->where('status','pending')->get('transfers')->result_array();
        $count=0;
        foreach($due as $d){
            list($ok,$m)=$this->complete_transfer($d['id']);
            if($ok)$count++;
        }
        return $count;
    }

    public function complete_transfer($transfer_id)
    {
        $transfer=$this->db->where('id',(int)$transfer_id)->get('transfers')->row_array();
        if(!$transfer || $transfer['status']!=='pending')return array(FALSE,'This transfer is not pending.');
        $account=$this->account($transfer['from_account_id']);
        if(!$account || $account['status']!=='active')return array(FALSE,'The source account is unavailable.');
        $total=$transfer['amount']+$transfer['fee'];
        if($total>(float)$account['available_balance'])return array(FALSE,'Insufficient balance to complete this transfer.');
        $now=date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->where('id',$account['id'])->set('balance','balance-'.$total,FALSE)->set('available_balance','available_balance-'.$total,FALSE)->update('accounts');
        $this->db->where('id',$transfer['id'])->update('transfers',array('status'=>'completed','updated_at'=>$now));
        $this->db->where('transfer_id',$transfer['id'])->update('transactions',array('status'=>'completed','balance_after'=>(float)$account['available_balance']-$total));
        $this->db->trans_complete();
        if($this->db->trans_status() && function_exists('notify_user')){
            try{notify_user((int)$transfer['user_id'],'Transfer '.$transfer['reference'].' completed','<p>Your transfer of '.$this->money_local($transfer['amount'],$transfer['currency']).' to '.htmlspecialchars($transfer['recipient_name']).' has been completed.</p>','','notify_transfers');}catch(Exception $e){}
            try { $this->notify_user((int)$transfer['user_id'],'transfer','Transfer '.$transfer['reference'].' completed','To '.$transfer['recipient_name'].' · '.$this->money_local($transfer['amount'],$transfer['currency']),'transactions'); } catch (Exception $e) {}
        }
        return $this->db->trans_status()?array(TRUE,$transfer['reference']):array(FALSE,'The transfer could not be completed.');
    }

    public function decline_transfer($transfer_id,$status='failed')
    {
        $status=in_array($status,array('failed','cancelled'),TRUE)?$status:'failed';
        $transfer=$this->db->where('id',(int)$transfer_id)->get('transfers')->row_array();
        if(!$transfer || $transfer['status']!=='pending')return FALSE;
        $this->db->trans_start();
        $this->db->where('id',$transfer['id'])->update('transfers',array('status'=>$status,'updated_at'=>date('Y-m-d H:i:s')));
        $this->db->where('transfer_id',$transfer['id'])->update('transactions',array('status'=>$status));
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function transaction($id)
    {
        $this->db->select('t.*, a.account_number, a.name account_name, u.first_name, u.last_name, u.email')->from('transactions t')->join('accounts a','a.id=t.account_id')->join('users u','u.id=a.user_id')->where('t.id',(int)$id);
        $t=$this->db->get()->row_array();
        if($t && $t['transfer_id']) $t['transfer']=$this->db->where('id',$t['transfer_id'])->get('transfers')->row_array();
        return $t;
    }

    public function set_transaction_status($id, $status)
    {
        if (!in_array($status,array('completed','failed','cancelled'),TRUE)) return FALSE;
        $t=$this->db->where('id',(int)$id)->get('transactions')->row_array();
        if(!$t)return FALSE;
        // Transfer-linked transactions reconcile the actual money movement.
        if(!empty($t['transfer_id'])){
            if($status==='completed'){list($ok,$m)=$this->complete_transfer($t['transfer_id']);return $ok;}
            if($status==='failed'||$status==='cancelled'){return $this->decline_transfer($t['transfer_id'],$status);}
        }
        return $this->db->where('id',$id)->update('transactions',array('status'=>$status));
    }

    public function update_customer_status($id, $status)
    {
        if (!in_array($status, array('active','pending','suspended','closed'), TRUE)) return FALSE;
        return $this->db->where(array('id'=>(int)$id,'role'=>'customer'))->update('users', array('status'=>$status,'updated_at'=>date('Y-m-d H:i:s')));
    }

    public function adjust_balance($account_id, $customer_id, $amount, $direction, $note)
    {
        $account=$this->account($account_id,$customer_id); $amount=round((float)$amount,2);
        if(!$account || $amount<=0 || !in_array($direction,array('credit','debit'),TRUE)) return array(FALSE,'Invalid adjustment request.');
        if($direction==='debit' && $amount>(float)$account['available_balance']) return array(FALSE,'The debit exceeds the available balance.');
        $delta=$direction==='credit'?$amount:-$amount; $reference='ADJ-'.date('ymd').'-'.random_int(10000,99999); $now=date('Y-m-d H:i:s');
        $this->db->trans_start();
        $operator=$delta>=0?'+':'-'; $absolute=abs($delta);
        $this->db->where('id',$account_id)->set('balance','balance'.$operator.$absolute,FALSE)->set('available_balance','available_balance'.$operator.$absolute,FALSE)->update('accounts');
        $this->db->insert('transactions',array('account_id'=>$account_id,'reference'=>$reference,'type'=>$direction,'category'=>'Admin adjustment','description'=>$note ?: ucfirst($direction).' adjustment','amount'=>$amount,'currency'=>$account['currency'],'balance_after'=>(float)$account['available_balance']+$delta,'status'=>'completed','transaction_date'=>date('Y-m-d'),'created_at'=>$now));
        $this->db->trans_complete(); return $this->db->trans_status()?array(TRUE,$reference):array(FALSE,'Adjustment failed.');
    }

    public function ticket($id)
    {
        $ticket=$this->db->select('st.*,u.first_name,u.last_name,u.email')->from('support_tickets st')->join('users u','u.id=st.user_id')->where('st.id',(int)$id)->get()->row_array();
        if($ticket)$ticket['messages']=$this->db->select('tm.*,u.first_name,u.last_name')->from('ticket_messages tm')->join('users u','u.id=tm.user_id')->where('tm.ticket_id',(int)$id)->order_by('tm.created_at')->get()->result_array();
        return $ticket;
    }

    public function ticket_for_user($id, $user_id)
    {
        $ticket=$this->db->select('st.*,u.first_name,u.last_name,u.email')->from('support_tickets st')->join('users u','u.id=st.user_id')->where(array('st.id'=>(int)$id,'st.user_id'=>(int)$user_id))->get()->row_array();
        if($ticket)$ticket['messages']=$this->db->select('tm.*,u.first_name,u.last_name')->from('ticket_messages tm')->join('users u','u.id=tm.user_id')->where('tm.ticket_id',(int)$id)->order_by('tm.created_at')->get()->result_array();
        return $ticket;
    }

    public function customer_reply_ticket($id, $user_id, $message)
    {
        $t=$this->ticket_for_user((int)$id,(int)$user_id);
        if(!$t || in_array($t['status'],array('resolved','closed'),TRUE))return array(FALSE,'This ticket is closed.');
        $now=date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->insert('ticket_messages',array('ticket_id'=>(int)$id,'user_id'=>$user_id,'message'=>$message,'is_staff'=>0,'created_at'=>$now));
        $this->db->where('id',(int)$id)->update('support_tickets',array('status'=>'open','updated_at'=>$now));
        $this->db->trans_complete();
        return array($this->db->trans_status(),$t['reference']);
    }

    public function reply_ticket($id,$admin_id,$message,$status)
    {
        if(!in_array($status,array('open','in_progress','resolved','closed'),TRUE))$status='in_progress'; $now=date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->insert('ticket_messages',array('ticket_id'=>$id,'user_id'=>$admin_id,'message'=>$message,'is_staff'=>1,'created_at'=>$now));
        $this->db->where('id',$id)->update('support_tickets',array('status'=>$status,'assigned_to'=>$admin_id,'updated_at'=>$now));
        $this->db->trans_complete();
        if ($this->db->trans_status() && function_exists('notify_user')) {
            try {
                $t=$this->ticket($id);
                if(!empty($t['email'])) notify_user((int)$t['user_id'], 'Update on support request '.$t['reference'], '<p>NorthWest support has replied to your request <b>'.$t['reference'].'</b>.</p><p><i>'.htmlspecialchars(substr($message,0,200)).'</i></p>','','notify_tickets');
                try { $this->notify_user((int)$t['user_id'],'ticket','Support reply on '.$t['reference'],'NorthWest support replied to your request','support/'.$id); } catch (Exception $e) {}
            } catch (Exception $e) {}
        }
        return $this->db->trans_status();
    }

    public function loan_schedule($loan)
    {
        $schedule=array();
        $principal=(float)$loan['principal'];
        $outstanding=(float)$loan['outstanding_balance'];
        $monthly=(float)$loan['monthly_payment'];
        $rate=(float)$loan['interest_rate']/100/12;
        $remaining=(int)$loan['payments_remaining'];
        $start=date('Y-m-01',strtotime($loan['next_payment_date']));
        $made=(int)$loan['term_months']-(int)$loan['payments_remaining'];
        for($i=0;$i<$remaining && $outstanding>0.005;$i++){
            $interest=$outstanding*$rate;
            $payment=min($monthly,$outstanding+$interest);
            $to_principal=max(0,$payment-$interest);
            $outstanding=max(0,$outstanding-$to_principal);
            $due=date('Y-m-d',strtotime($start.' +'.($made+$i).' months'));
            $schedule[]=array('due'=>$due,'payment'=>round($payment,2),'interest'=>round($interest,2),'principal'=>round($to_principal,2),'balance'=>round($outstanding,2));
        }
        return $schedule;
    }

    public function pay_loan($loan_id, $user_id, $account_id)
    {
        $loan=$this->db->where(array('id'=>(int)$loan_id,'user_id'=>$user_id))->get('loans')->row_array();
        if(!$loan || $loan['status']!=='active')return array(FALSE,'The loan is not available for payment.');
        $account=$this->account((int)$account_id,$user_id);
        if(!$account || $account['status']!=='active')return array(FALSE,'The selected account is unavailable.');
        $amount=min((float)$loan['monthly_payment'],(float)$loan['outstanding_balance']);
        if($amount<=0)return array(FALSE,'The loan is already fully repaid.');
        if($amount>(float)$account['available_balance'])return array(FALSE,'Insufficient available balance for this payment.');
        $now=date('Y-m-d H:i:s');$reference='LN-PAY-'.date('ymd').'-'.random_int(10000,99999);
        $new_balance=max(0,round((float)$loan['outstanding_balance']-$amount,2));
        $remaining=max(0,(int)$loan['payments_remaining']-1);
        $status=$new_balance<=0?'paid':'active';
        $this->db->trans_start();
        $this->db->where('id',$account['id'])->set('balance','balance-'.$amount,FALSE)->set('available_balance','available_balance-'.$amount,FALSE)->update('accounts');
        $this->db->where('id',$loan['id'])->update('loans',array('outstanding_balance'=>$new_balance,'payments_remaining'=>$remaining,'status'=>$status,'updated_at'=>$now));
        $this->db->insert('transactions',array('account_id'=>$account['id'],'reference'=>$reference,'type'=>'debit','category'=>'Loan payment','description'=>'Loan payment '.$loan['reference'],'amount'=>$amount,'currency'=>$account['currency'],'balance_after'=>(float)$account['available_balance']-$amount,'status'=>'completed','transaction_date'=>date('Y-m-d'),'created_at'=>$now));
        $this->db->trans_complete();
        if($this->db->trans_status()){ try{ $this->notify_user($user_id,'loan',$status==='paid'?'Loan paid off':'Loan payment received',$status==='paid'?'Congratulations — loan '.$loan['reference'].' is fully repaid.':'Your payment of '.$this->money_local($amount,$account['currency']).' was received.','loans'); }catch(Exception $e){} }
        return $this->db->trans_status()?array(TRUE,$reference):array(FALSE,'The loan payment could not be processed.');
    }

    public function create_loan($user_id, $data)
    {
        $amount=round((float)$data['amount'],2);
        $term=max(6,min(120,(int)$data['term_months']));
        if($amount<100)return array(FALSE,'Minimum loan amount is 100.');
        if($amount>250000)return array(FALSE,'Maximum loan amount is 250,000.');
        $rate=round((float)($data['interest_rate'] ?: 6.25),3);
        // Monthly payment via standard amortization
        $r=$rate/100/12;
        $payment=$r==0?$amount/$term:$amount*$r*pow(1+$r,$term)/(pow(1+$r,$term)-1);
        $payment=round($payment,2);
        $now=date('Y-m-d H:i:s');$reference='NW-LN-'.random_int(100000,999999);
        $this->db->trans_start();
        $this->db->insert('loans',array(
            'user_id'=>$user_id,'reference'=>$reference,'type'=>trim($data['type'] ?: 'Personal loan'),'principal'=>$amount,'outstanding_balance'=>$amount,
            'interest_rate'=>$rate,'monthly_payment'=>$payment,'next_payment_date'=>date('Y-m-d',strtotime('+1 month')),'term_months'=>$term,'payments_remaining'=>$term,
            'status'=>'active','created_at'=>$now,'updated_at'=>$now
        ));
        $this->db->trans_complete();
        if($this->db->trans_status()){ try{ $this->notify_user($user_id,'loan','Loan application approved','Your loan '.$reference.' has been opened. Monthly payment '.$this->money_local($payment,'USD').'.','loans'); }catch(Exception $e){} }
        return $this->db->trans_status()?array(TRUE,$reference):array(FALSE,'Unable to process the loan application.');
    }

    public function all_cards() { return $this->db->select('c.*,u.first_name,u.last_name,a.account_number')->from('cards c')->join('users u','u.id=c.user_id')->join('accounts a','a.id=c.account_id')->order_by('c.created_at','DESC')->get()->result_array(); }

    public function admin_create_card($data)
    {
        $account=$this->db->select('a.*,u.first_name,u.last_name')->from('accounts a')->join('users u','u.id=a.user_id')->where('a.id',(int)$data['account_id'])->get()->row_array();
        if(!$account || $account['status']!=='active')return array(FALSE,'The selected account is unavailable.');
        $now=date('Y-m-d H:i:s');$number=str_pad((string)random_int(0,9999999999999999),16,'0',STR_PAD_LEFT);$last_four=substr($number,-4);
        $this->db->trans_start();
        $this->db->insert('cards',array('user_id'=>$account['user_id'],'account_id'=>$account['id'],'cardholder_name'=>$account['first_name'].' '.$account['last_name'],'masked_number'=>'•••• •••• •••• '.$last_four,'last_four'=>$last_four,'expiry_month'=>random_int(1,12),'expiry_year'=>(int)date('Y')+random_int(3,5),'card_type'=>in_array($data['card_type'],array('virtual','physical'),TRUE)?$data['card_type']:'physical','network'=>'Visa','status'=>'active','is_frozen'=>0,'online_enabled'=>1,'international_enabled'=>1,'daily_limit'=>round((float)($data['daily_limit']?:10000),2),'created_at'=>$now,'updated_at'=>$now));
        $this->db->trans_complete();
        if($this->db->trans_status()){ try{ $this->notify_user($account['user_id'],'card','New card issued','A new card ending in '.$last_four.' has been added to your account.','cards'); }catch(Exception $e){} }
        return $this->db->trans_status()?array(TRUE,'Card ending in '.$last_four.' issued.'):array(FALSE,'Unable to issue the card.');
    }

    public function customers_with_accounts(){return $this->db->select('a.id account_id,u.id,a.name account_name,a.account_number,u.first_name,u.last_name')->from('accounts a')->join('users u','u.id=a.user_id')->where('u.role','customer')->where('a.status','active')->order_by('u.first_name')->get()->result_array();}
    public function all_loans() { return $this->db->select('l.*,u.first_name,u.last_name')->from('loans l')->join('users u','u.id=l.user_id')->order_by('l.created_at','DESC')->get()->result_array(); }

    public function create_account($user_id, $data)
    {
        $now=date('Y-m-d H:i:s');
        $type=in_array($data['type'],array('checking','savings','investment'),TRUE)?$data['type']:'checking';
        $currency=strtoupper(trim($data['currency'] ?: 'USD')); if(!in_array($currency,array('USD','EUR','GBP'),TRUE))$currency='USD';
        $account_number='NW'.date('ym').str_pad(random_int(0,9999999),7,'0',STR_PAD_LEFT);
        $attempts=0;
        while($this->db->where('account_number',$account_number)->count_all_results('accounts') && $attempts<5){$account_number='NW'.date('ym').str_pad(random_int(0,9999999),7,'0',STR_PAD_LEFT);$attempts++;}
        $opening=round((float)($data['opening_balance'] ?? 0),2);
        $this->db->trans_start();
        $this->db->insert('accounts',array(
            'user_id'=>$user_id,'account_number'=>$account_number,'name'=>$data['name'] ?: 'NorthWest '.ucfirst($type),'type'=>$type,'currency'=>$currency,
            'balance'=>$opening,'available_balance'=>$opening,'status'=>'active','is_primary'=>0,'created_at'=>$now,'updated_at'=>$now
        ));
        $account_id=$this->db->insert_id();
        if($opening>0){
            $reference='DEP-'.date('ymd').'-'.random_int(10000,99999);
            $this->db->insert('transactions',array('account_id'=>$account_id,'reference'=>$reference,'type'=>'credit','category'=>'Opening deposit','description'=>'Opening deposit for new account','amount'=>$opening,'currency'=>$currency,'balance_after'=>$opening,'status'=>'completed','transaction_date'=>date('Y-m-d'),'created_at'=>$now));
        }
        $this->db->trans_complete();
        return $this->db->trans_status()?$account_id:FALSE;
    }

    public function admin_issue_loan($data)
    {
        $user=$this->db->select('id')->where('id',(int)$data['user_id'])->where('role','customer')->get('users')->row_array();
        if(!$user)return array(FALSE,'Select a valid customer.');
        $amount=round((float)$data['amount'],2);$term=max(6,min(120,(int)$data['term_months']));
        if($amount<=0)return array(FALSE,'Enter a valid amount.');
        $rate=round((float)($data['interest_rate'] ?: 6.25),3);
        $r=$rate/100/12;$payment=$r==0?$amount/$term:$amount*$r*pow(1+$r,$term)/(pow(1+$r,$term)-1);$payment=round($payment,2);
        $now=date('Y-m-d H:i:s');$reference='NW-LN-'.random_int(100000,999999);
        $this->db->trans_start();
        $this->db->insert('loans',array('user_id'=>$user['id'],'reference'=>$reference,'type'=>trim($data['type'] ?: 'Personal loan'),'principal'=>$amount,'outstanding_balance'=>$amount,'interest_rate'=>$rate,'monthly_payment'=>$payment,'next_payment_date'=>date('Y-m-d',strtotime('+1 month')),'term_months'=>$term,'payments_remaining'=>$term,'status'=>'active','created_at'=>$now,'updated_at'=>$now));
        $this->db->trans_complete();
        if($this->db->trans_status()){ try{ $this->notify_user($user['id'],'loan','Loan issued','A loan '.$reference.' has been opened on your account. Monthly payment '.$this->money_local($payment,'USD').'.','loans'); }catch(Exception $e){} }
        return $this->db->trans_status()?array(TRUE,$reference):array(FALSE,'Unable to create the loan.');
    }

    public function admins()
    {
        return $this->db->select('id,username,email,first_name,last_name,status,last_login_at,created_at')->where('role','admin')->order_by('created_at','DESC')->get('users')->result_array();
    }

    public function create_admin($data)
    {
        $now=date('Y-m-d H:i:s');
        return $this->db->insert('users',array(
            'username'=>$data['username'],'email'=>$data['email'],'password_hash'=>password_hash($data['password'],PASSWORD_DEFAULT),
            'first_name'=>$data['first_name'],'last_name'=>$data['last_name'],'role'=>'admin','status'=>'active','created_at'=>$now,'updated_at'=>$now
        ));
    }

    public function register_customer($data)
    {
        $now=date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->insert('users',array('username'=>$data['username'],'email'=>$data['email'],'password_hash'=>password_hash($data['password'],PASSWORD_DEFAULT),'first_name'=>$data['first_name'],'last_name'=>$data['last_name'],'role'=>'customer','status'=>'pending','created_at'=>$now,'updated_at'=>$now));
        $uid=$this->db->insert_id();
        $this->db->insert('customer_profiles',array('user_id'=>$uid,'phone'=>$data['phone'],'country'=>$data['country'],'kyc_status'=>'pending','created_at'=>$now,'updated_at'=>$now));
        $account_number='NW'.date('ym').str_pad($uid,7,'0',STR_PAD_LEFT);
        $this->db->insert('accounts',array('user_id'=>$uid,'account_number'=>$account_number,'name'=>'NorthWest Select','type'=>'checking','currency'=>'USD','balance'=>0,'available_balance'=>0,'status'=>'active','is_primary'=>1,'created_at'=>$now,'updated_at'=>$now));
        $this->db->trans_complete();
        return $this->db->trans_status() ? $uid : FALSE;
    }

    public function create_customer($data)
    {
        $now=date('Y-m-d H:i:s'); $this->db->trans_start();
        $this->db->insert('users',array('username'=>$data['username'],'email'=>$data['email'],'password_hash'=>password_hash($data['password'],PASSWORD_DEFAULT),'first_name'=>$data['first_name'],'last_name'=>$data['last_name'],'role'=>'customer','status'=>'active','created_at'=>$now,'updated_at'=>$now));
        $uid=$this->db->insert_id();
        $this->db->insert('customer_profiles',array('user_id'=>$uid,'phone'=>$data['phone'],'country'=>$data['country'],'kyc_status'=>'pending','created_at'=>$now,'updated_at'=>$now));
        $this->db->insert('accounts',array('user_id'=>$uid,'account_number'=>'NW'.date('ym').str_pad($uid,7,'0',STR_PAD_LEFT),'name'=>'NorthWest Select','type'=>'checking','currency'=>'USD','balance'=>$data['opening_balance'],'available_balance'=>$data['opening_balance'],'status'=>'active','is_primary'=>1,'created_at'=>$now,'updated_at'=>$now));
        $this->db->trans_complete(); return $this->db->trans_status() ? $uid : FALSE;
    }

    public function create_password_reset($email)
    {
        $user=$this->db->select('id')->where('email',$email)->where('role','customer')->get('users')->row_array();
        if(!$user)return FALSE;
        $token=bin2hex(random_bytes(32));
        $this->db->where('user_id',$user['id'])->delete('password_resets');
        $this->db->insert('password_resets',array('user_id'=>$user['id'],'token'=>$token,'expires_at'=>date('Y-m-d H:i:s',strtotime('+30 minutes')),'used'=>0,'created_at'=>date('Y-m-d H:i:s')));
        return $token;
    }

    public function get_password_reset($token)
    {
        $row=$this->db->where('token',$token)->where('used',0)->where('expires_at >',date('Y-m-d H:i:s'))->get('password_resets')->row_array();
        return $row?:NULL;
    }

    public function complete_password_reset($token,$password)
    {
        $row=$this->get_password_reset($token);
        if(!$row)return FALSE;
        $this->db->trans_start();
        $this->db->where('id',$row['user_id'])->update('users',array('password_hash'=>password_hash($password,PASSWORD_DEFAULT),'updated_at'=>date('Y-m-d H:i:s')));
        $this->db->where('id',$row['id'])->update('password_resets',array('used'=>1));
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function preferences($user_id)
    {
        $rows=$this->db->select('pref_key,pref_value')->where('user_id',$user_id)->get('user_preferences')->result_array();
        $out=array();foreach($rows as $r)$out[$r['pref_key']]=$r['pref_value'];return $out;
    }

    public function set_preference($user_id,$key,$value)
    {
        if($value===''||$value===NULL){return $this->db->where(array('user_id'=>$user_id,'pref_key'=>$key))->delete('user_preferences');}
        return $this->db->replace('user_preferences',array('user_id'=>$user_id,'pref_key'=>$key,'pref_value'=>$value,'updated_at'=>date('Y-m-d H:i:s')));
    }

    public function set_twofa($user_id,$enabled)
    {
        return $this->db->where('id',$user_id)->update('users',array('twofa_enabled'=>$enabled?'1':'0','updated_at'=>date('Y-m-d H:i:s')));
    }

    /* -------------------- TOTP authenticator app -------------------- */

    /** Begin TOTP enrollment: generate + store an unconfirmed secret and return it. */
    public function totp_begin_enrollment($user_id)
    {
        $this->load->library('Totp');
        $secret = Totp::generate_secret();
        $this->db->where('id', $user_id)->update('users', array(
            'totp_secret' => $secret, 'totp_confirmed' => 0, 'updated_at' => date('Y-m-d H:i:s'),
        ));
        return $secret;
    }

    /** Confirm a TOTP code during enrollment; returns [bool, string]. */
    public function totp_confirm_enrollment($user_id, $code)
    {
        $user = $this->db->select('id,totp_secret,totp_confirmed')->where('id', $user_id)->get('users')->row_array();
        if (!$user || empty($user['totp_secret'])) return array(FALSE, 'No enrollment in progress.');
        if (!empty($user['totp_confirmed'])) return array(FALSE, 'Authenticator app is already set up.');
        $this->load->library('Totp');
        if (!Totp::verify($code, $user['totp_secret'])) return array(FALSE, 'That code is incorrect or has expired.');
        $backup = Totp::generate_backup_codes(8);
        $this->db->trans_start();
        $this->db->where('id', $user_id)->update('users', array(
            'totp_secret' => $user['totp_secret'], 'totp_confirmed' => 1, 'twofa_enabled' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        $this->save_backup_codes($user_id, $backup);
        $this->db->trans_complete();
        return $this->db->trans_status() ? array(TRUE, $backup) : array(FALSE, 'Unable to complete enrollment.');
    }

    /** Disable TOTP and clear secret + backup codes. */
    public function totp_disable($user_id)
    {
        return $this->db->where('id', $user_id)->update('users', array(
            'totp_secret' => NULL, 'totp_confirmed' => 0, 'backup_codes_hash' => NULL,
            'twofa_enabled' => 0, 'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Verify a TOTP code OR a backup code during sign-in.
     * Returns 'totp', 'backup', or FALSE.
     */
    public function totp_verify($user, $code)
    {
        $code = trim((string)$code);
        if (empty($user['totp_secret'])) return FALSE;
        $this->load->library('Totp');
        // A 6-digit code is a TOTP attempt; otherwise treat it as a backup code.
        if (preg_match('/^\d{6}$/', $code)) {
            return Totp::verify($code, $user['totp_secret']) ? 'totp' : FALSE;
        }
        $normalized = strtoupper(preg_replace('/\s+/', '', $code));
        if (!empty($user['backup_codes_hash'])) {
            $codes = explode(',', $normalized); // accept a single code too
            $stored = array_filter(array_map('trim', explode(',', $this->decrypt_backup_codes($user))));
            if (empty($stored)) {
                // Fallback: hashed storage (one-time-use mode).
                if (Totp::verify_backup_code($normalized, $user['backup_codes_hash'])) {
                    $this->db->where('id', $user['id'])->update('users', array('backup_codes_hash' => NULL, 'updated_at' => date('Y-m-d H:i:s')));
                    return 'backup';
                }
            } else {
                // Reversible storage so a single code can be consumed while the rest remain.
                foreach ($stored as $i => $known) {
                    if (hash_equals(strtoupper($known), $normalized)) {
                        unset($stored[$i]);
                        $this->save_backup_codes((int)$user['id'], array_values($stored));
                        return 'backup';
                    }
                }
            }
        }
        return FALSE;
    }

    /**
     * Store backup codes reversible-but-encrypted with the app's encryption key.
     * Encryption uses the AEAD-validated CI encryption class when an encryption
     * key is configured; otherwise falls back to a keyed HMAC scheme so codes
     * remain one-time-use without ever being stored in plaintext.
     */
    private function save_backup_codes($user_id, array $codes)
    {
        $payload = json_encode(array_values($codes));
        $key = (string) getenv('VP_ENCRYPTION_KEY') ?: (string) config_item('encryption_key');
        if ($key !== '' && function_exists('openssl_encrypt')) {
            $iv = random_bytes(16);
            $cipher = openssl_encrypt($payload, 'AES-256-CBC', hash('sha256', $key, TRUE), OPENSSL_RAW_DATA, $iv);
            $stored = 'v2:'.base64_encode($iv).'$'.base64_encode($cipher);
        } else {
            // Deterministic obfuscation (NOT strong — set VP_ENCRYPTION_KEY in production).
            $stored = 'v1:'.base64_encode(str_rot13($payload));
        }
        $this->db->where('id', $user_id)->update('users', array('backup_codes_hash' => $stored, 'updated_at' => date('Y-m-d H:i:s')));
    }

    private function decrypt_backup_codes($user)
    {
        $raw = (string) ($user['backup_codes_hash'] ?? '');
        if ($raw === '' || strpos($raw, 'v1:') !== 0) return '';
        if (strpos($raw, 'v2:') === 0) {
            $key = (string) getenv('VP_ENCRYPTION_KEY') ?: (string) config_item('encryption_key');
            if ($key === '' || !function_exists('openssl_decrypt')) return '';
            list(, $rest) = explode(':', $raw, 2);
            list($iv64, $ct64) = explode('$', $rest, 2);
            $iv = base64_decode($iv64); $ct = base64_decode($ct64);
            $plain = openssl_decrypt($ct, 'AES-256-CBC', hash('sha256', $key, TRUE), OPENSSL_RAW_DATA, $iv);
            $arr = json_decode($plain, TRUE);
            return is_array($arr) ? implode(',', $arr) : '';
        }
        // v1 obfuscation
        list(, $b64) = explode(':', $raw, 2);
        $arr = json_decode(str_rot13(base64_decode($b64)), TRUE);
        return is_array($arr) ? implode(',', $arr) : '';
    }

    public function change_password($user_id,$current,$new)
    {
        $user=$this->db->select('password_hash')->where('id',$user_id)->get('users')->row_array();
        if(!$user || !password_verify($current,$user['password_hash']))return FALSE;
        return $this->db->where('id',$user_id)->update('users',array('password_hash'=>password_hash($new,PASSWORD_DEFAULT),'updated_at'=>date('Y-m-d H:i:s')));
    }

    public function audit_log($limit=200,$search=NULL)
    {
        $this->db->select('l.*, u.first_name, u.last_name, u.email')->from('audit_logs l')->join('users u','u.id=l.user_id','left');
        if($search!==NULL && $search!=='')$this->db->group_start()->like('l.action',$search)->or_like('l.description',$search)->or_like('u.email',$search)->group_end();
        return $this->db->order_by('l.id','DESC')->limit($limit)->get()->result_array();
    }

    public function add_notification($user_id, $type, $title, $body=NULL, $link=NULL)
    {
        return $this->db->insert('user_notifications', array(
            'user_id'=>(int)$user_id,'type'=>$type,'title'=>$title,'body'=>$body,'link'=>$link,'is_read'=>0,'created_at'=>date('Y-m-d H:i:s')
        ));
    }

    public function notifications($user_id, $limit=20, $unread_only=FALSE, $filter=NULL, $offset=0)
    {
        $this->db->where('user_id',(int)$user_id);
        if($unread_only)$this->db->where('is_read',0);
        if($filter && in_array($filter,array('unread','transfer','ticket','security','loan','card','deposit','general'),TRUE)){
            if($filter==='unread')$this->db->where('is_read',0);
            else $this->db->where('type',$filter);
        }
        if($offset>0)$this->db->offset((int)$offset);
        return $this->db->order_by('id','DESC')->limit($limit)->get('user_notifications')->result_array();
    }

    public function count_notifications($user_id, $filter=NULL)
    {
        $this->db->where('user_id',(int)$user_id);
        if($filter && in_array($filter,array('unread','transfer','ticket','security','loan','card','deposit','general'),TRUE)){
            if($filter==='unread')$this->db->where('is_read',0);
            else $this->db->where('type',$filter);
        }
        return $this->db->count_all_results('user_notifications');
    }

    public function unread_notification_count($user_id)
    {
        return $this->db->where('user_id',(int)$user_id)->where('is_read',0)->count_all_results('user_notifications');
    }

    /** Unread count grouped by type (for filter badges). */
    public function unread_counts_by_type($user_id)
    {
        $rows=$this->db->select('type, COUNT(*) c')->where('user_id',(int)$user_id)->where('is_read',0)->group_by('type')->get('user_notifications')->result_array();
        $out=array(); foreach($rows as $r)$out[$r['type']]=(int)$r['c'];
        return $out;
    }

    public function notification($id, $user_id)
    {
        return $this->db->where(array('id'=>(int)$id,'user_id'=>(int)$user_id))->get('user_notifications')->row_array();
    }

    public function mark_notification_read($id, $user_id)
    {
        return $this->db->where(array('id'=>(int)$id,'user_id'=>(int)$user_id,'is_read'=>0))->update('user_notifications', array('is_read'=>1));
    }

    public function mark_notifications_read($user_id)
    {
        return $this->db->where('user_id',(int)$user_id)->where('is_read',0)->update('user_notifications', array('is_read'=>1));
    }

    public function delete_notification($id, $user_id)
    {
        return $this->db->where(array('id'=>(int)$id,'user_id'=>(int)$user_id))->delete('user_notifications');
    }

    public function delete_all_notifications($user_id)
    {
        return $this->db->where('user_id',(int)$user_id)->delete('user_notifications');
    }

    /**
     * Whether a notification TYPE is enabled for this user according to their
     * stored preferences. Security notifications are always delivered.
     */
    public function notification_enabled($user_id, $type)
    {
        if($type==='security') return TRUE; // security alerts can't be disabled
        $map=array(
            'transfer'=>'notify_transfers','deposit'=>'notify_transfers',
            'ticket'=>'notify_tickets','loan'=>'notify_loans','card'=>'notify_cards',
            'general'=>'notify_general',
        );
        $key=$map[$type] ?? NULL;
        if(!$key) return TRUE;
        $prefs=$this->preferences($user_id);
        // Default ON unless explicitly disabled.
        return ($prefs[$key] ?? '1') !== '0';
    }

    /**
     * Create a notification — but only if the user's preferences allow that
     * type. Security notifications are always stored.
     */
    public function OLDNAME
    {
        if(!$this->notification_enabled($user_id, $type)) return FALSE;
        return $this->add_notification($user_id, $type, $title, $body, $link);
    }

    public function login_attempts($key, $window_seconds=900, $max=5)
    {
        $cutoff=time()-$window_seconds;
        // trim old rows
        $this->db->where('created_at <',date('Y-m-d H:i:s',$cutoff))->delete('login_attempts');
        return $this->db->where('attempt_key',$key)->where('created_at >=',date('Y-m-d H:i:s',$cutoff))->count_all_results('login_attempts');
    }

    public function record_login_attempt($key,$success=FALSE)
    {
        return $this->db->insert('login_attempts',array('attempt_key'=>$key,'success'=>$success?'1':'0','ip_address'=>$this->input->ip_address(),'created_at'=>date('Y-m-d H:i:s')));
    }

    public function clear_login_attempts($key)
    {
        return $this->db->where('attempt_key',$key)->delete('login_attempts');
    }

    public function audit($action,$description,$user_id=NULL)
    {
        return $this->db->insert('audit_logs',array('user_id'=>$user_id,'action'=>$action,'description'=>$description,'ip_address'=>$this->input->ip_address(),'user_agent'=>substr($this->input->user_agent(),0,255),'created_at'=>date('Y-m-d H:i:s')));
    }

    public function settings()
    {
        $rows=$this->db->get('settings')->result_array(); $out=array(); foreach($rows as $r)$out[$r['setting_key']]=$r['setting_value']; return $out;
    }

    /* ---- Multi-currency exchange ---- */

    public function exchange_rates()
    {
        $rows=$this->db->order_by('from_currency','ASC')->get('exchange_rates')->result_array();
        return $rows;
    }

    public function exchange_rate($from,$to)
    {
        if($from===$to)return 1.0;
        $r=$this->db->where(array('from_currency'=>$from,'to_currency'=>$to))->get('exchange_rates')->row_array();
        if($r)return (float)$r['rate'];
        // Try the inverse if a direct pair is missing.
        $inv=$this->db->where(array('from_currency'=>$to,'to_currency'=>$from))->get('exchange_rates')->row_array();
        if($inv && (float)$inv['rate']>0)return 1/(float)$inv['rate'];
        return NULL;
    }

    public function save_exchange_rate($from,$to,$rate)
    {
        $rate=round((float)$rate,10);
        if($rate<=0)return FALSE;
        $ok=$this->db->replace('exchange_rates',array('from_currency'=>$from,'to_currency'=>$to,'rate'=>$rate,'updated_at'=>date('Y-m-d H:i:s')));
        // Record a daily historical snapshot for the rate chart.
        if($ok)$this->record_rate_history($from,$to,$rate);
        return $ok;
    }

    /**
     * Insert/update today's historical snapshot for a currency pair.
     */
    public function record_rate_history($from,$to,$rate)
    {
        $rate=round((float)$rate,10);
        if($rate<=0)return FALSE;
        $today=date('Y-m-d');
        $existing=$this->db->where(array('from_currency'=>$from,'to_currency'=>$to,'snapshot_date'=>$today))->get('exchange_rate_history')->row_array();
        if($existing){
            return $this->db->where('id',$existing['id'])->update('exchange_rate_history',array('rate'=>$rate));
        }
        return $this->db->insert('exchange_rate_history',array(
            'from_currency'=>$from,'to_currency'=>$to,'rate'=>$rate,
            'snapshot_date'=>$today,'created_at'=>date('Y-m-d H:i:s')
        ));
    }

    /**
     * Daily rate history for a pair over the last $days days, oldest-first.
     * Backfills any missing days (including today) from the current rate so the
     * chart always renders a continuous line.
     */
    public function exchange_rate_history($from,$to,$days=30)
    {
        $days=(int)$days; if($days<2)$days=30;
        $since=date('Y-m-d',strtotime('-'.($days-1).' days'));
        $rows=$this->db->where(array('from_currency'=>$from,'to_currency'=>$to))
            ->where('snapshot_date >=',$since)
            ->order_by('snapshot_date','ASC')
            ->get('exchange_rate_history')->result_array();
        $by_date=array();
        foreach($rows as $r)$by_date[$r['snapshot_date']]=(float)$r['rate'];

        // Determine a seed rate: prefer the most recent historical value, else
        // fall back to the live exchange_rates table, else inverse pair.
        $seed=NULL;
        if(!empty($by_date)){$seed=end($by_date);reset($by_date);}
        else{$seed=$this->exchange_rate($from,$to);}
        if($seed===NULL)$seed=1.0;

        $out=array();
        for($i=$days-1;$i>=0;$i--){
            $d=date('Y-m-d',strtotime("-{$i} days"));
            if(isset($by_date[$d])){
                $seed=(float)$by_date[$d];
            }else{
                // Persist the seed for this missing day so future requests find it.
                $this->record_rate_history($from,$to,$seed);
            }
            $out[]=array('date'=>$d,'rate'=>$seed);
        }
        return $out;
    }

    /**
     * Snapshot every live currency pair once per day. Called by the scheduler.
     * Returns how many pairs were recorded.
     */
    public function snapshot_exchange_rates()
    {
        $rates=$this->exchange_rates();
        $n=0;
        foreach($rates as $r){
            if($this->record_rate_history($r['from_currency'],$r['to_currency'],$r['rate']))$n++;
        }
        return $n;
    }

    public function exchange_convert($user_id,$from_account_id,$to_account_id,$amount)
    {
        $from=$this->account((int)$from_account_id,$user_id);
        $to=$this->account((int)$to_account_id,$user_id);
        $amount=round((float)$amount,2);
        if(!$from || !$to || $from['status']!=='active' || $to['status']!=='active')return array(FALSE,'One of the selected accounts is unavailable.');
        if($amount<=0)return array(FALSE,'Enter a valid amount.');
        if($amount>(float)$from['available_balance'])return array(FALSE,'Insufficient balance in the source account.');
        if($from['currency']===$to['currency'])return array(FALSE,'Choose two accounts with different currencies.');
        $rate=$this->exchange_rate($from['currency'],$to['currency']);
        if($rate===NULL || $rate<=0)return array(FALSE,'No exchange rate is configured for '.$from['currency'].' → '.$to['currency'].'.');
        $converted=round($amount*$rate,2);
        $now=date('Y-m-d H:i:s');
        $ref='FX-'.date('ymd').'-'.random_int(100000,999999);
        $this->db->trans_start();
        $this->db->where('id',$from['id'])->set('balance','balance-'.$amount,FALSE)->set('available_balance','available_balance-'.$amount,FALSE)->update('accounts');
        $this->db->where('id',$to['id'])->set('balance','balance+'.$converted,FALSE)->set('available_balance','available_balance+'.$converted,FALSE)->update('accounts');
        $this->db->insert('transactions',array('account_id'=>$from['id'],'reference'=>$ref.'-D','type'=>'debit','category'=>'Currency exchange','description'=>'Exchanged to '.$to['currency'],'amount'=>$amount,'currency'=>$from['currency'],'balance_after'=>(float)$from['available_balance']-$amount,'status'=>'completed','transaction_date'=>date('Y-m-d'),'created_at'=>$now));
        $this->db->insert('transactions',array('account_id'=>$to['id'],'reference'=>$ref.'-C','type'=>'credit','category'=>'Currency exchange','description'=>'Exchanged from '.$from['currency'],'amount'=>$converted,'currency'=>$to['currency'],'balance_after'=>(float)$to['available_balance']+$converted,'status'=>'completed','transaction_date'=>date('Y-m-d'),'created_at'=>$now));
        $this->db->trans_complete();
        return $this->db->trans_status()?array(TRUE,array('reference'=>$ref,'rate'=>$rate,'converted'=>$converted,'from_currency'=>$from['currency'],'to_currency'=>$to['currency'])):array(FALSE,'The exchange could not be completed.');
    }

    /* -------------------- Mobile Check Deposits -------------------- */

    public function check_deposits_for_user($user_id, $limit = 50)
    {
        return $this->db->select('cd.*, a.account_number, a.name account_name')
            ->from('check_deposits cd')
            ->join('accounts a', 'a.id = cd.account_id')
            ->where('cd.user_id', (int)$user_id)
            ->order_by('cd.created_at', 'DESC')
            ->limit((int)$limit)
            ->get()->result_array();
    }

    public function check_deposit($id, $user_id = NULL)
    {
        $this->db->select('cd.*, a.account_number, a.name account_name, a.currency')
            ->from('check_deposits cd')
            ->join('accounts a', 'a.id = cd.account_id')
            ->where('cd.id', (int)$id);
        if ($user_id !== NULL) $this->db->where('cd.user_id', (int)$user_id);
        return $this->db->get()->row_array();
    }

    public function all_check_deposits($status = NULL, $limit = 100, $offset = 0)
    {
        $this->db->select('cd.*, u.first_name, u.last_name, u.email, a.account_number, a.name account_name')
            ->from('check_deposits cd')
            ->join('users u', 'u.id = cd.user_id')
            ->join('accounts a', 'a.id = cd.account_id');
        if ($status) $this->db->where('cd.status', $status);
        return $this->db->order_by('cd.created_at', 'DESC')
            ->limit((int)$limit, (int)$offset)
            ->get()->result_array();
    }

    public function count_check_deposits($status = NULL)
    {
        if ($status) $this->db->where('status', $status);
        return (int) $this->db->count_all_results('check_deposits');
    }

    public function create_check_deposit($user_id, $account_id, $amount, $front_path, $back_path, $check_number = NULL)
    {
        $account = $this->account((int)$account_id, (int)$user_id);
        if (!$account || $account['status'] !== 'active') return array(FALSE, 'The selected account is unavailable.');
        $amount = round((float)$amount, 2);
        if ($amount <= 0) return array(FALSE, 'Enter a valid deposit amount.');
        $daily_limit = 25000;
        $today_start = date('Y-m-d 00:00:00');
        $today_total = (float) $this->db->select_sum('amount', 'total')
            ->where('user_id', (int)$user_id)->where('created_at >=', $today_start)
            ->get('check_deposits')->row()->total;
        if ($today_total + $amount > $daily_limit) {
            return array(FALSE, 'This deposit would exceed your daily mobile deposit limit of '.money($daily_limit, $account['currency']).'.');
        }
        $reference = 'MCD-'.date('ymd').'-'.random_int(100000, 999999);
        $now = date('Y-m-d H:i:s');
        $this->db->insert('check_deposits', array(
            'user_id' => (int)$user_id, 'account_id' => (int)$account_id, 'reference' => $reference,
            'amount' => $amount, 'check_number' => $check_number ?: NULL,
            'front_image_path' => $front_path, 'back_image_path' => $back_path,
            'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
        ));
        $id = $this->db->insert_id();
        try { $this->notify_user((int)$user_id, 'deposit', 'Check deposit submitted', 'Your deposit of '.money($amount, $account['currency']).' ('.$reference.') is pending review.', 'deposits'); } catch (Exception $e) {}
        return array(TRUE, $reference);
    }

    public function review_check_deposit($id, $approve, $note = NULL)
    {
        $deposit = $this->check_deposit($id);
        if (!$deposit) return array(FALSE, 'Deposit not found.');
        if ($deposit['status'] !== 'pending') return array(FALSE, 'This deposit has already been reviewed.');

        $status = $approve ? 'approved' : 'rejected';
        $this->db->trans_start();
        $transaction_id = NULL;
        if ($approve) {
            $account = $this->account((int)$deposit['account_id'], (int)$deposit['user_id']);
            if (!$account || $account['status'] !== 'active') {
                $this->db->trans_complete();
                return array(FALSE, 'The destination account is no longer active.');
            }
            $reference = $deposit['reference'];
            $now = date('Y-m-d H:i:s');
            $this->db->where('id', $account['id'])
                ->set('balance', 'balance+'.$deposit['amount'], FALSE)
                ->set('available_balance', 'available_balance+'.$deposit['amount'], FALSE)
                ->update('accounts');
            $this->db->insert('transactions', array(
                'account_id' => $account['id'], 'reference' => $reference,
                'type' => 'credit', 'category' => 'Check deposit',
                'description' => 'Mobile check deposit'.($deposit['check_number'] ? ' #'.$deposit['check_number'] : ''),
                'amount' => $deposit['amount'], 'currency' => $account['currency'],
                'balance_after' => (float)$account['available_balance'] + (float)$deposit['amount'],
                'status' => 'completed', 'transaction_date' => date('Y-m-d'), 'created_at' => $now,
            ));
            $transaction_id = $this->db->insert_id();
        }
        $this->db->where('id', $deposit['id'])->update('check_deposits', array(
            'status' => $status, 'review_note' => $note,
            'transaction_id' => $transaction_id, 'updated_at' => date('Y-m-d H:i:s'),
        ));
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return array(FALSE, 'Unable to process this review.');

        $msg = $approve
            ? 'Check deposit '.$deposit['reference'].' approved and credited to your account.'
            : 'Check deposit '.$deposit['reference'].' was rejected. '.(string)$note;
        try { $this->notify_user((int)$deposit['user_id'], 'deposit', $approve ? 'Deposit approved' : 'Deposit rejected', $msg, 'deposits'); } catch (Exception $e) {}
        return array(TRUE, $status);
    }

    /* -------------------- Savings Goals -------------------- */

    public function goals($user_id)
    {
        return $this->db->where('user_id', (int)$user_id)
            ->where('status !=', 'archived')
            ->order_by('status', 'ASC')
            ->order_by('created_at', 'DESC')
            ->get('savings_goals')->result_array();
    }

    public function goal($goal_id, $user_id)
    {
        return $this->db->where(array('id' => (int)$goal_id, 'user_id' => (int)$user_id))
            ->get('savings_goals')->row_array();
    }

    public function create_goal($user_id, $data)
    {
        $now = date('Y-m-d H:i:s');
        $row = array(
            'user_id'       => (int)$user_id,
            'name'          => substr(trim((string)$data['name']), 0, 120),
            'target_amount' => round((float)$data['target_amount'], 2),
            'saved_amount'  => 0,
            'target_date'   => !empty($data['target_date']) ? $data['target_date'] : NULL,
            'icon'          => substr((string)($data['icon'] ?? '🎯'), 0, 16),
            'color'         => substr((string)($data['color'] ?? '#1468e5'), 0, 20),
            'status'        => 'active',
            'created_at'    => $now,
            'updated_at'    => $now,
        );
        $this->db->insert('savings_goals', $row);
        return $this->db->insert_id();
    }

    public function contribute_goal($goal_id, $user_id, $amount)
    {
        $amount = round((float)$amount, 2);
        if ($amount <= 0) return array(FALSE, 'Enter an amount greater than zero.');
        $goal = $this->goal($goal_id, $user_id);
        if (!$goal) return array(FALSE, 'Goal not found.');
        $new_amount = round((float)$goal['saved_amount'] + $amount, 2);
        $status = $new_amount >= (float)$goal['target_amount'] ? 'completed' : 'active';
        $this->db->where('id', $goal['id'])->update('savings_goals', array(
            'saved_amount' => $new_amount, 'status' => $status, 'updated_at' => date('Y-m-d H:i:s'),
        ));
        return array(TRUE, array('saved' => $new_amount, 'status' => $status, 'completed' => $status === 'completed'));
    }

    public function withdraw_goal($goal_id, $user_id, $amount)
    {
        $amount = round((float)$amount, 2);
        if ($amount <= 0) return array(FALSE, 'Enter an amount greater than zero.');
        $goal = $this->goal($goal_id, $user_id);
        if (!$goal) return array(FALSE, 'Goal not found.');
        if ($amount > (float)$goal['saved_amount']) return array(FALSE, 'You cannot withdraw more than you have saved.');
        $new_amount = round((float)$goal['saved_amount'] - $amount, 2);
        $this->db->where('id', $goal['id'])->update('savings_goals', array(
            'saved_amount' => $new_amount, 'status' => 'active', 'updated_at' => date('Y-m-d H:i:s'),
        ));
        return array(TRUE, array('saved' => $new_amount));
    }

    public function delete_goal($goal_id, $user_id)
    {
        return $this->db->where(array('id' => (int)$goal_id, 'user_id' => (int)$user_id))->delete('savings_goals');
    }

    /* -------------------- Budget insights -------------------- */

    /**
     * Monthly spending grouped by category for the last N months.
     * Returns [ ['month'=>'YYYY-MM', 'category'=>..., 'total'=>float], ... ]
     */
    public function monthly_spending_by_category($user_id, $months = 6)
    {
        $since = date('Y-m-01', strtotime('-' . ((int)$months - 1) . ' months'));
        $rows = $this->db->select("DATE_FORMAT(t.created_at,'%Y-%m') AS month, t.category, SUM(t.amount) AS total")
            ->from('transactions t')->join('accounts a', 'a.id=t.account_id')
            ->where('a.user_id', (int)$user_id)
            ->where('t.type', 'debit')->where('t.status', 'completed')
            ->where('t.created_at >=', $since . ' 00:00:00')
            ->group_by('month, t.category')
            ->order_by('month', 'ASC')->order_by('total', 'DESC')
            ->get()->result_array();
        return $rows;
    }

    /** Income vs expense totals for each of the last N months. */
    public function monthly_income_expense($user_id, $months = 6)
    {
        $since = date('Y-m-01', strtotime('-' . ((int)$months - 1) . ' months'));
        $rows = $this->db->select("DATE_FORMAT(t.created_at,'%Y-%m') AS month, t.type, SUM(t.amount) AS total")
            ->from('transactions t')->join('accounts a', 'a.id=t.account_id')
            ->where('a.user_id', (int)$user_id)->where('t.status', 'completed')
            ->where('t.created_at >=', $since . ' 00:00:00')
            ->group_by('month, t.type')
            ->order_by('month', 'ASC')
            ->get()->result_array();
        $out = array();
        foreach ($rows as $r) {
            $m = $r['month'];
            if (!isset($out[$m])) $out[$m] = array('income' => 0.0, 'expenses' => 0.0);
            $out[$m][$r['type'] === 'credit' ? 'income' : 'expenses'] = (float)$r['total'];
        }
        return $out;
    }

    /* -------------------- Statements -------------------- */

    /**
     * Transactions for one account over a month, ordered oldest→newest so
     * the running balance reads chronologically.
     */
    public function statement_transactions($account_id, $year, $month)
    {
        $start = sprintf('%04d-%02d-01 00:00:00', (int)$year, (int)$month);
        $end = date('Y-m-t 23:59:59', strtotime($start));
        return $this->db->where('account_id', (int)$account_id)
            ->where('created_at >=', $start)->where('created_at <=', $end)
            ->where('status', 'completed')
            ->order_by('created_at', 'ASC')->order_by('id', 'ASC')
            ->get('transactions')->result_array();
    }

    /**
     * Opening balance for an account at the start of a month: the current
     * balance minus every completed credit/debit delta from the month onward.
     */
    public function opening_balance($account_id, $year, $month)
    {
        $account = $this->db->select('balance')->where('id', (int)$account_id)->get('accounts')->row_array();
        if (!$account) return 0.0;
        $current = (float)$account['balance'];
        $cutoff = sprintf('%04d-%02d-01 00:00:00', (int)$year, (int)$month);
        return round($current - $this->posted_delta($account_id, $cutoff), 2);
    }

    /** Sum of credit/debit deltas posted at/after a given timestamp. */
    private function posted_delta($account_id, $since)
    {
        $row = $this->db->select_sum('CASE WHEN type="credit" THEN amount ELSE -amount END', 'delta')
            ->where('account_id', (int)$account_id)->where('created_at >=', $since)
            ->where('status', 'completed')
            ->get('transactions')->row();
        return (float)($row->delta ?? 0);
    }

    /** List of months (YYYY-MM) that have transactions for any of the user's accounts, newest first. */
    public function statement_months($user_id)
    {
        $rows = $this->db->select("DISTINCT DATE_FORMAT(t.created_at,'%Y-%m') AS ym", FALSE)
            ->from('transactions t')->join('accounts a', 'a.id=t.account_id')
            ->where('a.user_id', (int)$user_id)
            ->order_by('ym', 'DESC')->limit(36)->get()->result_array();
        return array_column($rows, 'ym');
    }

    public function save_settings($values)
    {
        foreach($values as $key=>$value)$this->db->replace('settings',array('setting_key'=>$key,'setting_value'=>$value,'updated_at'=>date('Y-m-d H:i:s')));
        return TRUE;
    }
}
