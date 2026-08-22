<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kb-hero">
  <div class="kb-hero__inner">
    <em class="eyebrow">HELP CENTER</em>
    <h1>How can we help you?</h1>
    <p>Browse articles by topic, or search for an answer. You can also reach our 24/7 support team.</p>
    <form class="kb-search" method="get" action="<?=site_url('help')?>" role="search" id="kb-form">
      <span class="kb-search__icon">🔍</span>
      <input type="search" name="q" id="kb-input" value="<?=html_escape($q)?>" placeholder="Search for an answer, e.g. 'freeze my card'" autocomplete="off" aria-label="Search help articles" autofocus>
      <button class="btn" type="submit">Search</button>
      <div class="kb-results" id="kb-results" hidden></div>
    </form>
  </div>
</section>

<section class="kb-main">
  <?php if ($q !== ''): ?>
    <div class="kb-search-header">
      <h2><?=count($articles)?> result<?=count($articles)===1?'':'s'?> for “<?=html_escape($q)?>”</h2>
      <a class="outline" href="<?=site_url('help')?>">← Clear search</a>
    </div>
    <?php if (empty($articles)): ?>
      <div class="empty kb-empty">
        <span class="kb-empty__icon">🤔</span>
        <h3>No articles matched “<?=html_escape($q)?>”</h3>
        <p>Try a different word, browse by topic below, or contact our support team — we're here 24/7.</p>
        <a class="btn" href="<?=site_url('contact')?>">Contact support</a>
      </div>
    <?php else: ?>
      <div class="kb-grid">
        <?php foreach ($articles as $a): ?>
          <a class="kb-card" href="<?=site_url('help/'.$a['slug'])?>">
            <span class="kb-card__cat"><?=html_escape($a['category'])?></span>
            <h3><?=html_escape($a['title'])?></h3>
            <p><?=html_escape($a['summary'])?></p>
            <span class="kb-card__more">Read article →</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <nav class="kb-cats">
      <a class="kb-cat <?=!$category?'is-active':''?>" href="<?=site_url('help')?>">
        <span>📚</span><b>All topics</b><small><?=array_sum(array_column($categories,'count'))?> articles</small>
      </a>
      <?php foreach ($categories as $c): ?>
        <a class="kb-cat <?=$category===$c['slug']?'is-active':''?>" href="<?=site_url('help?category='.$c['slug'])?>">
          <span><?=isset(['getting-started'=>'🚀','accounts'=>'🏦','cards'=>'💳','transfers'=>'↗️','deposits'=>'📥','loans'=>'▥','savings'=>'🎯','budget'=>'📊','security'=>'🔐','mobile'=>'📱','support'=>'🛟'][$c['slug']])?['getting-started'=>'🚀','accounts'=>'🏦','cards'=>'💳','transfers'=>'↗️','deposits'=>'📥','loans'=>'▥','savings'=>'🎯','budget'=>'📊','security'=>'🔐','mobile'=>'📱','support'=>'🛟'][$c['slug']]:'📄'?></span>
          <b><?=html_escape($c['name'])?></b><small><?=$c['count']?> article<?=$c['count']===1?'':'s'?></small>
        </a>
      <?php endforeach; ?>
    </nav>

    <?php if ($category): ?>
      <div class="kb-search-header">
        <h2><?=html_escape($articles[0]['category'] ?? 'Topic')?></h2>
        <a class="outline" href="<?=site_url('help')?>">← All topics</a>
      </div>
    <?php else: ?>
      <h2 class="kb-section-title">Popular questions</h2>
    <?php endif; ?>

    <div class="kb-grid">
      <?php foreach ($articles as $a): ?>
        <a class="kb-card" href="<?=site_url('help/'.$a['slug'])?>">
          <span class="kb-card__cat"><?=html_escape($a['category'])?></span>
          <h3><?=html_escape($a['title'])?></h3>
          <p><?=html_escape($a['summary'])?></p>
          <span class="kb-card__more">Read article →</span>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="kb-cta panel">
      <div>
        <h2>Still have questions?</h2>
        <p>Our human support team is available around the clock — by phone, secure message or in branch.</p>
      </div>
      <div class="kb-cta__actions">
        <a class="btn" href="<?=site_url('contact')?>">Contact support</a>
        <a class="outline" href="<?=site_url('user/login')?>">Sign in to message us</a>
      </div>
    </div>
  <?php endif; ?>
</section>

<script>
(function(){
  var input=document.getElementById('kb-input');
  var box=document.getElementById('kb-results');
  var form=document.getElementById('kb-form');
  if(!input||!box)return;
  var timer=null, current='';
  function render(items){
    if(!items.length){box.innerHTML='<div class="kb-results__empty">No matching articles — press Enter to search</div>';box.hidden=false;return;}
    box.innerHTML=items.map(function(it){
      return '<a class="kb-results__item" href="'+it.url+'"><b>'+it.title+'</b><small>'+it.category+'</small></a>';
    }).join('');
    box.hidden=false;
  }
  input.addEventListener('input',function(){
    var q=input.value.trim();
    clearTimeout(timer);
    if(q.length<2){box.hidden=true;box.innerHTML='';return;}
    if(q===current)return;
    current=q;
    timer=setTimeout(function(){
      fetch('<?=site_url('help/search')?>?q='+encodeURIComponent(q),{headers:{'Accept':'application/json'}})
        .then(function(r){return r.json();})
        .then(function(d){if(d.query===input.value.trim())render(d.results);})
        .catch(function(){box.hidden=true;});
    },180);
  });
  input.addEventListener('focus',function(){if(box.children.length)box.hidden=false;});
  document.addEventListener('click',function(e){if(!form.contains(e.target))box.hidden=true;});
  form.addEventListener('submit',function(){box.hidden=true;});
})();
</script>
