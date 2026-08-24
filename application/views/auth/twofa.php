<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify sign-in · NorthWest Financial</title>
<?=render_seo_meta('Verify sign-in')?>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="icon" href="<?=base_url('public/favicon.svg')?>" type="image/svg+xml">
<link rel="alternate icon" href="<?=base_url('public/favicon.ico')?>" sizes="any">
<link rel="manifest" href="<?=base_url('public/site.webmanifest')?>">
<link rel="apple-touch-icon" href="<?=base_url('public/favicon.png')?>">
<link rel="stylesheet" href="<?=base_url('public/css/app.css')?>">
</head>
<body class="auth">
<?=render_announcement()?>
<section>
  <div class="auth-top">
    <?=render_language_switcher('lang-switch auth-lang')?>
    <a class="brand" href="<?=site_url('user/login')?>"><i><b></b><b></b><b></b></i>North<span>West</span></a>
    <a href="<?=site_url('user/login')?>">Back to sign in ›</a>
  </div>
  <div class="auth-card">
    <em>TWO-FACTOR AUTHENTICATION</em>
    <h1>Enter your code</h1>
    <?php if ($method === 'totp'): ?>
      <p>Open your authenticator app and enter the 6-digit code for <b>NorthWest</b>. Codes refresh every 30 seconds.</p>
      <?=form_open('twofa')?>
        <label>Authentication code</label>
        <input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code" placeholder="000000" class="totp-code-input">
        <?php if($this->session->flashdata('success')):?><div class="alert success">✓ <?=$this->session->flashdata('success')?></div><?php endif?>
        <?php if($this->session->flashdata('error')):?><div class="form-error"><?=$this->session->flashdata('error')?></div><?php endif?>
        <button class="btn wide">Verify and continue ›</button>
      </form>
      <details class="twofa-alt">
        <summary>Can't access your app? Use an email code or backup code</summary>
        <p>Enter one of your 8 backup codes, or we can email a one-time code to <b><?=html_escape($masked_email)?></b>.</p>
        <a class="forgot-link" href="<?=site_url('twofa/resend')?>">Email me a sign-in code</a>
      </details>
    <?php else: ?>
      <p>We've sent a 6-digit verification code to <b><?=html_escape($masked_email)?></b>. It expires in 5 minutes.</p>
      <?=form_open('twofa')?>
        <label>Verification code</label>
        <input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus placeholder="000000" class="totp-code-input">
        <?php if($this->session->flashdata('success')):?><div class="alert success">✓ <?=$this->session->flashdata('success')?></div><?php endif?>
        <?php if($this->session->flashdata('error')):?><div class="form-error"><?=$this->session->flashdata('error')?></div><?php endif?>
        <button class="btn wide">Verify and continue ›</button>
      </form>
      <a class="forgot-link" href="<?=site_url('twofa/resend')?>">Resend code</a>
    <?php endif; ?>
  </div>
  <footer>© 2026 NorthWest Financial Ltd.</footer>
</section>
<aside class="auth-image">
  <div>
    <span>✓ Extra security</span>
    <h2>An extra layer<br>of protection.</h2>
    <p>Two-factor authentication helps keep your account safe even if your password is ever compromised.</p>
    <hr>
    <b>6-digit<small> code</small></b>
    <b>5-min<small> expiry (email)</small></b>
    <b>2FA<small> protected</small></b>
  </div>
</aside>
<?=render_chat_widget()?>
</body>
</html>
