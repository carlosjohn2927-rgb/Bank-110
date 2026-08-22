<?php defined('BASEPATH') OR exit('No direct script access allowed');
$d = $deposit;
$status = $d['status'];
?>
<div class="page-title">
  <div>
    <em>CHECK DEPOSIT</em>
    <h1><?=html_escape($d['reference'])?></h1>
    <p>Submitted <?=date('F j, Y \a\t g:ia',strtotime($d['created_at']))?></p>
  </div>
  <span class="deposit-status deposit-status--<?=$status?> deposit-status--lg"><?=ucfirst($status)?></span>
</div>

<div class="grid two deposit-detail-grid">
  <section class="panel">
    <div class="panel-head"><div><h2>Check images</h2><p>Tap an image to view it full size</p></div></div>
    <div class="check-images">
      <a href="<?=base_url($d['front_image_path'])?>" target="_blank" class="check-image">
        <img src="<?=base_url($d['front_image_path'])?>" alt="Front of check <?=$d['reference']?>" loading="lazy">
        <span>Front</span>
      </a>
      <a href="<?=base_url($d['back_image_path'])?>" target="_blank" class="check-image">
        <img src="<?=base_url($d['back_image_path'])?>" alt="Back of check <?=$d['reference']?>" loading="lazy">
        <span>Back (endorsed)</span>
      </a>
    </div>
    <?php if(!empty($d['review_note'])): ?>
    <div class="deposit-note">
      <b><?=$status==='rejected'?'Rejection reason':'Note from reviewer'?></b>
      <p><?=html_escape($d['review_note'])?></p>
    </div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <div class="panel-head"><div><h2>Details</h2></div></div>
    <div class="detail-list">
      <div><span>Amount</span><b><?=money($d['amount'],$d['currency'])?></b></div>
      <div><span>Status</span><b class="deposit-status deposit-status--<?=$status?>"><?=ucfirst($status)?></b></div>
      <div><span>Deposited to</span><b><?=html_escape($d['account_name'])?> ••••<?=substr($d['account_number'],-4)?></b></div>
      <?php if(!empty($d['check_number'])): ?>
      <div><span>Check number</span><b>#<?=html_escape($d['check_number'])?></b></div>
      <?php endif; ?>
      <div><span>Submitted</span><b><?=date('M j, Y g:ia',strtotime($d['created_at']))?></b></div>
      <div><span>Last updated</span><b><?=date('M j, Y g:ia',strtotime($d['updated_at']))?></b></div>
    </div>

    <?php if($status==='pending'): ?>
    <div class="deposit-pending-note">
      <b>⏳ Under review</b>
      <p>Your deposit is being reviewed by our team. This usually takes a few minutes during business hours. You'll get a notification as soon as it's approved or rejected.</p>
    </div>
    <?php elseif($status==='approved'): ?>
    <div class="deposit-success-note">
      <b>✓ Deposit complete</b>
      <p>The funds have been credited to your account and are now available.</p>
      <a class="btn" href="<?=site_url('transactions')?>">View in transactions</a>
    </div>
    <?php else: ?>
    <div class="deposit-rejected-note">
      <b>! Deposit was not accepted</b>
      <p>Please review the reason above. If you have questions, contact support or submit a new deposit with clearer images.</p>
      <a class="btn" href="<?=site_url('support')?>">Contact support</a>
    </div>
    <?php endif; ?>

    <a class="outline wide" style="margin-top:14px" href="<?=site_url('deposits')?>">← Back to deposits</a>
  </section>
</div>
