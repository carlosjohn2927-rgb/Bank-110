<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="page-title">
  <div>
    <em>DEPOSIT</em>
    <h1>Deposit a check</h1>
    <p>Photograph the front and back of your endorsed check and deposit it in seconds.</p>
  </div>
</div>

<div class="deposit-kpis">
  <div class="deposit-kpi"><span>Pending review</span><b><?=money($total_pending)?></b></div>
  <div class="deposit-kpi"><span>Approved (all time)</span><b class="credit"><?=money($total_approved)?></b></div>
  <div class="deposit-kpi deposit-kpi--info">
    <span>Daily limit</span><b>$25,000</b>
    <small>Per customer, per business day</small>
  </div>
</div>

<div class="grid two deposit-grid">
  <!-- Capture form -->
  <section class="panel deposit-capture">
    <div class="panel-head"><div><h2>New check deposit</h2><p>Photos must be clear, well-lit and show all four corners.</p></div></div>
    <form method="post" action="<?=site_url('deposits/create')?><input type="hidden" name="<?=$this->security->get_csrf_token_name()?>" value="<?=$this->security->get_csrf_hash()?>">" enctype="multipart/form-data" class="deposit-form" id="depositForm">
      <label>Deposit to
        <select name="account_id" required>
          <option value="">Choose an account…</option>
          <?php foreach($accounts as $a): ?>
            <option value="<?=$a['id']?>" <?=$a['status']!=='active'?'disabled':''?>>
              <?=html_escape($a['name'])?> ••••<?=substr($a['account_number'],-4)?> — <?=money($a['available_balance'],$a['currency'])?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <div class="form-grid">
        <label>Check amount
          <input type="number" name="amount" step="0.01" min="0.01" max="25000" required placeholder="0.00">
        </label>
        <label>Check number (optional)
          <input type="text" name="check_number" maxlength="40" placeholder="e.g. 1042">
        </label>
      </div>

      <div class="check-photos">
        <label class="check-photo" id="photo-front">
          <input type="file" name="front" accept="image/jpeg,image/png,image/webp" capture="environment" required>
          <span class="check-photo__icon" aria-hidden="true">📷</span>
          <b>Front of check</b>
          <small>Tap to capture / upload</small>
          <img alt="" class="check-photo__preview">
        </label>
        <label class="check-photo" id="photo-back">
          <input type="file" name="back" accept="image/jpeg,image/png,image/webp" capture="environment" required>
          <span class="check-photo__icon" aria-hidden="true">📷</span>
          <b>Back (endorsed)</b>
          <small>Sign &amp; photograph</small>
          <img alt="" class="check-photo__preview">
        </label>
      </div>

      <div class="deposit-tip">
        <b>📌 Before you submit</b>
        <ul>
          <li>Endorse the back of the check with your signature.</li>
          <li>Keep the paper check for 14 days after it clears, then destroy it.</li>
          <li>Funds are typically available within 1–2 business days after approval.</li>
        </ul>
      </div>

      <button class="btn wide" type="submit">Submit deposit for review</button>
    </form>
  </section>

  <!-- Recent deposits -->
  <section class="panel">
    <div class="panel-head"><div><h2>Your deposits</h2><p>Recent check deposits and their status</p></div></div>
    <?php if(!$deposits): ?>
      <div class="empty">No check deposits yet. Your submissions will appear here.</div>
    <?php else: ?>
    <div class="deposit-list">
      <?php foreach($deposits as $d):
        $status = $d['status'];
      ?>
      <a href="<?=site_url('deposits/'.$d['id'])?>" class="deposit-row deposit-row--<?=$status?>">
        <div class="deposit-row__icon"><?= $status==='approved' ? '✓' : ($status==='rejected' ? '!' : '⏳') ?></div>
        <div class="deposit-row__body">
          <b><?=money($d['amount'])?></b>
          <small><?=html_escape($d['reference'])?> • <?=$d['account_name']?> • <?=date('M j, g:i a',strtotime($d['created_at']))?></small>
        </div>
        <span class="deposit-status deposit-status--<?=$status?>"><?=ucfirst($status)?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</div>

<script>
// Live image preview for the check photo inputs
document.querySelectorAll('.check-photo input[type=file]').forEach(function(input){
  input.addEventListener('change', function(){
    var file = input.files && input.files[0];
    if(!file) return;
    var wrap = input.closest('.check-photo');
    var img = wrap.querySelector('.check-photo__preview');
    var reader = new FileReader();
    reader.onload = function(e){ img.src = e.target.result; wrap.classList.add('has-image'); };
    reader.readAsDataURL(file);
  });
});
</script>
