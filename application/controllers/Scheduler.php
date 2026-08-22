<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Scheduler — processes scheduled transfers.
 *
 * Designed to be called by a cron job (e.g. every hour) so that scheduled
 * transfers complete automatically once their date arrives. It is safe to hit
 * over HTTP with a cron-triggered request, or via CLI.
 *
 *   Cron:  0,15,30,45 * * * *  wget -q -O /dev/null "https://YOURDOMAIN/scheduler/run?key=YOUR_SCHEDULER_KEY"
 *
 * The key is compared against VP_SCHEDULER_KEY in the .env to prevent abuse.
 */
class Scheduler extends CI_Controller
{
    public function run()
    {
        $key=(string)getenv('VP_SCHEDULER_KEY');
        if($key==='' || !hash_equals($key,(string)$this->input->get('key'))){return $this->_json(array('ok'=>FALSE,'error'=>'Forbidden'),403);}
        $this->load->database();
        $count=$this->Bank_model->process_scheduled();
        $rates=$this->Bank_model->snapshot_exchange_rates();
        return $this->_json(array('ok'=>TRUE,'processed'=>$count,'rates_snapshotted'=>$rates));
    }

    private function _json($data,$status=200)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode($data));
        return $this->output;
    }
}
