<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Budget insights — spending by category and income-vs-expense trends over
 * the last six months, with a simple monthly budget vs. actual comparison.
 *
 * Goals/budget targets are stored in user_preferences so they survive
 * without extra schema: preference key `budget_limits` holds a JSON map of
 * category => monthly limit (e.g. {"Groceries":500}).
 */
class Budget extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_customer();
    }

    public function index()
    {
        $user_id = (int)$this->user['id'];

        $category_rows = $this->Bank_model->monthly_spending_by_category($user_id, 6);
        $monthly = $this->Bank_model->monthly_income_expense($user_id, 6);

        // Build a sorted list of months (chronological).
        $months = array_keys($monthly);
        sort($months);

        // Category totals across the whole period and the current month.
        $current_month = date('Y-m');
        $by_category = array();
        $current_by_category = array();
        foreach ($category_rows as $r) {
            $cat = $r['category'] ?: 'Other';
            $by_category[$cat] = ($by_category[$cat] ?? 0) + (float)$r['total'];
            if ($r['month'] === $current_month) {
                $current_by_category[$cat] = (float)$r['total'];
            }
        }
        arsort($by_category);

        // Load budget limits from preferences.
        $limits = $this->budget_limits($user_id);

        // Chart data: last 6 months income vs expenses.
        $chart_labels = array();
        $chart_income = array();
        $chart_expense = array();
        $chart_savings = array();
        foreach ($months as $m) {
            $row = $monthly[$m];
            $chart_labels[] = date('M', strtotime($m.'-01'));
            $chart_income[] = round($row['income'], 2);
            $chart_expense[] = round($row['expenses'], 2);
            $chart_savings[] = round(max(0, $row['income'] - $row['expenses']), 2);
        }

        $current = $monthly[$current_month] ?? array('income' => 0, 'expenses' => 0);

        $this->render('customer/budget', array(
            'title'                 => 'Budget & insights',
            'current'               => $current,
            'months'                => $months,
            'by_category'           => $by_category,
            'current_by_category'   => $current_by_category,
            'limits'                => $limits,
            'chart_labels'          => $chart_labels,
            'chart_income'          => $chart_income,
            'chart_expense'         => $chart_expense,
            'chart_savings'         => $chart_savings,
        ));
    }

    /** Save a monthly limit for one category. */
    public function save_limit()
    {
        if ($this->input->method() !== 'post') redirect('budget');
        $category = trim((string)$this->input->post('category', TRUE));
        $limit = (float)$this->input->post('limit');
        if ($category === '') redirect('budget');

        $limits = $this->budget_limits((int)$this->user['id']);
        if ($limit <= 0) {
            unset($limits[$category]);
        } else {
            $limits[$category] = round($limit, 2);
        }
        $this->save_budget_limits((int)$this->user['id'], $limits);
        $this->session->set_flashdata('success', $limit > 0
            ? 'Budget limit for '.htmlspecialchars($category).' updated.'
            : 'Budget limit removed.');
        redirect('budget');
    }

    private function budget_limits($user_id)
    {
        $row = $this->db->where(array('user_id' => (int)$user_id, 'pref_key' => 'budget_limits'))
            ->get('user_preferences')->row_array();
        if (!$row || empty($row['pref_value'])) return array();
        $decoded = json_decode($row['pref_value'], TRUE);
        return is_array($decoded) ? $decoded : array();
    }

    private function save_budget_limits($user_id, array $limits)
    {
        $this->db->replace('user_preferences', array(
            'user_id'    => (int)$user_id,
            'pref_key'   => 'budget_limits',
            'pref_value' => json_encode($limits),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }
}
