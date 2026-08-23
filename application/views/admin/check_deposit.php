<?php defined('BASEPATH') OR exit('No direct script access allowed');
$d = $deposit; $isPending = $d['status']==='pending';
?>
<div class="page-title">
  <div>
    <em>CHECK DEPOSIT REVIEW</em>
    <h1><?=html_escape($d['reference'])?></h1>
    <p>Submitted <?=date('F j, Y \a\t g:ia',strtotime($d['created_at']))?></p>
  </div>
  <span class="deposit-status deposit-status--<?=$d['status']?> deposit-status--lg"><?=ucfirst($d['status'])?></span>
</div>

<div class="grid two admin-check-detail">
  <section class="panel">
    <div class="panel-head"><div><h2>Check images</h2><p>Verify payee, amount, signature and endorsement</p></div></div>
    <div class="check-images check-images--admin">
      <figure>
        <a href="<?=base_url($d['front_image_path'])?>" target="_blank"><img src="<?=base_url($d['front_image_path'])?>" alt="Front of check"></a>
        <figcaption>Front</figcaption>
      </figure>
      <figure>
        <a href="<?=base_url($d['back_image_path'])?>" target="_blank"><img src="<?=base_url($d['back_image_path'])?>" alt="Back of check"></a>
        <figcaption>Back (endorsed)</figcaption>
      </figure>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head"><div><h2>Deposit &amp; customer</h2></div></div>
    <div class="detail-list">
      <div><span>Customer</span><b><?=html_escape($d['first_name'].' '.$d['last_name'])?></b></div>
      <div><span>Email</span><b><?=html_escape($d['email'])?></b></div>
      <div><span>Amount</span><b class="amount"><?=money($d['amount'],$d['currency'])?></b></div>
      <div><span>Check number</span><b><?=$d['check_number']?html_escape($d['check_number']):'—'?></b></div>
      <div><span>Deposit to</span><b><?=html_escape($d['name'])?> ••••<?=substr($d['account_number'],-4)?></b></div>
      <div><span>Submitted</span><b><?=date('M j, Y g:ia',strtotime($d['created_at']))?></b></div>
      <?php if($d['review_note']): ?>
      <div><span>Review note</span><b><?=html_escape($d['review_note'])?></b></div>
      <?php endif; ?>
    </div>

    <?php if($isPending): ?>
    <form method="post" action="<?=site_url('admin/check-deposits/'.$d['id'].'/review')?>" class="check-review-form"><input type="hidden" name="<?=$this->security->get_csrf_token_name()?>" value="<?=$this->security->get_csrf_hash()?>">
      <label>Reviewer note (required if rejecting)
        <textarea name="note" rows="3" placeholder="Add a note — shown to the customer, especially for rejections."></textarea>
      </label>
      <div class="check-review-actions">
        <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Reject this deposit? The customer will be notified.');">✕ Reject deposit</button>
        <button type="submit" name="action" value="approve" class="btn btn-success" onclick="return confirm('Approve and credit <?=html_escape(money($d['amount'],$d['currency']))?> to the customer\'s account?');">✓ Approve &amp; credit</button>
      </div>
    </form>
    <?php else: ?>
    <a class="outline wide" href="<?=site_url('admin/check-deposits?status='.$d['status'])?>">← Back to <?=html_escape($d['status'])?> deposits</a>
    <?php endif; ?>
  </section>
</div>
