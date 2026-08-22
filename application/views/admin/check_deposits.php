<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="page-title">
  <div>
    <em>OPERATIONS</em>
    <h1>Check deposits</h1>
    <p>Review and approve mobile check deposits submitted by customers.</p>
  </div>
</div>

<div class="check-tabs" role="tablist">
  <a href="<?=site_url('admin/check-deposits?status=pending')?>" class="check-tab <?=$status==='pending'?'is-active':''?>">
    Pending <b><?=$counts['pending']?></b>
  </a>
  <a href="<?=site_url('admin/check-deposits?status=approved')?>" class="check-tab <?=$status==='approved'?'is-active':''?>">
    Approved <b><?=$counts['approved']?></b>
  </a>
  <a href="<?=site_url('admin/check-deposits?status=rejected')?>" class="check-tab <?=$status==='rejected'?'is-active':''?>">
    Rejected <b><?=$counts['rejected']?></b>
  </a>
</div>

<section class="panel">
  <?php if(!$deposits): ?>
    <div class="empty">
      <h3 style="margin:0 0 6px">No <?=$status?> deposits</h3>
      <p>Check deposits with this status will appear here.</p>
    </div>
  <?php else: ?>
  <div class="admin-check-list">
    <?php foreach($deposits as $d): ?>
    <a href="<?=site_url('admin/check-deposits/'.$d['id'])?>" class="admin-check-row">
      <div class="admin-check-row__thumbs">
        <img src="<?=base_url($d['front_image_path'])?>" alt="Front" loading="lazy">
        <img src="<?=base_url($d['back_image_path'])?>" alt="Back" loading="lazy">
      </div>
      <div class="admin-check-row__body">
        <b><?=html_escape($d['first_name'].' '.$d['last_name'])?></b>
        <small><?=html_escape($d['email'])?> • <?=html_escape($d['reference'])?> • <?=$d['account_name']?> ••••<?=substr($d['account_number'],-4)?></small>
        <small><?=date('M j, Y g:ia',strtotime($d['created_at']))?><?=!empty($d['check_number'])?' • Check #'.html_escape($d['check_number']):''?></small>
      </div>
      <div class="admin-check-row__amount"><?=money($d['amount'],$d['currency'])?></div>
      <span class="deposit-status deposit-status--<?=$d['status']?>"><?=ucfirst($d['status'])?></span>
      <span class="admin-check-row__chevron">›</span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if($pagination): ?><div class="pager-wrap"><?=$pagination?></div><?php endif; ?>
  <?php endif; ?>
</section>
