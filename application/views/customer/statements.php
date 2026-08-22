<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="page-title">
  <div>
    <em>DOCUMENTS</em>
    <h1>Statements</h1>
    <p>Download monthly PDF statements for each of your accounts.</p>
  </div>
  <a class="outline" href="<?=site_url('transactions/statement')?>">↓ Export transactions (CSV)</a>
</div>

<?php if (empty($months)): ?>
  <div class="empty">
    <span style="font-size:42px;display:block;margin-bottom:8px;opacity:.6">📄</span>
    <h3>No statements yet</h3>
    <p>Once your accounts have transactions, monthly statements will appear here automatically.</p>
  </div>
<?php else: ?>
  <section class="panel statements-list">
    <div class="panel-head"><div><h2>Available statements</h2><p><?=count($months)?> month<?=count($months)===1?'':'s'?> of activity</p></div></div>
    <?php foreach ($months as $m):
      $ts = strtotime($m['month'].'-01');
    ?>
      <div class="statement-month">
        <div class="statement-month__head">
          <h3><?=date('F Y', $ts)?></h3>
          <span class="statement-month__count"><?=count($m['accounts'])?> account<?=count($m['accounts'])===1?'':'s'?></span>
        </div>
        <div class="statement-accounts">
          <?php foreach ($m['accounts'] as $a):
            [$y,$mo] = explode('-', $m['month']);
          ?>
            <a class="statement-card" href="<?=site_url('statements/'.$a['id'].'/'.$y.'/'.$mo)?>">
              <span class="statement-card__icon">📄</span>
              <div class="statement-card__body">
                <b><?=html_escape($a['name'])?></b>
                <small>••••<?=substr($a['account_number'],-4)?> · <?=strtoupper($a['currency'])?> · PDF</small>
              </div>
              <span class="statement-card__dl">↓</span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<div class="insight">
  <i>🔒</i>
  <div>
    <small>SECURE</small>
    <h3>Your statements are private</h3>
    <p>PDFs are generated on demand and stored in your protected folder. Each statement lists every transaction, a running balance, and opening/closing totals for the month.</p>
  </div>
</div>
