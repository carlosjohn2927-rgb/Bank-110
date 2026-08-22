<?php defined('BASEPATH') OR exit('No direct script access allowed');
$status = $profile['kyc_status'] ?? 'pending';
$icons = array('passport'=>'🛂','drivers_license'=>'🚗','national_id'=>'🪪','proof_of_address'=>'🏠','selfie'=>'🤳','other'=>'📄');
?>
<div class="page-title">
  <div>
    <em>VERIFICATION</em>
    <h1>Identity verification</h1>
    <p>Upload a government-issued ID and (if required) a proof of address. Documents are reviewed securely by our team.</p>
  </div>
  <span class="kyc-badge kyc-badge--<?=$status?>"><?=ucfirst($status)?></span>
</div>

<div class="grid kyc-grid">
  <!-- Upload panel -->
  <section class="panel">
    <div class="panel-head"><div><h2>Upload a document</h2><p>JPG, PNG, WebP or PDF · up to <?=number_format($this->config->item('max_upload_kb')/1024,0)?>MB</p></div></div>
    <?=form_open_multipart('kyc/upload', array('class'=>'kyc-upload', 'id'=>'kycForm'))?>
      <label>Document type
        <select name="doc_type" required>
          <option value="">Choose a document type…</option>
          <?php foreach ($doc_types as $val => $label): ?>
            <option value="<?=$val?>"><?=html_escape($label)?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="kyc-drop" id="kycDrop">
        <input type="file" name="document" accept="image/jpeg,image/png,image/webp,application/pdf" required>
        <span class="kyc-drop__icon">📎</span>
        <b>Tap to choose a file</b>
        <small>or drag &amp; drop it here</small>
        <span class="kyc-drop__file" id="kycFileName"></span>
      </label>
      <button class="btn wide" type="submit">Submit for review</button>
    </form>
    <div class="kyc-tips">
      <b>Tips for a fast review</b>
      <ul>
        <li>Use a well-lit, in-focus photo of the whole document.</li>
        <li>All four corners of the ID must be visible.</li>
        <li>Your name and address must match your profile.</li>
      </ul>
    </div>
  </section>

  <!-- Your documents -->
  <section class="panel">
    <div class="panel-head"><div><h2>Your documents</h2><p><?=count($documents)?> submitted</p></div></div>
    <?php if (!$documents): ?>
      <div class="empty">No documents yet. Upload your ID to get verified.</div>
    <?php else: ?>
      <div class="kyc-docs">
        <?php foreach ($documents as $d):
          $isImg = strpos($d['mime_type'] ?? '', 'image/') === 0;
        ?>
          <div class="kyc-doc kyc-doc--<?=$d['status']?>">
            <a class="kyc-doc__thumb" href="<?=site_url('kyc/download/'.$d['id'])?>" target="_blank">
              <?php if ($isImg): ?>
                <img src="<?=site_url('kyc/download/'.$d['id'])?>" alt="<?=html_escape($d['original_name'])?>">
              <?php else: ?>
                <span class="kyc-doc__pdf"><?=$icons[$d['doc_type']] ?? '📄'?></span>
              <?php endif; ?>
            </a>
            <div class="kyc-doc__body">
              <b><?=html_escape($doc_types[$d['doc_type']] ?? ucfirst(str_replace('_',' ',$d['doc_type'])))?></b>
              <small><?=date('M j, Y g:ia', strtotime($d['created_at']))?></small>
              <?php if ($d['review_note']): ?>
                <small class="kyc-doc__note">⚠️ <?=html_escape($d['review_note'])?></small>
              <?php endif; ?>
            </div>
            <span class="kyc-doc__status"><?=ucfirst($d['status'])?></span>
            <?php if ($d['status'] === 'pending'): ?>
              <form method="post" action="<?=site_url('kyc/delete/'.$d['id'])?>" onsubmit="return confirm('Remove this pending document?');">
                <button class="kyc-doc__del" title="Delete" aria-label="Delete">×</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<script>
(function(){
  var drop=document.getElementById('kycDrop'),file=drop&&drop.querySelector('input'),name=document.getElementById('kycFileName');
  if(file){
    file.addEventListener('change',function(){
      if(file.files[0])name.textContent=file.files[0].name;
    });
    ['dragenter','dragover'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.add('drag');});});
    ['dragleave','drop'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.remove('drag');});});
    drop.addEventListener('drop',function(e){if(e.dataTransfer.files[0]){file.files=e.dataTransfer.files;name.textContent=e.dataTransfer.files[0].name;}});
  }
})();
</script>
