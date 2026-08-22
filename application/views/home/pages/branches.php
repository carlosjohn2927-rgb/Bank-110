<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="page-hero page-hero--branches">
  <div class="page-hero__inner">
    <em class="eyebrow">BRANCHES &amp; ATMS</em>
    <h1>Find a NorthWest<br>near you.</h1>
    <p>We're a digital-first bank, but we also believe in the value of a face-to-face conversation. Visit one of our flagship branches or use any of 40,000+ surcharge-free ATMs worldwide.</p>
  </div>
</section>

<section class="branch-finder">
  <div class="branch-finder__inner">
    <form class="branch-search" method="get" onsubmit="return false;">
      <input type="text" placeholder="Enter city, ZIP or address" aria-label="Search by location">
      <button class="btn">Search</button>
    </form>

    <div class="branch-map" role="img" aria-label="Illustrative map showing NorthWest branch locations">
      <span class="pin pin-1"></span>
      <span class="pin pin-2"></span>
      <span class="pin pin-3"></span>
      <span class="pin pin-4"></span>
      <div class="branch-map__legend"><i></i> NorthWest branch</div>
    </div>

    <div class="branch-list">
      <article class="branch-card">
        <h3>Zürich · HQ Flagship</h3>
        <p>Bahnhofstrasse 42, 8001 Zürich</p>
        <ul><li>Mon–Fri 9:00–18:00</li><li>Sat 10:00–15:00</li><li>24-hour ATM</li></ul>
        <a class="link-arrow" href="<?=site_url('contact')?>">Book an appointment</a>
      </article>
      <article class="branch-card">
        <h3>London · Canary Wharf</h3>
        <p>1 Canada Square, London E14 5AB</p>
        <ul><li>Mon–Fri 9:00–17:30</li><li>Sat 10:00–14:00</li><li>24-hour ATM</li></ul>
        <a class="link-arrow" href="<?=site_url('contact')?>">Book an appointment</a>
      </article>
      <article class="branch-card">
        <h3>New York · Manhattan</h3>
        <p>350 Park Avenue, New York, NY 10022</p>
        <ul><li>Mon–Fri 8:30–17:00</li><li>Sat 10:00–13:00</li><li>24-hour ATM</li></ul>
        <a class="link-arrow" href="<?=site_url('contact')?>">Book an appointment</a>
      </article>
      <article class="branch-card">
        <h3>Montréal · Centre-ville</h3>
        <p>1201 Boulevard Robert-Bourassa, Montréal QC</p>
        <ul><li>Mon–Fri 9:00–18:00</li><li>Sat closed</li><li>24-hour ATM</li></ul>
        <a class="link-arrow" href="<?=site_url('contact')?>">Book an appointment</a>
      </article>
    </div>
  </div>
</section>

<section class="prose-section section--alt">
  <div class="prose-grid">
    <div><h2>40,000+ free ATMs</h2><p>Withdraw cash without fees at any NorthWest or partner ATM worldwide. Premium account holders also get out-of-network fees fully rebated, everywhere.</p></div>
    <div><h2>Book ahead, skip the line</h2><p>Reserve a time with a specialist for mortgages, business accounts or financial planning — we'll be ready when you arrive.</p></div>
  </div>
</section>

<section class="final-cta">
  <div class="final-cta__inner">
    <h2>Banking in person,<br>and everywhere else.</h2>
    <p>Open an account online in minutes, then visit a branch whenever you'd like to talk to a human.</p>
    <div class="hero__cta">
      <a class="btn btn-lg" href="<?=site_url('register')?>">Open free account ›</a>
      <a class="btn btn-ghost btn-lg" href="<?=site_url('contact')?>">Talk to us</a>
    </div>
  </div>
</section>
