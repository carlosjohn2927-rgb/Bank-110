<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="page-hero page-hero--contact">
  <div class="page-hero__inner">
    <em class="eyebrow">CONTACT US</em>
    <h1>We're here,<br>24 hours a day.</h1>
    <p>Talk to a real person whenever you need to — by phone, secure message, email or in a branch. There are no robots, no scripts and no holding music for hours.</p>
  </div>
</section>

<section class="contact-grid">
  <div class="contact-cards">
    <article class="contact-card">
      <i>📞</i>
      <h3>Phone</h3>
      <p>Speak to someone right away, any time of day or night.</p>
      <a href="tel:+18006972879"><b>1-800-NW-SUPPORT</b></a>
      <small>Outside the US: +1 212 555 0142 (collect calls accepted)</small>
    </article>
    <article class="contact-card">
      <i>💬</i>
      <h3>Secure message</h3>
      <p>For account-specific questions, the fastest and safest route is to message us from inside online banking.</p>
      <a class="btn" href="<?=site_url('user/login')?>">Sign in to message</a>
    </article>
    <article class="contact-card">
      <i>✉️</i>
      <h3>Email</h3>
      <p>General enquiries and non-urgent questions. We reply within one business day.</p>
      <a href="mailto:hello@northwest.example"><b>hello@northwest.example</b></a>
      <small>Fraud: fraud@northwest.example · Press: press@northwest.example</small>
    </article>
    <article class="contact-card">
      <i>🏛️</i>
      <h3>Visit a branch</h3>
      <p>Book an appointment with a specialist at one of our flagship branches.</p>
      <a class="btn" href="<?=site_url('branches')?>">Find branches &amp; ATMs</a>
    </article>
  </div>

  <div class="contact-form-wrap panel">
    <h2>Send us a message</h2>
    <p>Leave your details and we'll get back to you — usually within a few hours.</p>
    <form class="contact-form" onsubmit="return false;">
      <div class="form-grid">
        <label>First name<input type="text" required placeholder="Jane"></label>
        <label>Last name<input type="text" required placeholder="Doe"></label>
      </div>
      <label>Email address<input type="email" required placeholder="you@example.com"></label>
      <label>Subject
        <select required>
          <option value="">Choose a topic…</option>
          <option>Opening an account</option>
          <option>Cards</option>
          <option>Transfers &amp; payments</option>
          <option>Loans &amp; mortgages</option>
          <option>Technical support</option>
          <option>Something else</option>
        </select>
      </label>
      <label>Message<textarea rows="5" required placeholder="How can we help?"></textarea></label>
      <button class="btn wide">Send message</button>
      <p class="contact-form__note">By sending this message you agree to our <a href="<?=site_url('privacy')?>">privacy policy</a>. Never include your full card number, password or one-time code.</p>
    </form>
  </div>
</section>

<section class="prose-section section--alt">
  <div class="prose-grid">
    <div><h2>Mailing address</h2><p>NorthWest Financial Ltd.<br>Bahnhofstrasse 42<br>8001 Zürich, Switzerland</p></div>
    <div><h2>Regulatory &amp; complaints</h2><p>If you are unhappy with any part of our service, email <a href="mailto:complaints@northwest.example">complaints@northwest.example</a>. We acknowledge every complaint within 24 hours and aim to resolve it within 15 business days.</p></div>
  </div>
</section>
