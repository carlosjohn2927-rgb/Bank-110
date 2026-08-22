<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="page-hero page-hero--help">
  <div class="page-hero__inner">
    <em class="eyebrow">HELP CENTER</em>
    <h1>How can we help?</h1>
    <p>Answers to the questions customers ask most. Can't find what you need? Our support team is available 24/7.</p>
    <form class="help-search" method="get" action="<?=site_url('support')?>" onsubmit="return false;">
      <input type="text" placeholder="Search for an answer, e.g. 'how do I freeze my card'" aria-label="Search help">
      <button class="btn">Search</button>
    </form>
  </div>
</section>

<section class="prose-section">
  <div class="help-cats">
    <a href="#account"><i>▣</i><span>Your account</span></a>
    <a href="#cards"><i>▤</i><span>Cards</span></a>
    <a href="#transfers"><i>↗</i><span>Transfers</span></a>
    <a href="#loans"><i>▥</i><span>Loans</span></a>
    <a href="#security"><i>🔒</i><span>Security</span></a>
    <a href="#app"><i>📱</i><span>Mobile app</span></a>
  </div>
</section>

<?php
$faqs = array(
  'account'  => array('Your account', array(
    'How do I open an account?' => 'Tap "Open free account" on the homepage, complete the short application (about 5 minutes) and verify your identity. Most customers are approved instantly and can start banking right away.',
    'What documents do I need?' => 'A government-issued photo ID (passport, driver\'s licence or national ID) and, for some accounts, proof of address. We accept photos taken with your phone.',
    'Is there a minimum balance?' => 'No. Our standard checking and savings accounts have no minimum balance and no monthly fees.',
    'How do I close my account?' => 'There is no fee and no penalty. Contact support and we will close it for you, usually the same day.',
  )),
  'cards'    => array('Cards', array(
    'How do I freeze my card?' => 'Open the app, go to Cards, select the card and tap "Freeze". Purchases are blocked instantly. Tap again to unfreeze.',
    'What do I do if my card is lost or stolen?' => 'Freeze it immediately in the app, then use "Report lost card". We will cancel it and send a replacement by post, usually within 3–5 business days.',
    'Can I use my card abroad?' => 'Yes. International use is enabled by default on most cards; you can toggle it anytime. Premium cards charge no foreign transaction fees.',
    'How do I set my daily spending limit?' => 'In Cards → Card controls you can set daily spending, ATM withdrawal and online purchase limits in real time.',
  )),
  'transfers' => array('Transfers & payments', array(
    'How long do transfers take?' => 'NorthWest-to-NorthWest transfers are instant. Domestic transfers arrive the same business day; international transfers typically take 1–2 business days.',
    'Are transfers free?' => 'Transfers between NorthWest accounts and standard domestic transfers are free. See our Fees page for international wire pricing.',
    'Can I schedule a future payment?' => 'Yes — choose a date when setting up the transfer and we will process it automatically on that day.',
    'How do I add a beneficiary?' => 'Go to Transfer → Beneficiaries → Add. You will need their name, account number and bank name.',
  )),
  'loans'    => array('Loans & mortgages', array(
    'How fast will I get a decision?' => 'Most personal loan applications are decided in under two minutes. Mortgages and larger loans may require additional documents.',
    'What rate will I get?' => 'Your rate depends on your credit profile and the loan term. Use our loan calculator to estimate your monthly payment before applying.',
    'Can I pay off my loan early?' => 'Yes, at any time, with no prepayment penalty or fee. You only pay interest up to the date you repay.',
  )),
  'security' => array('Security & fraud', array(
    'How do I turn on two-factor authentication?' => 'Go to Settings → Security and follow the prompts to enable 2FA. We recommend using an authenticator app.',
    'I think someone accessed my account — what now?' => 'Change your password immediately, sign out all devices in Settings, then call our 24/7 fraud line at 1-800-NW-SECURE.',
    'Will NorthWest ever ask for my password or code?' => 'No. We will never call, email or message you asking for your password, PIN or one-time code. Forward suspicious messages to fraud@northwest.example.',
  )),
  'app'      => array('Mobile app', array(
    'Which devices are supported?' => 'The NorthWest app runs on iOS 15+ and Android 10+. You can also bank from any modern web browser.',
    'Can I deposit a check with my phone?' => 'Yes. In the app choose Deposit, photograph the front and back of the endorsed check, and enter the amount.',
    'Does the app work offline?' => 'You can view recently loaded balances and transactions offline. Payments and transfers require an internet connection.',
  )),
);
?>
<section class="faq-section">
<?php foreach ($faqs as $id => $group): ?>
  <div class="faq-group" id="<?=html_escape($id)?>">
    <h2><?=html_escape($group[0])?></h2>
    <div class="faq-list">
    <?php foreach ($group[1] as $q => $a): ?>
      <details class="faq-item">
        <summary><?=html_escape($q)?><span class="faq-toggle" aria-hidden="true"></span></summary>
        <p><?=html_escape($a)?></p>
      </details>
    <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
</section>

<section class="final-cta">
  <div class="final-cta__inner">
    <h2>Still need help?</h2>
    <p>Our human support team is available around the clock — by phone, secure message or in branch.</p>
    <div class="hero__cta">
      <a class="btn btn-lg" href="<?=site_url('contact')?>">Contact support</a>
      <a class="btn btn-ghost btn-lg" href="<?=site_url('user/login')?>">Sign in to your account</a>
    </div>
  </div>
</section>
