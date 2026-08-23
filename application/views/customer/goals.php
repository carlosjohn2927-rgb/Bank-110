<?php defined('BASEPATH') OR exit('No direct script access allowed');
$pct = $total_target > 0 ? min(100, round(($total_saved / $total_target) * 100)) : 0;
$icons = array('🏠','✈️','🚗','🎓','💍','🏖️','💻','🎁','👶','🎯','💊','📈');
$colors = array('#1468e5','#15a36a','#7855d0','#e4aa3b','#e64b5d','#0e9aa7');
?>
<div class="page-title">
  <div>
    <em>SAVINGS</em>
    <h1>Savings goals</h1>
    <p>Set a target, add money as you go, and watch your progress grow.</p>
  </div>
  <button class="btn" data-modal="new-goal">+ New goal</button>
</div>

<div class="goals-summary">
  <div class="goals-summary__card">
    <span>Total saved</span>
    <b><?=money($total_saved)?></b>
    <small>across <?=count($goals)?> goal<?=count($goals)===1?'':'s'?></small>
  </div>
  <div class="goals-summary__card">
    <span>Still to save</span>
    <b><?=money(max(0,$total_target-$total_saved))?></b>
    <small>to hit all targets</small>
  </div>
  <div class="goals-summary__progress">
    <div class="goals-summary__bar"><i style="width:<?=$pct?>%"></i></div>
    <span><?=$pct?>% of all goals funded</span>
  </div>
</div>

<?php if (!empty($goals)): ?>
<div class="goals-grid">
  <?php foreach ($goals as $g):
    $saved = (float)$g['saved_amount']; $target = (float)$g['target_amount'];
    $p = $target > 0 ? min(100, round(($saved/$target)*100)) : 0;
    $is_done = $g['status'] === 'completed';
  ?>
  <article class="goal-card <?= $is_done ? 'goal-card--done' : '' ?>" style="--accent:<?=html_escape($g['color'] ?: '#1468e5')?>">
    <header>
      <span class="goal-card__icon"><?=html_escape($g['icon'] ?: '🎯')?></span>
      <form method="post" action="<?=site_url('goals/'.$g['id'].'/delete')?><input type="hidden" name="<?=$this->security->get_csrf_token_name()?>" value="<?=$this->security->get_csrf_hash()?>">" onsubmit="return confirm('Delete this goal? This cannot be undone.');" style="margin-left:auto">
        <button class="goal-card__del" title="Delete goal" aria-label="Delete goal">×</button>
      </form>
    </header>
    <h3><?=html_escape($g['name'])?></h3>
    <div class="goal-card__amount"><b><?=money($saved)?></b><span>/ <?=money($target)?></span></div>
    <div class="goal-card__bar"><i style="width:<?=$p?>%"></i></div>
    <div class="goal-card__meta">
      <span><?=$p?>% funded</span>
      <?php if (!empty($g['target_date'])): ?><span>🎯 <?=date('M j, Y', strtotime($g['target_date']))?></span><?php endif; ?>
      <?php if ($is_done): ?><span class="goal-badge">🎉 Completed</span><?php endif; ?>
    </div>
    <div class="goal-card__actions">
      <button class="outline" data-modal="add-<?=$g['id']?>">+ Add money</button>
      <button class="outline" data-modal="withdraw-<?=$g['id']?>">Withdraw</button>
    </div>
  </article>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty">
  <h3 style="margin:0 0 6px">No goals yet</h3>
  <p>Create your first savings goal — a holiday, a new car, a home — and start tracking it today.</p>
  <button class="btn" data-modal="new-goal">+ Create a goal</button>
</div>
<?php endif; ?>

<!-- New goal dialog -->
<dialog id="new-goal">
  <div class="dialog-head"><h2>New savings goal</h2><button type="button" data-close>×</button></div>
  <form method="post" action="<?=site_url('goals/create')?><input type="hidden" name="<?=$this->security->get_csrf_token_name()?>" value="<?=$this->security->get_csrf_hash()?>">" style="padding:24px">
    <label>Goal name<input name="name" required maxlength="120" placeholder="e.g. Summer holiday"></label>
    <label>Target amount<input name="target_amount" type="number" step="0.01" min="1" required placeholder="5000"></label>
    <label>Target date (optional)<input name="target_date" type="date" min="<?=date('Y-m-d')?>"></label>
    <label>Icon
      <div class="icon-picker">
        <?php foreach ($icons as $i): ?>
          <label class="icon-pick"><input type="radio" name="icon" value="<?=$i?>" <?= $i==='🎯'?'checked':'' ?>><span><?=$i?></span></label>
        <?php endforeach; ?>
      </div>
    </label>
    <label>Color
      <div class="color-picker">
        <?php foreach ($colors as $c): ?>
          <label class="color-pick"><input type="radio" name="color" value="<?=$c?>" <?= $c==='#1468e5'?'checked':'' ?>><span style="background:<?=$c?>"></span></label>
        <?php endforeach; ?>
      </div>
    </label>
    <button class="btn wide">Create goal</button>
  </form>
</dialog>

<?php foreach ($goals as $g): ?>
<dialog id="add-<?=$g['id']?>">
  <div class="dialog-head"><h2>Add to "<?=html_escape($g['name'])?>"</h2><button type="button" data-close>×</button></div>
  <form method="post" action="<?=site_url('goals/'.$g['id'].'/contribute')?><input type="hidden" name="<?=$this->security->get_csrf_token_name()?>" value="<?=$this->security->get_csrf_hash()?>">" style="padding:24px">
    <p>Currently saved: <b><?=money($g['saved_amount'])?></b> of <?=money($g['target_amount'])?></p>
    <label>Amount<input name="amount" type="number" step="0.01" min="0.01" required autofocus></label>
    <button class="btn wide">Add money</button>
  </form>
</dialog>
<dialog id="withdraw-<?=$g['id']?>">
  <div class="dialog-head"><h2>Withdraw from "<?=html_escape($g['name'])?>"</h2><button type="button" data-close>×</button></div>
  <form method="post" action="<?=site_url('goals/'.$g['id'].'/withdraw')?><input type="hidden" name="<?=$this->security->get_csrf_token_name()?>" value="<?=$this->security->get_csrf_hash()?>">" style="padding:24px">
    <p>Available: <b><?=money($g['saved_amount'])?></b></p>
    <label>Amount<input name="amount" type="number" step="0.01" min="0.01" max="<?=html_escape($g['saved_amount'])?>" required autofocus></label>
    <button class="btn wide">Withdraw</button>
  </form>
</dialog>
<?php endforeach; ?>
