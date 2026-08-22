<?php defined('BASEPATH') OR exit('No direct script access allowed');
$income = (float)($current['income'] ?? 0);
$expenses = (float)($current['expenses'] ?? 0);
$savings = max(0, $income - $expenses);
$rate = $income > 0 ? round(($savings / $income) * 100) : 0;

// Prepare bar chart scales
$max_bar = max(1, max(array_merge($chart_income, $chart_expense)));
$colors_arr = array('#1468e5','#15a36a','#7855d0','#e4aa3b','#e64b5d','#0e9aa7','#ff7a59','#5b6b83');
$cat_colors = array();
$i = 0;
foreach (array_keys($by_category) as $c) { $cat_colors[$c] = $colors_arr[$i % count($colors_arr)]; $i++; }
$total_spent = array_sum($by_category);
?>
<div class="page-title">
  <div>
    <em>INSIGHTS</em>
    <h1>Budget &amp; spending</h1>
    <p>See where your money goes and set monthly limits by category.</p>
  </div>
  <div class="budget-range">
    <span class="budget-range__label">Last 6 months</span>
  </div>
</div>

<!-- Top KPIs -->
<div class="stats four budget-kpis">
  <div><i>↓</i><div><span>Income this month</span><b class="credit"><?=money($income)?></b></div></div>
  <div><i>↑</i><div><span>Spent this month</span><b><?=money($expenses)?></b></div></div>
  <div><i>✓</i><div><span>Net saved</span><b class="credit"><?=money($savings)?></b></div></div>
  <div><i>%</i><div><span>Savings rate</span><b><?=$rate?>%</b></div></div>
</div>

<!-- Income vs Expenses chart -->
<div class="panel budget-chart">
  <div class="panel-head">
    <div><h2>Income vs expenses</h2><p>Monthly totals for the last 6 months</p></div>
  </div>
  <div class="budget-chart__body">
    <?php if (empty(array_filter($chart_income)) && empty(array_filter($chart_expense))): ?>
      <div class="empty" style="border:0">No transaction data yet. Once you use your accounts, your trends will appear here.</div>
    <?php else: ?>
    <div class="bar-chart">
      <?php foreach ($chart_labels as $idx => $label):
        $ih = round(($chart_income[$idx] / $max_bar) * 100);
        $eh = round(($chart_expense[$idx] / $max_bar) * 100);
      ?>
      <div class="bar-group">
        <div class="bar-stack">
          <div class="bar bar--income" style="height:<?=$ih?>%" title="Income: <?=money($chart_income[$idx])?>"></div>
          <div class="bar bar--expense" style="height:<?=$eh?>%" title="Expenses: <?=money($chart_expense[$idx])?>"></div>
        </div>
        <span class="bar-label"><?=html_escape($label)?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="bar-legend">
      <span><i style="background:#15a36a"></i> Income</span>
      <span><i style="background:#e64b5d"></i> Expenses</span>
      <span><i style="background:#1468e5"></i> Net saved</span>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="grid two">
  <!-- Category breakdown -->
  <div class="panel">
    <div class="panel-head"><div><h2>Spending by category</h2><p>Over the last 6 months</p></div></div>
    <?php if (empty($by_category)): ?>
      <div class="empty" style="border:0">No spending recorded yet.</div>
    <?php else: ?>
    <div class="cat-breakdown">
      <?php foreach ($by_category as $cat => $amt):
        $p = $total_spent > 0 ? round(($amt / $total_spent) * 100) : 0;
        $color = $cat_colors[$cat];
      ?>
      <div class="cat-row">
        <div class="cat-row__head">
          <span><i style="background:<?=$color?>"></i><?=html_escape($cat)?></span>
          <b><?=money($amt)?></b>
        </div>
        <div class="cat-row__bar"><i style="width:<?=$p?>%;background:<?=$color?>"></i></div>
        <small><?=$p?>% of total spending</small>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Monthly budget limits -->
  <div class="panel">
    <div class="panel-head"><div><h2>Monthly budget limits</h2><p>Get a warning when a category is near its limit</p></div></div>
    <div class="budget-limits">
      <?php
      $categories = array_unique(array_merge(array_keys($current_by_category), array_keys($by_category)));
      if (empty($categories)) echo '<div class="empty" style="border:0">Add transactions to set budget limits per category.</div>';
      foreach ($categories as $cat):
        $spent = (float)($current_by_category[$cat] ?? 0);
        $limit = (float)($limits[$cat] ?? 0);
        $pct = $limit > 0 ? round(($spent / $limit) * 100) : 0;
        $cls = $limit <= 0 ? 'is-unset' : ($pct >= 100 ? 'is-over' : $pct >= 80 ? 'is-near' : '');
      ?>
      <div class="budget-limit <?=$cls?>">
        <form method="post" action="<?=site_url('budget/save-limit')?>" class="budget-limit__form">
          <div class="budget-limit__head">
            <span><i style="background:<?=$cat_colors[$cat] ?? '#1468e5'?>"></i><?=html_escape($cat)?></span>
            <?php if ($limit > 0): ?>
              <b><?=money($spent)?> / <?=money($limit)?></b>
            <?php else: ?>
              <b class="muted">No limit set</b>
            <?php endif; ?>
          </div>
          <?php if ($limit > 0): ?>
          <div class="cat-row__bar"><i style="width:<?=min(100,$pct)?>%"></i></div>
          <?php endif; ?>
          <div class="budget-limit__controls">
            <input type="hidden" name="category" value="<?=html_escape($cat)?>">
            <input type="number" name="limit" step="0.01" min="0" placeholder="Monthly limit" value="<?= $limit > 0 ? html_escape($limit) : '' ?>">
            <button class="btn">Save</button>
            <?php if ($limit > 0): ?>
            <button class="outline" formnovalidate name="limit" value="0" title="Remove limit">✕</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="insight">
  <i>💡</i>
  <div>
    <small>TIP</small>
    <h3>Try the 50/30/20 rule</h3>
    <p>Aim to spend about 50% of your income on needs, 30% on wants, and put 20% toward savings goals and debt.</p>
    <a href="<?=site_url('goals')?>">Set up a savings goal →</a>
  </div>
</div>
