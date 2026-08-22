<?php defined('BASEPATH') OR exit('No direct script access allowed');
$catColors = array('#1468e5','#15a36a','#7855d0','#e4aa3b','#e64b5d','#0e9aa7','#ff7a59','#5b6b83');
?>
<div class="page-title">
  <div>
    <em>ADMINISTRATION</em>
    <h1>Operations overview</h1>
    <p>Monitor the health and activity of NorthWest banking.</p>
  </div>
  <div class="dash-head">
    <div class="range-select" role="tablist">
      <button type="button" data-range="7"  class="<?=($range===7)?'is-active':''?>">7D</button>
      <button type="button" data-range="30" class="<?=($range===30)?'is-active':''?>">30D</button>
      <button type="button" data-range="90" class="<?=($range===90)?'is-active':''?>">90D</button>
    </div>
    <a class="btn" href="<?=site_url('admin/customers/create')?>">+ Add customer</a>
  </div>
</div>

<!-- Range KPI cards (AJAX-updated) -->
<div class="stats four kpi-range" id="kpi-range">
  <div><i>⇄</i><span>Transactions<b data-kpi="transactions"><?=number_format($kpis['transactions'])?></b><small>over <?=$range?> days</small></span></div>
  <div><i>$</i><span>Volume moved<b data-kpi="volume"><?=money($kpis['volume'])?></b><small>completed, <?=$range?> days</small></span></div>
  <div><i>♙</i><span>New customers<b data-kpi="new_customers"><?=number_format($kpis['new_customers'])?></b><small>joined in <?=$range?> days</small></span></div>
  <div><i>▣</i><span>New accounts<b data-kpi="new_accounts"><?=number_format($kpis['new_accounts'])?></b><small>opened in <?=$range?> days</small></span></div>
</div>

<!-- Snapshot KPIs (all-time / today) -->
<div class="stats four">
  <div><i>♙</i><span>Total customers<b><?=number_format($metrics['customers'])?></b><small>Active customer records</small></span></div>
  <div><i>$</i><span>Total deposits<b><?=money($metrics['deposits'])?></b><small>Across all accounts</small></span></div>
  <div><i>⇄</i><span>Transactions today<b><?=number_format($metrics['transactions_today'])?></b><small>Processed today</small></span></div>
  <div><i>!</i><span><a class="metric-link" href="<?=site_url('admin/transfers')?>">Pending transfers<b><?=$metrics['pending']?></b></a></span><small>Require review</small></div>
</div>
<div class="stats four">
  <div><i>◎</i><span>Scheduled transfers<b><?=$metrics['scheduled']?></b><small>Coming due</small></span></div>
  <div><i>▤</i><span>Cards issued<b><?=number_format($metrics['cards'])?></b><small>Total cards</small></span></div>
  <div><i>▥</i><span>Active loans<b><?=number_format($metrics['active_loans'])?></b><small>Live lending</small></span></div>
  <div><i>✓</i><span><a class="metric-link" href="<?=site_url('admin/check-deposits')?>">Check deposits for review<b><?=$metrics['pending_deposits']?></b></a></span><small>Pending review</small></div>
</div>

<div class="grid two admin-grid">
  <!-- Transaction volume (count + value) -->
  <section class="panel chart-panel">
    <div class="panel-head">
      <div><h2>Transaction volume</h2><p>Count and value over the selected range</p></div>
      <span class="chart-legend"><i class="lc-count"></i> Count <i class="lc-amount"></i> Value</span>
    </div>
    <div class="svg-chart-wrap">
      <svg class="svg-line-chart" id="chart-volume" viewBox="0 0 600 240" preserveAspectRatio="none"></svg>
    </div>
  </section>

  <!-- Account distribution donut -->
  <section class="panel spending">
    <div class="panel-head"><div><h2>Account distribution</h2><p>Active products (all-time)</p></div></div>
    <div class="donut" style="background:conic-gradient(#123d68 0% <?=$distribution['checking']?>%,#21ad79 <?=$distribution['checking']?>% <?=($distribution['checking']+$distribution['savings'])?>%,#7855d0 <?=($distribution['checking']+$distribution['savings'])?>% 100%)">
      <span><b><?=number_format($distribution['total'])?></b><small>accounts</small></span>
    </div>
    <ul>
      <li><i class="navy"></i>Checking <b><?=$distribution['checking']?>%</b></li>
      <li><i class="green"></i>Savings <b><?=$distribution['savings']?>%</b></li>
      <li><i class="purple"></i>Investment <b><?=$distribution['investment']?>%</b></li>
    </ul>
  </section>
