<?php defined('BASEPATH') OR exit('No direct script access allowed');
$d = $doc;
$isImg = strpos($d['mime_type'] ?? '', 'image/') === 0;
$typeLabel = ucfirst(str_replace('_',' ',$d['doc_type']));
?>
<div class="page-title">
  <div>
    <em>KYC REVIEW</em>
    <h1><?=html_escape($d['first_name'].' '.$d['last_name'])?></h1>
    <p><?=html_escape($d['email'])?> · <?=$typeLabel?> · submitted <?=date('F j, Y \a\t g:ia',strtotime($d['created_at']))?></p>
  </div>
  <span class="deposit-status deposit-status--<?=$d['status']?> deposit-status--lg"><?=ucfirst($d['status'])?></span>
</div>
<div class="grid two admin-check-detail">
  <section class="panel">
    <div class="panel-head"><div><h2>Document</h2><p><?=html_escape($d['original_name'] ?: $typeLabel)?></p></div></div>
    <div class="kyc-preview">
      <?php if ($isImg): ?>
        <a href="<?=site_url('kyc/download/'.$d['id'])?>" target="_blank"><img src="<?=site_url('kyc/download/'.$d['id'])?>" alt="<?=$typeLabel?>"></a>
      <?php else: ?>
        <div class="kyc-pdf">
          <span>📄</span>
          <p>This is a PDF document.</p>
          <a class="btn" href="<?=site_url('kyc/download/'.$d['id'])?>" target="_blank">Open PDF</a>
        </div>
      <?php endif; ?>
    </div>
  </section>
  <section class="panel">
    <div class="panel-head"><div><h2>Review</h2></div></div>
    <div class="detail-list">
      <div><span>Customer</span><b><?=html_escape($d['first_name'].' '.$d['last_name'])?></b></div>
      <div><span>Email</span><b><?=html_escape($d['email'])?></b></div>
      <div><span>Document type</span><b><?=$typeLabel?></b></div>
      <div><span>Submitted</span><b><?=date('M j, Y g:ia',strtotime($d['created_at']))?></b></div>
      <?php if($d['reviewed_at']):?>
        <div><span>Reviewed</span><b><?=date('M j, Y g:ia',strtotime($d['reviewed_at']))?></b></div>
      <?php endif;?>
      <?php if($d['review_note']):?>
        <div><span>Note</span><b><?=html_escape($d['review_note'])?></b></div>
      <?php endif;?>
    </div>
    <?php if($d['status']==='pending'):?>
    <form method="post" action="<?=site_url('admin/kyc-documents/'.$d['id'].'/review')?>" class="check-review-form">
      <label>Reviewer note (required if rejecting)
        <textarea name="note" rows="3" placeholder="Reason for rejection or any notes for the customer."></textarea>
      </label>
      <div class="check-review-actions">
        <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Reject this document? The customer will be notified.');">✕ Reject</button>
        <button type="submit" name="action" value="approve" class="btn btn-success" onclick="return confirm('Approve this document?');">✓ Approve</button>
      </div>
    </form>
    <?php else:?>
      <a class="outline wide" href="<?=site_url('admin/kyc-documents?status='.$d['status'])?>">← Back to <?=$d['status']?> documents</a>
    <?php endif;?>
  </section>
</div>
