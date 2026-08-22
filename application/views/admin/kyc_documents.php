<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="page-title">
  <div><em>COMPLIANCE</em><h1>KYC review</h1><p>Review and approve customer identity documents.</p></div>
</div>
<div class="check-tabs" role="tablist">
  <a href="<?=site_url('admin/kyc-documents?status=pending')?>" class="check-tab <?=$status==='pending'?'is-active':''?>">Pending <b><?=$counts['pending']?></b></a>
  <a href="<?=site_url('admin/kyc-documents?status=approved')?>" class="check-tab <?=$status==='approved'?'is-active':''?>">Approved <b><?=$counts['approved']?></b></a>
  <a href="<?=site_url('admin/kyc-documents?status=rejected')?>" class="check-tab <?=$status==='rejected'?'is-active':''?>">Rejected <b><?=$counts['rejected']?></b></a>
</div>
<section class="panel">
<?php if(!$documents):?>
  <div class="empty"><h3>No <?=$status?> documents</h3><p>Identity documents with this status will appear here.</p></div>
<?php else:?>
  <div class="admin-kyc-list">
    <?php foreach($documents as $d):
      $isImg = strpos($d['mime_type'] ?? '', 'image/')===0;
    ?>
      <a class="admin-kyc-row" href="<?=site_url('admin/kyc-documents/'.$d['id'])?>">
        <div class="admin-kyc-row__thumb">
          <?php if($isImg):?><img src="<?=site_url('kyc/download/'.$d['id'])?>" alt=""><?php else:?><span>📄</span><?php endif;?>
        </div>
        <div class="admin-kyc-row__body">
          <b><?=html_escape($d['first_name'].' '.$d['last_name'])?></b>
          <small><?=html_escape($d['email'])?> · <?=ucfirst(str_replace('_',' ',$d['doc_type']))?> · <?=date('M j, Y g:ia',strtotime($d['created_at']))?></small>
          <?php if($d['review_note']):?><small class="note">⚠️ <?=html_escape($d['review_note'])?></small><?php endif;?>
        </div>
        <span class="deposit-status deposit-status--<?=$d['status']?>"><?=ucfirst($d['status'])?></span>
        <span class="admin-check-row__chevron">›</span>
      </a>
    <?php endforeach;?>
  </div>
  <?php if($pagination):?><div class="pager-wrap"><?=$pagination?></div><?php endif;?>
<?php endif;?>
</section>
