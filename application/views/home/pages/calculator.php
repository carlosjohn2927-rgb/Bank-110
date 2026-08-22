<?php defined('BASEPATH') OR exit('No direct script access allowed');
$r = $result;
?>
<section class="page-hero page-hero--calc">
  <div class="page-hero__inner">
    <em class="eyebrow">LOAN CALCULATOR</em>
    <h1>Know your payment<br>before you apply.</h1>
    <p>Adjust the amount, rate and term to see your monthly payment, total interest and a year of amortization — instantly, no sign-up required.</p>
  </div>
</section>

<section class="calc-section">
  <form class="calc-form panel" method="get" action="<?=site_url('calculator')?>">
    <h2>Loan details</h2>
    <label class="calc-field">
      <span>Loan type</span>
      <select name="type">
        <option value="personal" <?=($type==='personal')?'selected':''?>>Personal loan</option>
        <option value="auto" <?=($type==='auto')?'selected':''?>>Auto loan</option>
        <option value="mortgage" <?=($type==='mortgage')?'selected':''?>>Mortgage</option>
      </select>
    </label>
    <label class="calc-field">
      <span>Loan amount — <b id="amountLabel">$<?=number_format($amount,0)?></b></span>
      <input type="range" name="amount" id="amount" min="1000" max="500000" step="500" value="<?=html_escape($amount)?>">
    </label>
    <label class="calc-field">
      <span>Interest rate (APR) — <b id="rateLabel"><?=number_format($rate,2)?>%</b></span>
      <input type="range" name="rate" id="rate" min="1" max="30" step="0.05" value="<?=html_escape($rate)?>">
    </label>
    <label class="calc-field">
      <span>Term — <b id="termLabel"><?=(int)$term?> months</b></span>
      <input type="range" name="term" id="term" min="6" max="360" step="1" value="<?=html_escape($term)?>">
    </label>
    <button class="btn wide" type="submit">Calculate</button>
  </form>

  <div class="calc-result panel">
    <?php if ($r): ?>
    <div class="calc-result__hero">
      <span>Estimated monthly payment</span>
      <b>$ <?=number_format($r['payment'],2)?></b>
    </div>
    <div class="calc-result__totals">
      <div><small>Total interest</small><b>$ <?=number_format($r['interest'],2)?></b></div>
      <div><small>Total repaid</small><b>$ <?=number_format($r['total'],2)?></b></div>
    </div>
    <h3>First-year amortization</h3>
    <div class="amort-table-wrap">
    <table class="amort-table">
      <thead><tr><th>Month</th><th>Payment</th><th>Principal</th><th>Interest</th><th>Balance</th></tr></thead>
      <tbody>
      <?php foreach ($r['schedule'] as $row): ?>
        <tr>
          <td><?=$row['month']?></td>
          <td>$<?=number_format($row['payment'],2)?></td>
          <td>$<?=number_format($row['principal'],2)?></td>
          <td>$<?=number_format($row['interest'],2)?></td>
          <td>$<?=number_format($row['balance'],2)?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php if ($term > 12): ?><p class="calc-note">Showing the first 12 of <?=(int)$term?> months for brevity.</p><?php endif; ?>
    <a class="btn wide" href="<?=site_url('register')?>">Apply for this loan ›</a>
    <?php else: ?>
    <div class="calc-result__hero">
      <span>Estimated monthly payment</span>
      <b>—</b>
    </div>
    <p class="calc-note">Use the sliders and press Calculate to see your estimated payment and amortization.</p>
    <?php endif; ?>
  </div>
</section>

<section class="prose-section section--alt">
  <div class="prose-grid">
    <div><h2>How is the payment calculated?</h2><p>We use the standard amortizing-loan formula: payment = P · r(1+r)<sup>n</sup> / ((1+r)<sup>n</sup> − 1), where P is the principal, r the monthly rate (APR ÷ 12), and n the number of months. This is the same formula lenders use.</p></div>
    <div><h2>This is an estimate</h2><p>Your actual rate and payment depend on your credit profile, loan purpose and term. Check your rate with no impact to your credit score by applying — it takes about two minutes.</p></div>
  </div>
</section>

<script>
(function(){
  var a=document.getElementById('amount'),r=document.getElementById('rate'),t=document.getElementById('term');
  var al=document.getElementById('amountLabel'),rl=document.getElementById('rateLabel'),tl=document.getElementById('termLabel');
  function fmt(n){return '$'+Number(n).toLocaleString(undefined,{maximumFractionDigits:0});}
  if(a)a.addEventListener('input',function(){al.textContent=fmt(a.value);});
  if(r)r.addEventListener('input',function(){rl.textContent=Number(r.value).toFixed(2)+'%';});
  if(t)t.addEventListener('input',function(){tl.textContent=t.value+' months';});
})();
</script>
