<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Banking that moves with you · NorthWest Financial</title>
<?=render_seo_meta('')?>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=base_url('public/css/app.css')?>">
</head>
<body class="home">
<?=render_announcement()?>

<header class="home-nav">
  <div class="home-nav__inner">
    <a class="brand brand-light" href="<?=site_url('/')?>"><i><b></b><b></b><b></b></i>North<span>West</span></a>
    <nav class="home-nav__links" aria-label="Primary">
      <a href="#features">Features</a>
      <a href="#products">Accounts</a>
      <a href="#security">Security</a>
      <a href="#support">Support</a>
    </nav>
    <div class="home-nav__cta">
      <?=render_language_switcher('lang-switch home-lang')?>
      <a class="link-btn" href="<?=site_url('login')?>">Admin</a>
      <a class="btn btn-sm" href="<?=site_url('user/login')?>">Sign in</a>
    </div>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="hero__bg" aria-hidden="true">
    <img src="<?=base_url('public/img/home/hero-skyscrapers.jpg')?>" alt="">
    <div class="hero__overlay"></div>
  </div>
  <div class="hero__inner">
    <div class="hero__copy">
      <em class="eyebrow">✦ NORTHWEST ONLINE BANKING</em>
      <h1>Banking that<br><span>moves with you.</span></h1>
      <p>Open an account in minutes, send money for free between NorthWest members, and manage every dollar from a beautifully secure dashboard — 24/7, on any device.</p>
      <div class="hero__cta">
        <a class="btn btn-lg" href="<?=site_url('register')?>">Open free account ›</a>
        <a class="btn btn-ghost btn-lg" href="<?=site_url('user/login')?>">Sign in</a>
      </div>
      <ul class="hero__badges">
        <li><span>🔒</span> 256-bit encryption</li>
        <li><span>⚡</span> Instant transfers</li>
        <li><span>🛟</span> Human support, 24/7</li>
      </ul>
    </div>
    <div class="hero__card" aria-hidden="true">
      <div class="bank-card">
        <div class="bank-card__chip"></div>
        <div class="bank-card__wave"></div>
        <small>NORTHWEST BLACK</small>
        <strong>•••• •••• •••• 4829</strong>
        <span>CURRENT · 08/31</span>
        <b>NORTHWEST</b>
      </div>
      <div class="hero__stat hero__stat--one">
        <span>Account balance</span>
        <b>$ 24,980<small>.50</small></b>
        <i class="up">▲ 12.4% this month</i>
      </div>
      <div class="hero__stat hero__stat--two">
        <i class="dot"></i>
        <span>Payment received</span>
        <b>+ $1,250.00</b>
      </div>
    </div>
  </div>
</section>

<!-- TRUST BAR -->
<section class="trustbar">
  <div class="trustbar__inner">
    <b>Trusted by <span>180,000+</span> customers</b>
    <div class="trustbar__logos" aria-label="Awards and certifications">
      <span>◆ FDIC Insured</span>
      <span>◉ PCI-DSS</span>
      <span>✦ ISO 27001</span>
      <span>▲ SOC 2 Type II</span>
      <span>★ 4.9 / 5 on Trustpilot</span>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="section" id="features">
  <div class="section__head">
    <em class="eyebrow">WHY NORTHWEST</em>
    <h2>Everything your money needs,<br>in one calm place.</h2>
    <p>Powerful tools wrapped in an interface that is actually easy to love — built for everyday people and serious savers alike.</p>
  </div>
  <div class="features">
    <article class="feature">
      <div class="feature__media"><img src="<?=base_url('public/img/home/mobile-banking.jpg')?>" alt="Customer banking on her phone"></div>
      <div class="feature__body">
        <i>📱</i>
        <h3>Bank from anywhere</h3>
        <p>Check balances, freeze cards, deposit checks and send money from your phone — all in real time, with zero compromises on security.</p>
        <a href="<?=site_url('register')?>">Get the app experience →</a>
      </div>
    </article>
    <article class="feature feature--reverse">
      <div class="feature__media"><img src="<?=base_url('public/img/home/analytics.jpg')?>" alt="Financial analytics dashboard on a laptop"></div>
      <div class="feature__body">
        <i>📊</i>
        <h3>Know where every dollar goes</h3>
        <p>Automatic spending categories, cash-flow forecasts and beautiful charts help you make smarter decisions without spreadsheets.</p>
        <a href="<?=site_url('user/login')?>">See your dashboard →</a>
      </div>
    </article>
    <article class="feature">
      <div class="feature__media"><img src="<?=base_url('public/img/home/advisor.jpg')?>" alt="Couple reviewing plans with a financial advisor"></div>
      <div class="feature__body">
        <i>🤝</i>
        <h3>Real humans, when it matters</h3>
        <p>Chat with our in-site AI assistant in seconds, or reach a qualified advisor by phone, email or secure message — 24 hours a day.</p>
        <a href="<?=site_url('register')?>">Talk to us →</a>
      </div>
    </article>
  </div>
