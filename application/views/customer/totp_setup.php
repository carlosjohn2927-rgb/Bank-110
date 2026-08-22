<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="page-title">
  <div>
    <em>SECURITY</em>
    <h1>Set up an authenticator app</h1>
    <p>Scan the QR code with Google Authenticator, Authy, 1Password or any TOTP app, then enter the 6-digit code below.</p>
  </div>
  <a class="outline" href="<?=site_url('settings')?>">Cancel</a>
</div>

<form method="post" action="<?=site_url('settings/twofa/confirm')?>" class="totp-setup">
  <div class="grid two">
    <section class="panel totp-qr-panel">
      <div class="panel-head"><div><h2>1. Scan this code</h2><p>With your authenticator app</p></div></div>
      <div class="totp-qr">
        <?=$qr?>
      </div>
      <details class="totp-manual">
        <summary>Can't scan? Enter this key manually</summary>
        <div class="totp-secret">
          <code id="totp-secret"><?=html_escape($secret_chunked)?></code>
          <button type="button" class="outline" id="copy-secret" data-secret="<?=html_escape($secret)?>">Copy</button>
        </div>
        <p class="totp-hint">In your app choose "Enter setup key" and paste: <b id="totp-secret-raw"><?=html_escape($secret)?></b></p>
      </details>
    </section>

    <section class="panel">
      <div class="panel-head"><div><h2>2. Verify the code</h2><p>Enter the 6-digit code from your app</p></div></div>
      <div class="totp-verify">
        <label for="code">Authentication code</label>
        <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus required placeholder="000000" class="totp-code-input">
        <p class="totp-timing">Codes rotate every 30 seconds. We accept codes from one step before or after to handle clock drift.</p>
        <button class="btn wide">Enable authenticator app</button>
        <p class="totp-next">After confirming you'll receive 8 one-time backup codes — save them somewhere safe; they're shown only once.</p>
      </div>
    </section>
  </div>
</form>

<script>
document.getElementById('copy-secret')?.addEventListener('click',function(){
  var s=this.dataset.secret;navigator.clipboard&&navigator.clipboard.writeText(s);
  this.textContent='Copied ✓';var b=this;setTimeout(function(){b.textContent='Copy';},2000);
});
</script>