</div>

<div class="grid two admin-grid">
  <!-- New signups -->
  <section class="panel chart-panel">
    <div class="panel-head"><div><h2>New customer signups</h2><p>Daily registrations over the range</p></div></div>
    <div class="svg-chart-wrap">
      <svg class="svg-bar-chart" id="chart-signups" viewBox="0 0 600 200" preserveAspectRatio="none"></svg>
    </div>
  </section>

  <!-- Spending by category -->
  <section class="panel">
    <div class="panel-head"><div><h2>Money out by category</h2><p>Debits over the selected range</p></div></div>
    <div class="cat-bars" id="cat-bars">
      <?php if(empty($categories)): ?>
        <div class="bell-empty">No debit activity in this range.</div>
      <?php else: $catMax=max(array_map(function($c){return (float)$c['total'];},$categories))?:1;
        foreach($categories as $i=>$c): ?>
        <div class="cat-bar">
          <span class="cat-bar__label"><?=html_escape($c['category'])?></span>
          <div class="cat-bar__track"><i style="width:<?=round(((float)$c['total']/$catMax)*100)?>%;background:<?=$catColors[$i%count($catColors)]?>"></i></div>
          <b class="cat-bar__value"><?=money($c['total'])?></b>
          <small class="cat-bar__count"><?=(int)$c['c']?></small>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </section>
</div>

<section class="panel">
  <div class="panel-head"><div><h2>Recent customers</h2><p>Latest account registrations</p></div><a href="<?=site_url('admin/customers')?>">View all customers ›</a></div>
  <?php $this->load->view('admin/partials/customer_table',array('customers'=>$customers));?>
</section>

