<div class="page-title"><div><em>MULTI-CURRENCY</em><h1><?=tl('exchange_title')?></h1><p><?=tl('exchange_sub')?></p></div></div>

<div class="grid two exchange-grid">
  <!-- Convert form -->
  <section class="panel form-panel">
    <div class="panel-head"><div><h2>Convert money</h2><p>Real-time rates between your accounts</p></div></div>
    <?=form_open('exchange')?>
      <div class="fx-accounts">
        <div>
          <label>From account</label>
          <select name="from_account_id" id="fx-from" required>
            <?php foreach($accounts as $a):?><option value="<?=$a['id']?>" data-cur="<?=$a['currency']?>" data-bal="<?=html_escape($a['available_balance'])?>"><?=html_escape($a['name'])?> •<?=substr($a['account_number'],-4)?> (<?=$a['currency']?>) — <?=money($a['available_balance'],$a['currency'])?></option><?php endforeach?>
          </select>
        </div>
        <button type="button" class="fx-swap" id="fx-swap" title="Swap accounts" aria-label="Swap from and to accounts">⇅</button>
        <div>
          <label>To account</label>
          <select name="to_account_id" id="fx-to" required>
            <?php foreach($accounts as $a):?><option value="<?=$a['id']?>" data-cur="<?=$a['currency']?>" data-bal="<?=html_escape($a['available_balance'])?>"><?=html_escape($a['name'])?> •<?=substr($a['account_number'],-4)?> (<?=$a['currency']?>) — <?=money($a['available_balance'],$a['currency'])?></option><?php endforeach?>
          </select>
        </div>
      </div>

      <label>Amount to exchange</label>
      <div class="fx-amount-row">
        <span class="fx-currency" id="fx-from-cur">USD</span>
        <input name="amount" id="fx-amount" type="number" step="0.01" min="0.01" required placeholder="0.00">
      </div>
      <div class="fx-preview" id="fx-preview"></div>
      <button class="btn wide" id="fx-submit"><?=tl('exchange_currency')?> ›</button>
      <p class="fx-disclaimer">Rates refresh with each trade. Conversions are final and appear instantly on both accounts.</p>
    </form>
  </section>

  <!-- Right rail: quick converter + chart -->
  <aside class="exchange-side">
    <!-- Quick converter (uses live AJAX rate, no account needed) -->
    <section class="panel quick-convert">
      <div class="panel-head"><div><h2>Quick converter</h2><p>Live reference rate</p></div></div>
      <div class="quick-convert__body">
        <div class="qc-row">
          <select id="qc-from"><?php $pairs=array();foreach($rates as $r){$pairs[$r['from_currency']]=1;} foreach(array('USD','EUR','GBP') as $c){ if(isset($pairs[$c])):?><option value="<?=$c?>" <?=$c==='USD'?'selected':''?>><?=$c?></option><?php endif;} ?></select>
          <input type="number" id="qc-amount" value="1000" step="0.01" min="0">
        </div>
        <button type="button" class="qc-swap" id="qc-swap" aria-label="Swap currencies">⇅</button>
        <div class="qc-row">
          <select id="qc-to"><?php $topairs=array();foreach($rates as $r){$topairs[$r['to_currency']]=1;} foreach(array('USD','EUR','GBP') as $c){ if(isset($topairs[$c])):?><option value="<?=$c?>" <?=$c==='EUR'?'selected':''?>><?=$c?></option><?php endif;} ?></select>
          <input type="text" id="qc-result" readonly placeholder="—">
        </div>
        <div class="qc-rate" id="qc-rate">Enter an amount to see the rate.</div>
      </div>
    </section>

    <!-- 30-day rate chart -->
    <section class="panel fx-chart-panel">
      <div class="panel-head">
        <div><h2>30-day trend</h2><p id="fx-chart-pair"><?=html_escape($history['from'])?> / <?=html_escape($history['to'])?></p></div>
        <div class="fx-chart-toggle">
          <button type="button" data-days="7" class="outline">7D</button>
          <button type="button" data-days="30" class="outline is-active">30D</button>
          <button type="button" data-days="90" class="outline">90D</button>
        </div>
      </div>
      <div class="fx-chart-body">
        <svg class="fx-chart" id="fx-chart" viewBox="0 0 600 220" preserveAspectRatio="none" role="img" aria-label="Exchange rate history chart">
          <defs>
            <linearGradient id="fx-grad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#1468e5" stop-opacity="0.28"/>
              <stop offset="100%" stop-color="#1468e5" stop-opacity="0"/>
            </linearGradient>
          </defs>
          <path class="fx-chart-area" id="fx-area" d="" fill="url(#fx-grad)"/>
          <path class="fx-chart-line" id="fx-line" d="" fill="none" stroke="#1468e5" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
        </svg>
        <div class="fx-chart-stats">
          <div><small>Latest</small><b id="fx-latest">—</b></div>
          <div><small>30d low</small><b id="fx-low" class="muted">—</b></div>
          <div><small>30d high</small><b id="fx-high" class="muted">—</b></div>
          <div><small>Change</small><b id="fx-change">—</b></div>
        </div>
      </div>
      <p class="fx-source">Mid-market reference rates · updated <?=date('M j, g:ia')?></p>
    </section>

    <!-- Live rates list -->
    <section class="panel transfer-info" style="margin:0">
      <i>◎</i>
      <h3>Exchange rates</h3>
      <p>Current reference rates (tap to chart):</p>
      <ul class="fx-rates" id="fx-rates">
        <?php foreach($rates as $r):?><li data-from="<?=$r['from_currency']?>" data-to="<?=$r['to_currency']?>"><b><?=$r['from_currency']?> → <?=$r['to_currency']?></b><span><?=number_format($r['rate'],4)?></span></li><?php endforeach?>
      </ul>
      <hr>
      <small>Rates are provided for reference and are subject to change.</small>
    </section>
  </aside>
