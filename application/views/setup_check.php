<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Installation check · NorthWest</title><style>
*{box-sizing:border-box}body{margin:0;font-family:'Segoe UI',Arial,sans-serif;background:#eef3f9;color:#17263a;display:grid;place-items:center;min-height:100vh;padding:24px}
.card{width:min(640px,96vw);background:#fff;border:1px solid #e3eaf2;border-radius:16px;padding:28px;box-shadow:0 18px 40px rgba(15,40,80,.10)}
h1{margin:0 0 4px;font-size:22px}
p.sub{color:#718095;margin:0 0 20px;font-size:13px}
.banner{padding:12px 14px;border-radius:10px;font-weight:700;font-size:14px;margin-bottom:18px}
.banner.ok{background:#e5f6ef;color:#168059}
.banner.fail{background:#ffeef0;color:#c33d4d}
.row{display:flex;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid #eef1f5;font-size:13px}
.row:last-child{border-bottom:0}
.badge{width:20px;height:20px;border-radius:50%;display:grid;place-items:center;color:#fff;font-size:12px;flex:none;font-weight:800}
.badge.ok{background:#15a36a}.badge.fail{background:#e64b5d}
.row .label{font-weight:600;flex:1}
.row .hint{color:#c33d4d;font-size:11px;max-width:48%;text-align:right}
.foot{margin-top:18px;font-size:11px;color:#98a5b6}
</style><link rel="icon" href="<?=base_url('public/favicon.svg')?>" type="image/svg+xml">
<link rel="alternate icon" href="<?=base_url('public/favicon.ico')?>" sizes="any">
</head><body><div class="card">
<h1>Installation check</h1>
<p class="sub">Verifies that NorthWest is fully installed and configured.</p>
<div class="banner <?=$all_ok?'ok':'fail'?>"><?=$all_ok?'✓ All checks passed — the application is fully installed.':'! Some checks failed — see below.'?></div>
<?php foreach($checks as $c):?>
<div class="row"><span class="badge <?=$c['ok']?'ok':'fail'?>"><?=$c['ok']?'✓':'!'?></span><span class="label"><?=html_escape($c['label'])?></span><?php if(!$c['ok']):?><span class="hint"><?=html_escape($c['hint'])?></span><?php endif?></div>
<?php endforeach?>
<div class="foot">Diagnostic only — this page changes nothing. Remove or restrict <code>/setup/check</code> in production if you wish.</div>
</div></body></html>