</section>

<!-- STATS -->
<section class="stats-band">
  <div class="stats-band__inner">
    <div><b>$4.2B+</b><span>Processed securely every quarter</span></div>
    <div><b>99.99%</b><span>Platform uptime, audited yearly</span></div>
    <div><b>&lt; 12ms</b><span>Average transaction response</span></div>
    <div><b>24/7</b><span>Fraud monitoring &amp; support</span></div>
  </div>
</section>

<!-- PRODUCTS -->
<section class="section section--alt" id="products">
  <div class="section__head">
    <em class="eyebrow">ACCOUNTS &amp; PRODUCTS</em>
    <h2>Accounts built around your goals.</h2>
    <p>Whether you are saving for a home, running a business or spending abroad — we have an account that fits.</p>
  </div>
  <div class="product-grid">
    <article class="product product--navy">
      <i>🏦</i>
      <h3>Everyday Checking</h3>
      <p>No monthly fees, free NorthWest-to-NorthWest transfers and a contactless debit card delivered to your door.</p>
      <ul>
        <li>✓ No minimum balance</li>
        <li>✓ 40,000+ free ATMs</li>
        <li>✓ Real-time alerts</li>
      </ul>
      <a class="link-arrow" href="<?=site_url('register')?>">Open an account</a>
    </article>
    <article class="product product--teal">
      <i>💰</i>
      <h3>High-Yield Savings</h3>
      <p>Earn one of the most competitive rates in the country with no lock-in and no hidden fees.</p>
      <ul>
        <li>✓ 4.85% APY*</li>
        <li>✓ FDIC insured</li>
        <li>✓ Automated savings tools</li>
      </ul>
      <a class="link-arrow" href="<?=site_url('register')?>">Start saving</a>
    </article>
    <article class="product product--purple">
      <i>💳</i>
      <h3>Premium Credit Card</h3>
      <p>Up to 3% cash back, travel insurance and a sleek metal card. Manage everything straight from the app.</p>
      <ul>
        <li>✓ 3% cash back</li>
        <li>✓ No foreign transaction fees</li>
        <li>✓ Instant card freeze</li>
      </ul>
      <a class="link-arrow" href="<?=site_url('register')?>">Compare cards</a>
    </article>
    <article class="product product--gold">
      <i>📈</i>
      <h3>Loans &amp; Mortgages</h3>
      <p>Transparent rates, fast decisions and flexible repayment — personal loans, auto loans and home financing.</p>
      <ul>
        <li>✓ Decisions in minutes</li>
        <li>✓ Rates from 5.9% APR</li>
        <li>✓ No prepayment penalties</li>
      </ul>
      <a class="link-arrow" href="<?=site_url('register')?>">Check your options</a>
    </article>
  </div>
</section>

<!-- SECURITY -->
<section class="security" id="security">
  <div class="security__inner">
    <div class="security__media">
      <img src="<?=base_url('public/img/home/towers.jpg')?>" alt="Modern glass financial towers">
      <div class="security__badge"><span>🛡️</span><b>Bank-grade</b><small>256-bit TLS · biometric login · zero-trust architecture</small></div>
    </div>
    <div class="security__copy">
      <em class="eyebrow">SECURITY FIRST</em>
      <h2>Your money is protected around the clock.</h2>
      <p>We use the same encryption, monitoring and zero-trust architecture as the world's largest financial institutions — then add a layer of human review on anything unusual.</p>
      <div class="security__grid">
        <div><i>🔐</i><b>256-bit encryption</b><small>Every session, end to end.</small></div>
        <div><i>👁️</i><b>24/7 fraud watch</b><small>AI + human monitoring.</small></div>
        <div><i>📲</i><b>Two-factor auth</b><small>One-time codes on every sign-in.</small></div>
        <div><i>🧊</i><b>FDIC insured</b><small>Deposits protected up to limits.</small></div>
      </div>
      <a class="btn" href="<?=site_url('register')?>">Open your secure account</a>
    </div>
  </div>
