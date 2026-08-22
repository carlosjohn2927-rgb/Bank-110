<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Savings Goals — let customers set targets, add/withdraw money and
 * track progress toward their savings goals.
 */
class Goals extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_customer();
    }

    public function index()
    {
        $goals = $this->Bank_model->goals($this->user['id']);
        $total_saved = 0;
        $total_target = 0;
        foreach ($goals as $g) {
            $total_saved += (float)$g['saved_amount'];
            $total_target += (float)$g['target_amount'];
        }
        $this->render('customer/goals', array(
            'title'        => 'Savings goals',
            'goals'        => $goals,
            'total_saved'  => $total_saved,
            'total_target' => $total_target,
        ));
    }

    public function create()
    {
        if ($this->input->method() !== 'post') redirect('goals');
        $this->form_validation->set_rules('name', 'Goal name', 'required|trim|max_length[120]');
        $this->form_validation->set_rules('target_amount', 'Target amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('target_date', 'Target date', 'callback__valid_date');
        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors(' ', ' '));
            redirect('goals');
        }
        $id = $this->Bank_model->create_goal($this->user['id'], array(
            'name'          => $this->input->post('name', TRUE),
            'target_amount' => $this->input->post('target_amount'),
            'target_date'   => $this->input->post('target_date', TRUE) ?: NULL,
            'icon'          => $this->input->post('icon', TRUE) ?: '🎯',
            'color'         => $this->input->post('color', TRUE) ?: '#1468e5',
        ));
        $this->Bank_model->audit('goal_created', 'Created savings goal #'.$id, $this->user['id']);
        $this->session->set_flashdata('success', 'Goal created — start saving toward it!');
        redirect('goals');
    }

    public function contribute($goal_id = NULL)
    {
        if ($this->input->method() !== 'post') redirect('goals');
        $amount = (float)$this->input->post('amount');
        list($ok, $res) = $this->Bank_model->contribute_goal((int)$goal_id, $this->user['id'], $amount);
        if (!$ok) { $this->session->set_flashdata('error', $res); redirect('goals'); }
        $msg = 'Added '.money($amount).' to your goal.';
        if (!empty($res['completed'])) $msg .= ' 🎉 Goal reached!';
        $this->Bank_model->audit('goal_contribution', $msg.' (goal #'.$goal_id.')', $this->user['id']);
        $this->session->set_flashdata('success', $msg);
        redirect('goals');
    }

    public function withdraw($goal_id = NULL)
    {
        if ($this->input->method() !== 'post') redirect('goals');
        $amount = (float)$this->input->post('amount');
        list($ok, $res) = $this->Bank_model->withdraw_goal((int)$goal_id, $this->user['id'], $amount);
        if (!$ok) { $this->session->set_flashdata('error', $res); redirect('goals'); }
        $this->Bank_model->audit('goal_withdrawal', 'Withdrew '.money($amount).' from goal #'.$goal_id, $this->user['id']);
        $this->session->set_flashdata('success', 'Withdrew '.money($amount).' from your goal.');
        redirect('goals');
    }

    public function delete($goal_id = NULL)
    {
        $this->Bank_model->delete_goal((int)$goal_id, $this->user['id']);
        $this->Bank_model->audit('goal_deleted', 'Deleted savings goal #'.$goal_id, $this->user['id']);
        $this->session->set_flashdata('success', 'Goal deleted.');
        redirect('goals');
    }

    /** Validation callback: empty or a valid YYYY-MM-DD date. */
    public function _valid_date($value)
    {
        if ($value === '' || $value === NULL) return TRUE;
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            $this->form_validation->set_message('_valid_date', 'Enter a valid target date.');
            return FALSE;
        }
        return TRUE;
    }
}