<script>
(function(){
  var rangeButtons=document.querySelectorAll('.range-select button');
  var charts={};
  var DATA=<?=json_encode(array('range'=>$range,'volume'=>$volume,'signups'=>$signups,'categories'=>$categories,'kpis'=>$kpis))?>;

  // ---- Transaction volume line chart (dual: count bars + value line) ----
  function renderVolume(d){
    var svg=document.getElementById('chart-volume');if(!svg)return;
    var counts=d.volume.counts, amounts=d.volume.amounts, labels=d.volume.labels;
    var W=600,H=240,padL=40,padR=12,padT=16,padB=28;
    var maxC=Math.max(1,Math.max.apply(null,counts));
    var maxA=Math.max(1,Math.max.apply(null,amounts));
    var innerW=W-padL-padR, innerH=H-padT-padB;
    var n=counts.length, barW=Math.max(2,innerW/n*0.6);
    var step=innerW/(n-1||1);
    var html='';
    // y gridlines
    for(var g=0;g<=4;g++){
      var y=padT+innerH*(g/4);
      html+='<line x1="'+padL+'" y1="'+y+'" x2="'+(W-padR)+'" y2="'+y+'" stroke="#eef2f7" stroke-width="1"/>';
    }
    // value line
    var pts=amounts.map(function(v,i){return (padL+i*step)+','+(padT+innerH-(v/maxA)*innerH);});
    if(pts.length>1){
      var area='M'+padL+','+(padT+innerH)+' L'+pts.join(' L')+' L'+(padL+(n-1)*step)+','+(padT+innerH)+' Z';
      html+='<path d="'+area+'" fill="rgba(20,104,229,0.10)"/>';
      html+='<path d="M'+pts.join(' L')+'" fill="none" stroke="#1468e5" stroke-width="2" vector-effect="non-scaling-stroke"/>';
    }
    // count bars
    for(var i=0;i<n;i++){
      var bh=(counts[i]/maxC)*innerH;
      var x=padL+i*step-barW/2;
      html+='<rect x="'+x+'" y="'+(padT+innerH-bh)+'" width="'+barW+'" height="'+bh+'" rx="2" fill="#cfe0fb"><title>'+labels[i]+': '+counts[i]+' txns, '+amounts[i].toFixed(2)+'</title></rect>';
    }
    // x labels (sparse)
    var labelEvery=Math.ceil(n/7);
    for(var i=0;i<n;i+=labelEvery){
      html+='<text x="'+(padL+i*step)+'" y="'+(H-8)+'" font-size="9" fill="#8494a8" text-anchor="middle">'+labels[i]+'</text>';
    }
    svg.innerHTML=html;
  }

  // ---- Signups bar chart ----
  function renderSignups(d){
    var svg=document.getElementById('chart-signups');if(!svg)return;
    var counts=d.signups.counts,labels=d.signups.labels;
    var W=600,H=200,padL=34,padR=10,padT=14,padB=26;
    var innerW=W-padL-padR,innerH=H-padT-padB;
    var maxC=Math.max(1,Math.max.apply(null,counts));
    var n=counts.length, gap=3, barW=(innerW-gap*(n-1))/n;
    var html='';
    for(var g=0;g<=4;g++){var y=padT+innerH*(g/4);
      html+='<line x1="'+padL+'" y1="'+y+'" x2="'+(W-padR)+'" y2="'+y+'" stroke="#eef2f7"/>';}
    for(var i=0;i<n;i++){
      var bh=(counts[i]/maxC)*innerH;
      html+='<rect x="'+(padL+i*(barW+gap))+'" y="'+(padT+innerH-bh)+'" width="'+barW+'" height="'+bh+'" rx="2" fill="#21ad79"><title>'+labels[i]+': '+counts[i]+' signups</title></rect>';
    }
    var labelEvery=Math.ceil(n/7);
    for(var i=0;i<n;i+=labelEvery){
      html+='<text x="'+(padL+i*(barW+gap)+barW/2)+'" y="'+(H-7)+'" font-size="9" fill="#8494a8" text-anchor="middle">'+labels[i]+'</text>';
    }
    svg.innerHTML=html;
  }

  // ---- Category bars ----
  function renderCategories(d){
    var wrap=document.getElementById('cat-bars');if(!wrap)return;
    var colors=['#1468e5','#15a36a','#7855d0','#e4aa3b','#e64b5d','#0e9aa7','#ff7a59','#5b6b83'];
    if(!d.categories.length){wrap.innerHTML='<div class="bell-empty">No debit activity in this range.</div>';return;}
    var max=Math.max.apply(null,d.categories.map(function(c){return parseFloat(c.total);}))||1;
    wrap.innerHTML=d.categories.map(function(c,i){
      return '<div class="cat-bar"><span class="cat-bar__label">'+escapeHtml(c.category)+'</span>'
        +'<div class="cat-bar__track"><i style="width:'+Math.round((c.total/max)*100)+'%;background:'+colors[i%colors.length]+'"></i></div>'
        +'<b class="cat-bar__value">$'+Number(c.total).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})+'</b>'
        +'<small class="cat-bar__count">'+c.c+'</small></div>';
    }).join('');
  }

  function renderKpis(d){
    var map={transactions:Number(d.kpis.transactions).toLocaleString(),
      volume:'$'+Number(d.kpis.volume).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}),
      new_customers:Number(d.kpis.new_customers).toLocaleString(),
      new_accounts:Number(d.kpis.new_accounts).toLocaleString()};
    Object.keys(map).forEach(function(k){var el=document.querySelector('[data-kpi="'+k+'"]');if(el)el.textContent=map[k];});
  }

  function escapeHtml(s){return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}

  function renderAll(d){renderKpis(d);renderVolume(d);renderSignups(d);renderCategories(d);}
  renderAll(DATA);

  rangeButtons.forEach(function(btn){
    btn.addEventListener('click',function(){
      rangeButtons.forEach(function(b){b.classList.remove('is-active');});
      btn.classList.add('is-active');
      var r=btn.dataset.range;
      document.body.classList.add('dash-loading');
      fetch('<?=site_url('admin/dashboard/data')?>?range='+r,{headers:{'Accept':'application/json'},credentials:'same-origin'})
        .then(function(res){return res.json();})
        .then(function(d){if(d.ok){DATA=d;renderAll(d);}})
        .catch(function(){})
        .finally(function(){document.body.classList.remove('dash-loading');});
    });
  });
})();
</script>
