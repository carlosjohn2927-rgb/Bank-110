<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign in · NorthWest Financial</title>
<?=render_seo_meta('Sign in')?>
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
    <a class="brand" href="<?=site_url('/')?>"><i><b></b><b></b><b></b></i>North<span>West</span></a>
    <a href="<?=site_url('/')?>">← Back to home</a>
  </div>
  <div class="auth-card">
    <em>SECURE ACCESS</em>
    <h1>Welcome back</h1>
    <p>Enter your credentials to continue.</p>
    <?=form_open('login')?>
      <label>Email or username</label>
      <input name="identity" required autofocus placeholder="Enter email or username" autocomplete="username">
      <label>Password</label>
      <input name="password" type="password" required placeholder="Enter password" autocomplete="current-password">
      <?php if($this->session->flashdata('error')):?><div class="form-error"><?=$this->session->flashdata('error')?></div><?php endif?>
      <button class="btn wide">Sign in securely ›</button>
    </form>
    <div class="security-note">✓ <span><b>Secure access</b><br>All actions are securely audited.</span></div>
  </div>
  <footer>© 2026 NorthWest Financial Ltd. · <a href="<?=site_url('/')?>">Home</a></footer>
</section>
<aside class="auth-image admin-image">
  <div>
    <span>✓ Operations secured</span>
    <h2>Banking control.<br>One clear view.</h2>
    <p>Manage financial operations through a protected environment.</p>
  </div>
</aside>
<?=render_chat_widget()?>
</body>
</html>