</div>

<!-- Hidden chart seed data from the server (first pair) -->
<script id="fx-seed" type="application/json"><?=json_encode(array(
  'from'=>$history['from'],'to'=>$history['to'],
  'labels'=>$history['labels'],'rates'=>$history['rates'],
))?></script>
<script>
(function(){
  var fromEl=document.getElementById('fx-from'),
      toEl=document.getElementById('fx-to'),
      amtEl=document.getElementById('fx-amount'),
      pv=document.getElementById('fx-preview'),
      fromCur=document.getElementById('fx-from-cur'),
      swap=document.getElementById('fx-swap'),
      submit=document.getElementById('fx-submit');

  function rateFor(fc,tc,cb){
    if(fc===tc){cb(null);return;}
    var rows=document.querySelectorAll('#fx-rates li');
    var rate=null;
    rows.forEach(function(r){
      var f=r.getAttribute('data-from'),t=r.getAttribute('data-to');
      if(f===fc&&t===tc)rate=parseFloat(r.querySelector('span').textContent);
      if(f===tc&&t===fc&&rate===null)rate=1/parseFloat(r.querySelector('span').textContent);
    });
    cb(rate);
  }
  function calc(){
    if(!fromEl.value||!toEl.value||!amtEl.value)return;
    var fc=fromEl.selectedOptions[0].dataset.cur, tc=toEl.selectedOptions[0].dataset.cur, a=parseFloat(amtEl.value)||0;
    fromCur.textContent=fc;
    if(fc===tc){pv.className='fx-preview warn';pv.textContent='Choose two accounts with different currencies.';submit.disabled=true;return;}
    submit.disabled=false;
    rateFor(fc,tc,function(rate){
      if(rate&&a>0){
        pv.className='fx-preview';
        pv.innerHTML='≈ <b>'+a.toFixed(2)+' '+fc+'</b> = <b>'+(a*rate).toFixed(2)+' '+tc+'</b> <small>@ '+rate.toFixed(4)+'</small>';
      } else {
        pv.className='fx-preview warn';pv.textContent='No rate available for '+fc+' → '+tc;
        submit.disabled=true;
      }
    });
  }
  [fromEl,toEl,amtEl].forEach(function(el){el.addEventListener('change',calc);el.addEventListener('input',calc);});
  swap.addEventListener('click',function(){
    var f=fromEl.value,t=toEl.value;fromEl.value=t;toEl.value=f;calc();
  });

  /* ---------- Quick converter ---------- */
  var qf=document.getElementById('qc-from'),qt=document.getElementById('qc-to'),
      qa=document.getElementById('qc-amount'),qres=document.getElementById('qc-result'),qr=document.getElementById('qc-rate'),qswap=document.getElementById('qc-swap');
  function qc(){
    var f=qf.value,t=qt.value,a=parseFloat(qa.value)||0;
    if(f===t){qres.value=(a?a.toFixed(2):'');qr.textContent='Same currency — rate 1.0000';return;}
    fetch('<?=site_url('exchange/convert')?>?from='+encodeURIComponent(f)+'&to='+encodeURIComponent(t)+'&amount='+a,{headers:{'Accept':'application/json'}})
      .then(function(r){return r.json();})
      .then(function(d){
        if(d.ok){qres.value=d.converted.toFixed(2)+' '+d.to;qr.textContent='1 '+d.from+' = '+d.rate.toFixed(4)+' '+d.to;}
        else{qres.value='—';qr.textContent=d.error||'Rate unavailable';}
      }).catch(function(){qres.value='—';qr.textContent='Network error';});
  }
  [qf,qt,qa].forEach(function(el){el.addEventListener('change',qc);el.addEventListener('input',qc);});
  qswap.addEventListener('click',function(){var t=qf.value;qf.value=qt.value;qt.value=t;qc();});
  if(qa.value)qc();

  /* ---------- 30-day chart ---------- */
  var seed=JSON.parse(document.getElementById('fx-seed').textContent||'{}');
  var chart=document.getElementById('fx-chart'),line=document.getElementById('fx-line'),area=document.getElementById('fx-area');
  var latestEl=document.getElementById('fx-low')&&document.getElementById('fx-latest');
  function drawChart(labels,rates){
    if(!rates||!rates.length){line.setAttribute('d','');area.setAttribute('d','');return;}
    var w=600,h=220,pad=10;
    var mn=Math.min.apply(null,rates),mx=Math.max.apply(null,rates);
    if(mn===mx){mn=mn*0.99;mx=mx*1.01;}
    var range=mx-mn;
    var pts=rates.map(function(v,i){
      var x=pad+(i/(rates.length-1))*(w-pad*2);
      var y=h-pad-((v-mn)/range)*(h-pad*2);
      return [x,y];
    });
    var d='M'+pts.map(function(p){return p[0].toFixed(1)+','+p[1].toFixed(1);}).join(' L');
    line.setAttribute('d',d);
    area.setAttribute('d',d+' L'+pts[pts.length-1][0].toFixed(1)+','+(h-pad)+' L'+pts[0][0].toFixed(1)+','+(h-pad)+' Z');
    document.getElementById('fx-latest').textContent=rates[rates.length-1].toFixed(4);
    document.getElementById('fx-low').textContent=mn.toFixed(4);
    document.getElementById('fx-high').textContent=mx.toFixed(4);
    var first=rates[0],last=rates[rates.length-1],chg=((last-first)/first*100);
    var ch=document.getElementById('fx-change');
    ch.textContent=(chg>=0?'+':'')+chg.toFixed(2)+'%';
    ch.style.color=chg>=0?'#15a36a':'#c33d4d';
  }
  function loadHistory(from,to,days){
    document.getElementById('fx-chart-pair').textContent=from+' / '+to;
    fetch('<?=site_url('exchange/history')?>?from='+from+'&to='+to+'&days='+days,{headers:{'Accept':'application/json'}})
      .then(function(r){return r.json();})
      .then(function(d){if(d.ok)drawChart(d.labels,d.rates);});
  }
  drawChart(seed.labels,seed.rates);

  document.querySelectorAll('.fx-chart-toggle button').forEach(function(b){
    b.addEventListener('click',function(){
      document.querySelectorAll('.fx-chart-toggle button').forEach(function(x){x.classList.remove('is-active');});
      b.classList.add('is-active');
      loadHistory(seed.from,seed.to,parseInt(b.dataset.days,10));
    });
  });
  document.querySelectorAll('#fx-rates li').forEach(function(li){
    li.style.cursor='pointer';
    li.addEventListener('click',function(){
      seed.from=li.dataset.from;seed.to=li.dataset.to;
      loadHistory(seed.from,seed.to,30);
      document.querySelectorAll('.fx-chart-toggle button').forEach(function(x){x.classList.remove('is-active');if(x.dataset.days==='30')x.classList.add('is-active');});
    });
  });
})();
</script>
