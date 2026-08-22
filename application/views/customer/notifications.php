<?php defined('BASEPATH') OR exit('No direct script access allowed');
$icons=array('transfer'=>'↗','ticket'=>'?','security'=>'✓','loan'=>'▥','card'=>'▤','deposit'=>'↓','general'=>'♢');
$filters=array(
  ''=>'All','unread'=>'Unread','transfer'=>'Transfers','deposit'=>'Deposits',
  'ticket'=>'Support','loan'=>'Loans','card'=>'Cards','security'=>'Security','general'=>'General',
);
$unread_badge=function($key)use($unread_by_type){if($key==='')return $unread_total; if($key==='unread')return $unread_total; return isset($unread_by_type[$key])?$unread_by_type[$key]:0;};
?>
<div class="page-title">
  <div>
    <em>INBOX</em>
    <h1>Notifications</h1>
    <p><?=number_format($total)?> total · <?=(int)$unread_total?> unread</p>
  </div>
  <?php if($notifs): ?>
  <div class="notif-actions">
    <form method="post" action="<?=site_url('notifications/mark-all')?>" style="display:inline">
      <button class="outline" type="submit">✓ Mark all read</button>
    </form>
    <form method="post" action="<?=site_url('notifications/clear')?>" style="display:inline" onsubmit="return confirm('Delete all notifications? This cannot be undone.');">
      <button class="btn-danger-outline" type="submit">Clear all</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<nav class="notif-filters">
  <?php foreach($filters as $key=>$label): $cnt=$unread_badge($key);
    $qs=$key!==''?'?filter='.$key:''; ?>
    <a href="<?=site_url('notifications'.$qs)?>" class="notif-filter <?=($filter??'')===$key?'is-active':''?>">
      <?=$label?><?php if($cnt>0):?><span class="notif-filter__count"><?=$cnt?></span><?php endif;?>
    </a>
  <?php endforeach; ?>
</nav>

<?php if(!$notifs): ?>
  <div class="empty notif-empty">
    <span class="notif-empty__icon">🔔</span>
    <h3>No <?=($filter&&$filter!=='unread')?html_escape($filters[$filter]).' ':''?>notifications</h3>
    <p><?=($filter==='unread')?"You're all caught up.":'Updates about your account will appear here.'?></p>
    <?php if($filter):?><a class="btn" href="<?=site_url('notifications')?>">View all notifications</a><?php endif;?>
  </div>
<?php else: ?>
<section class="panel list notif-list" id="notif-list">
  <?php foreach($notifs as $n):
    $is_unread=!$n['is_read'];
    $target=$n['link']?site_url('notifications/read/'.$n['id']):site_url('notifications/read/'.$n['id']);
  ?>
  <article class="list-row notif-row <?=$is_unread?'unread':''?>" data-id="<?=$n['id']?>">
    <a class="notif-row__main" href="<?=$target?>">
      <span class="avatar"><?=$icons[$n['type']]??'♢'?></span>
      <div class="notif-row__body">
        <b><?=html_escape($n['title'])?></b>
        <small><?=html_escape($n['body'])?></small>
        <time datetime="<?=date('c',strtotime($n['created_at']))?>"><?=$this->time_ago($n['created_at'])?></time>
      </div>
      <?php if($is_unread):?><span class="notif-dot" title="Unread"></span><?php endif;?>
    </a>
    <div class="notif-row__tools">
      <?php if($n['link']):?><a class="outline notif-tool" href="<?=site_url($n['link'])?>">View</a><?php endif;?>
      <button class="notif-tool notif-delete" type="button" title="Delete" data-id="<?=$n['id']?>" aria-label="Delete notification">×</button>
    </div>
  </article>
  <?php endforeach; ?>
</section>
<?php if(!empty($pagination)):?><div class="pager-wrap"><?=$pagination?></div><?php endif;?>
<?php endif; ?>

<script>
(function(){
  var csrf=null;
  function post(url,cb){fetch(url,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'}).then(function(r){return r.json();}).then(cb).catch(function(){});}
  document.querySelectorAll('.notif-delete').forEach(function(btn){
    btn.addEventListener('click',function(){
      var row=btn.closest('.notif-row');
      post('<?=site_url('notifications/delete')?>/'+btn.dataset.id,function(res){
        if(res&&res.ok){row.style.transition='opacity .2s,transform .2s';row.style.opacity='0';row.style.transform='translateX(20px)';setTimeout(function(){row.remove();},200);}
      });
    });
  });
})();
</script>
