<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Shared layout for public marketing / content pages.
 * Expects: $title (string), $seo_desc (string), $active (string), $content (HTML string).
 */
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=html_escape($title ?? 'NorthWest Financial')?> · NorthWest Financial</title>
<meta name="description" content="<?=html_escape($seo_desc ?? '')?>">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=base_url('public/css/app.css')?>">
</head>
<body class="home public-page">
<?=render_announcement()?>

<header class="home-nav home-nav--solid">
  <div class="home-nav__inner">
    <a class="brand" href="<?=site_url('/')?>"><i><b></b><b></b><b></b></i>North<span>West</span></a>
    <nav class="home-nav__links" aria-label="Primary">
      <a href="<?=site_url('products')?>" class="<?=($active??'')==='products'?'is-active':''?>">Accounts</a>
      <a href="<?=site_url('borrow')?>" class="<?=($active??'')==='loans'?'is-active':''?>">Loans</a>
      <a href="<?=site_url('cards-public')?>" class="<?=($active??'')==='cards'?'is-active':''?>">Cards</a>
      <a href="<?=site_url('calculator')?>" class="<?=($active??'')==='calculator'?'is-active':''?>">Calculator</a>
      <a href="<?=site_url('about')?>" class="<?=($active??'')==='about'?'is-active':''?>">About</a>
      <a href="<?=site_url('help')?>" class="<?=($active??'')==='help'?'is-active':''?>">Help</a>
      <a href="<?=site_url('contact')?>" class="<?=($active||'')==='contact'?'is-active':''?>">Contact</a>
    </nav>
    <div class="home-nav__cta">
      <?=render_language_switcher('lang-switch home-lang')?>
      <a class="link-btn" href="<?=site_url('login')?>">Admin</a>
      <a class="btn btn-sm" href="<?=site_url('user/login')?>">Sign in</a>
    </div>
  </div>
</header>

<main class="public-main">
<?=$content?>
</main>

<footer class="home-foot">
  <div class="home-foot__inner">
    <div class="home-foot__brand">
      <a class="brand brand-light" href="<?=site_url('/')?>"><i><b></b><b></b><b></b></i>North<span>West</span></a>
      <p>Banking that moves with you — secure, human and designed around your life.</p>
    </div>
    <div class="home-foot__cols">
      <div><h4>Banking</h4>
        <a href="<?=site_url('products')?>">Checking</a>
        <a href="<?=site_url('products')?>">Savings</a>
        <a href="<?=site_url('cards-public')?>">Credit cards</a>
        <a href="<?=site_url('borrow')?>">Loans &amp; mortgages</a>
        <a href="<?=site_url('calculator')?>">Loan calculator</a>
      </div>
      <div><h4>Company</h4>
        <a href="<?=site_url('about')?>">About us</a>
        <a href="<?=site_url('security-center')?>">Security</a>
        <a href="<?=site_url('branches')?>">Branches &amp; ATMs</a>
        <a href="<?=site_url('fees')?>">Fees &amp; pricing</a>
        <a href="<?=site_url('login')?>">Staff login</a>
      </div>
      <div><h4>Support</h4>
        <a href="<?=site_url('help')?>">Help center / FAQ</a>
        <a href="<?=site_url('contact')?>">Contact us</a>
        <a href="<?=site_url('user/login')?>">Sign in</a>
        <a href="<?=site_url('register')?>">Open account</a>
      </div>
      <div><h4>Legal</h4>
        <a href="<?=site_url('privacy')?>">Privacy policy</a>
        <a href="<?=site_url('terms')?>">Terms &amp; conditions</a>
        <a href="<?=site_url('security-center')?>">Fraud &amp; security</a>
      </div>
    </div>
  </div>
  <div class="home-foot__bottom">
    <span>© 2026 NorthWest Financial Ltd. All rights reserved.</span>
    <span>Member FDIC · Equal Housing Lender · Privacy · Terms · Cookies</span>
  </div>
</footer>

<?=render_chat_widget()?>
</body>
</html>
