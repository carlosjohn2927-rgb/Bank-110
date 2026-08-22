<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="page-title">
  <div>
    <em>SECURITY</em>
    <h1>Your backup codes</h1>
    <p>Save these codes somewhere safe. Each can be used once if you lose your authenticator app.</p>
  </div>
</div>

<section class="panel backup-codes">
  <div class="backup-codes__head">
    <b>🔑 Keep these somewhere safe</b>
    <span>8 one-time codes · you won't see them again</span>
  </div>
  <div class="backup-codes__grid">
    <?php foreach ($codes as $i => $code): ?>
      <code><?=html_escape($code)?></code>
    <?php endforeach; ?>
  </div>
  <div class="backup-codes__actions">
    <button type="button" class="outline" id="print-codes" onclick="window.print()">🖨️ Print</button>
    <button type="button" class="outline" id="copy-codes" data-codes="<?=html_escape(implode("\n",$codes))?>">📋 Copy all</button>
    <a class="btn" href="<?=site_url('settings')?>">I've saved them — done</a>
  </div>
  <p class="backup-codes__warn">If you lose access to your authenticator app and don't have these codes, you'll need to contact support to regain access. <a href="<?=site_url('contact')?>">Contact support →</a></p>
</section>

<script>
document.getElementById('copy-codes')?.addEventListener('click',function(){
  var codes=this.dataset.codes;
  if(navigator.clipboard){navigator.clipboard.writeText(codes);this.textContent='Copied ✓';var b=this;setTimeout(function(){b.textContent='📋 Copy all';},2000);}
});
</script>
