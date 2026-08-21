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

    public function total_balance($user_id)
    {
        $row = $this->db->select_sum('available_balance')->where('user_id', $user_id)->where('status', 'active')->get('accounts')->row_array();
        return (float) ($row['available_balance'] ?? 0);
    }

    public function transactions_for_user($user_id, $limit = 50, $filters = array())
    {
        $this->db->select('t.*, a.account_number, a.name account_name')->from('transactions t')->join('accounts a', 'a.id=t.account_id')->where('a.user_id', $user_id);
        if (!empty($filters['search'])) $this->db->group_start()->like('t.description', $filters['search'])->or_like('t.reference', $filters['search'])->group_end();
        if (!empty($filters['type'])) $this->db->where('t.type', $filters['type']);
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

    public function create_transfer($user_id, $data)
    {
        $account = $this->account($data['from_account_id'], $user_id);
        $amount = round((float) $data['amount'], 2);
        if (!$account || $account['status'] !== 'active') return array(FALSE, 'The selected account is unavailable.');
        if ($amount <= 0) return array(FALSE, 'Enter a valid transfer amount.');
        if ($amount > (float) $account['available_balance']) return array(FALSE, 'Insufficient available balance.');
        if ($amount > 25000) return array(FALSE, 'This transfer exceeds your daily limit.');

        $reference = 'NW-'.date('ymd').'-'.random_int(100000, 999999);
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->where('id', $account['id'])->set('balance', 'balance-'.$amount, FALSE)->set('available_balance', 'available_balance-'.$amount, FALSE)->update('accounts');
        $this->db->insert('transfers', array(
            'reference'=>$reference, 'user_id'=>$user_id, 'from_account_id'=>$account['id'], 'beneficiary_id'=>$data['beneficiary_id'] ?: NULL,
            'recipient_name'=>$data['recipient_name'], 'recipient_account'=>$data['recipient_account'], 'recipient_bank'=>$data['recipient_bank'],
            'transfer_type'=>$data['transfer_type'], 'amount'=>$amount, 'currency'=>$account['currency'], 'fee'=>0, 'note'=>$data['note'],
            'scheduled_for'=>$data['scheduled_for'], 'status'=>'pending', 'created_at'=>$now, 'updated_at'=>$now
        ));
        $transfer_id = $this->db->insert_id();
        $this->db->insert('transactions', array(
            'account_id'=>$account['id'], 'transfer_id'=>$transfer_id, 'reference'=>$reference, 'type'=>'debit', 'category'=>'Transfer',
            'description'=>'Transfer to '.$data['recipient_name'], 'amount'=>$amount, 'currency'=>$account['currency'],
            'balance_after'=>(float)$account['available_balance']-$amount, 'status'=>'pending', 'transaction_date'=>$data['scheduled_for'], 'created_at'=>$now
        ));
        $this->db->trans_complete();
        return $this->db->trans_status() ? array(TRUE, $reference) : array(FALSE, 'The transfer could not be submitted.');
    }

    public function cards($user_id)
    {
        return $this->db->select('c.*, a.account_number, a.available_balance')->from('cards c')->join('accounts a','a.id=c.account_id')->where('c.user_id',$user_id)->get()->result_array();
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

    public function tickets($user_id = NULL)
    {
        $this->db->select('st.*, u.first_name, u.last_name')->from('support_tickets st')->join('users u','u.id=st.user_id');
        if ($user_id !== NULL) $this->db->where('st.user_id',$user_id);
        return $this->db->order_by('st.updated_at','DESC')->get()->result_array();
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
        return $this->db->select('u.*, cp.phone, cp.address, cp.city, cp.country, cp.date_of_birth')->from('users u')->join('customer_profiles cp','cp.user_id=u.id','left')->where('u.id',$user_id)->get()->row_array();
    }

    public function update_profile($user_id, $user, $profile)
    {
        $this->db->trans_start();
        $this->db->where('id',$user_id)->update('users',$user);
        if ($this->db->where('user_id',$user_id)->count_all_results('customer_profiles')) $this->db->where('user_id',$user_id)->update('customer_profiles',$profile);
        else { $profile['user_id']=$user_id; $this->db->insert('customer_profiles',$profile); }
        $this->db->trans_complete(); return $this->db->trans_status();
    }

    public function admin_metrics()
    {
        $metrics = array();
        $metrics['customers'] = $this->db->where('role','customer')->count_all_results('users');
        $metrics['deposits'] = (float)($this->db->select_sum('available_balance')->get('accounts')->row()->available_balance ?? 0);
        $metrics['transactions_today'] = $this->db->where('DATE(created_at)',date('Y-m-d'))->count_all_results('transactions');
        $metrics['pending'] = $this->db->where('status','pending')->count_all_results('transfers');
        return $metrics;
    }

    public function customers($limit=100)
    {
        return $this->db->select('u.*, COALESCE(SUM(a.available_balance),0) total_balance, COUNT(a.id) account_count')->from('users u')->join('accounts a','a.user_id=u.id','left')->where('u.role','customer')->group_by('u.id')->order_by('u.created_at','DESC')->limit($limit)->get()->result_array();
    }

    public function customer_detail($id)
    {
        $user=$this->profile($id); if(!$user || $user['role']!=='customer') return NULL;
        $user['accounts']=$this->accounts($id); $user['transactions']=$this->transactions_for_user($id,20); return $user;
    }

    public function all_transactions($kind=NULL, $limit=100)
    {
        $this->db->select('t.*, a.account_number, u.first_name, u.last_name')->from('transactions t')->join('accounts a','a.id=t.account_id')->join('users u','u.id=a.user_id');
        if ($kind === 'deposits') $this->db->where('t.type','credit');
        if ($kind === 'transfers') $this->db->where('t.category','Transfer');
        return $this->db->order_by('t.created_at','DESC')->limit($limit)->get()->result_array();
    }

    public function set_transaction_status($id, $status)
    {
        if (!in_array($status,array('completed','failed','cancelled'),TRUE)) return FALSE;
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

    public function reply_ticket($id,$admin_id,$message,$status)
    {
        if(!in_array($status,array('open','in_progress','resolved','closed'),TRUE))$status='in_progress'; $now=date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->insert('ticket_messages',array('ticket_id'=>$id,'user_id'=>$admin_id,'message'=>$message,'is_staff'=>1,'created_at'=>$now));
        $this->db->where('id',$id)->update('support_tickets',array('status'=>$status,'assigned_to'=>$admin_id,'updated_at'=>$now));
        $this->db->trans_complete(); return $this->db->trans_status();
    }

    public function all_cards() { return $this->db->select('c.*,u.first_name,u.last_name,a.account_number')->from('cards c')->join('users u','u.id=c.user_id')->join('accounts a','a.id=c.account_id')->order_by('c.created_at','DESC')->get()->result_array(); }
    public function all_loans() { return $this->db->select('l.*,u.first_name,u.last_name')->from('loans l')->join('users u','u.id=l.user_id')->order_by('l.created_at','DESC')->get()->result_array(); }

    public function create_customer($data)
    {
        $now=date('Y-m-d H:i:s'); $this->db->trans_start();
        $this->db->insert('users',array('username'=>$data['username'],'email'=>$data['email'],'password_hash'=>password_hash($data['password'],PASSWORD_DEFAULT),'first_name'=>$data['first_name'],'last_name'=>$data['last_name'],'role'=>'customer','status'=>'active','created_at'=>$now,'updated_at'=>$now));
        $uid=$this->db->insert_id();
        $this->db->insert('customer_profiles',array('user_id'=>$uid,'phone'=>$data['phone'],'country'=>$data['country'],'kyc_status'=>'pending','created_at'=>$now,'updated_at'=>$now));
        $this->db->insert('accounts',array('user_id'=>$uid,'account_number'=>'NW'.date('ym').str_pad($uid,7,'0',STR_PAD_LEFT),'name'=>'NorthWest Select','type'=>'checking','currency'=>'USD','balance'=>$data['opening_balance'],'available_balance'=>$data['opening_balance'],'status'=>'active','is_primary'=>1,'created_at'=>$now,'updated_at'=>$now));
        $this->db->trans_complete(); return $this->db->trans_status() ? $uid : FALSE;
    }

    public function audit($action,$description,$user_id=NULL)
    {
        return $this->db->insert('audit_logs',array('user_id'=>$user_id,'action'=>$action,'description'=>$description,'ip_address'=>$this->input->ip_address(),'user_agent'=>substr($this->input->user_agent(),0,255),'created_at'=>date('Y-m-d H:i:s')));
    }

    public function settings()
    {
        $rows=$this->db->get('settings')->result_array(); $out=array(); foreach($rows as $r)$out[$r['setting_key']]=$r['setting_value']; return $out;
    }

    public function save_settings($values)
    {
        foreach($values as $key=>$value)$this->db->replace('settings',array('setting_key'=>$key,'setting_value'=>$value,'updated_at'=>date('Y-m-d H:i:s')));
        return TRUE;
    }
}