</section>

<!-- APP CTA -->
<section class="app-cta">
  <div class="app-cta__inner">
    <div class="app-cta__copy">
      <em class="eyebrow eyebrow--light">THE NORTHWEST APP</em>
      <h2>Your branch in your pocket.</h2>
      <p>Pay bills, send money overseas, freeze a misplaced card, deposit a check by photo and get instant support — all from the app you will actually enjoy opening.</p>
      <div class="app-cta__buttons">
        <a href="<?=site_url('register')?>" class="store-btn"><span>🍎</span><div><small>Download on the</small><b>App Store</b></div></a>
        <a href="<?=site_url('register')?>" class="store-btn"><span>▶</span><div><small>Get it on</small><b>Google Play</b></div></a>
      </div>
    </div>
    <div class="app-cta__media">
      <img src="<?=base_url('public/img/home/mobile-pay.jpg')?>" alt="Customer paying with her phone and NorthWest card">
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section" id="support">
  <div class="section__head">
    <em class="eyebrow">CUSTOMER STORIES</em>
    <h2>Loved by people who<br>refuse to settle for boring banking.</h2>
  </div>
  <div class="quotes">
    <figure class="quote">
      <div class="quote__stars">★★★★★</div>
      <blockquote>"I opened an account in under five minutes. A year later it is still the only banking app I actually look forward to opening."</blockquote>
      <figcaption><b>Maya R.</b><small>Designer · Zürich</small></figcaption>
    </figure>
    <figure class="quote">
      <div class="quote__stars">★★★★★</div>
      <blockquote>"The savings tools helped me put aside enough for a down payment without ever feeling the pinch. Genuinely brilliant."</blockquote>
      <figcaption><b>Daniel K.</b><small>Engineer · London</small></figcaption>
    </figure>
    <figure class="quote">
      <div class="quote__stars">★★★★★</div>
      <blockquote>"Someone called me within minutes when my card was used abroad. That is the kind of care you just do not see anymore."</blockquote>
      <figcaption><b>Aïcha B.</b><small>Founder · Montréal</small></figcaption>
    </figure>
  </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta">
  <div class="final-cta__inner">
    <h2>Ready to bank<br>the way you live?</h2>
    <p>Join 180,000+ people who manage their money with NorthWest. No monthly fees, no paperwork, no nonsense.</p>
    <div class="hero__cta">
      <a class="btn btn-lg" href="<?=site_url('register')?>">Open free account ›</a>
      <a class="btn btn-ghost btn-lg" href="<?=site_url('user/login')?>">I already have an account</a>
    </div>
  </div>
</section>

<footer class="home-foot">
  <div class="home-foot__inner">
    <div class="home-foot__brand">
      <a class="brand brand-light" href="<?=site_url('/')?>"><i><b></b><b></b><b></b></i>North<span>West</span></a>
      <p>Banking that moves with you — secure, human and designed around your life.</p>
    </div>
    <div class="home-foot__cols">
      <div><h4>Banking</h4><a href="<?=site_url('register')?>">Checking</a><a href="<?=site_url('register')?>">Savings</a><a href="<?=site_url('register')?>">Credit cards</a><a href="<?=site_url('register')?>">Loans</a></div>
      <div><h4>Company</h4><a href="#features">About us</a><a href="#security">Security</a><a href="#support">Careers</a><a href="<?=site_url('login')?>">Staff login</a></div>
      <div><h4>Support</h4><a href="<?=site_url('user/login')?>">Sign in</a><a href="<?=site_url('register')?>">Open account</a><a href="#support">Help center</a><a href="#support">Contact</a></div>
    </div>
  </div>
  <div class="home-foot__bottom">
    <span>© 2026 NorthWest Financial Ltd. All rights reserved.</span>
    <span>Privacy · Terms · Cookies · Security</span>
  </div>
</footer>

<?=render_chat_widget()?>
</body>
</html>
