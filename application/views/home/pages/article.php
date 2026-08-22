<?php defined('BASEPATH') OR exit('No direct script access allowed');
$a = $article;
?>
<section class="kb-article">
  <div class="kb-article__inner">
    <nav class="kb-breadcrumb">
      <a href="<?=site_url('help')?>">Help center</a>
      <span>›</span>
      <a href="<?=site_url('help?category='.urlencode($a['category_slug']))?>"><?=html_escape($a['category'])?></a>
    </nav>

    <span class="kb-card__cat"><?=html_escape($a['category'])?></span>
    <h1><?=html_escape($a['title'])?></h1>
    <p class="kb-article__summary"><?=html_escape($a['summary'])?></p>

    <div class="kb-article__body">
      <?=$a['body']?>
    </div>

    <div class="kb-article__helpful">
      <p>Was this article helpful?</p>
      <div>
        <button type="button" class="outline" data-helpful="yes">👍 Yes</button>
        <button type="button" class="outline" data-helpful="no">👎 No</button>
      </div>
      <span class="kb-helpful-msg" hidden>Thanks for your feedback — we use it to improve the help center.</span>
    </div>

    <div class="kb-article__footer">
      <a class="btn" href="<?=site_url('contact')?>">Contact support</a>
      <a class="outline" href="<?=site_url('help')?>">← Back to help center</a>
    </div>
  </div>
</section>
<script>
document.querySelectorAll('[data-helpful]').forEach(function(b){
  b.addEventListener('click',function(){
    document.querySelectorAll('[data-helpful]').forEach(function(x){x.disabled=true;});
    document.querySelector('.kb-helpful-msg').hidden=false;
  });
});
</script>
