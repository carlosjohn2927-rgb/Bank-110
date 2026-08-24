<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Moving announcement bar — black text on a white background.
 * Included at the very top of every page so it shows site-wide.
 */
$message = isset($message) && $message !== '' ? $message : site_announcement();
$repeat  = 8; // copies per group; groups are mirrored for a seamless loop
?>
<div class="announcement" role="region" aria-label="Announcements">
  <span class="sr-only"><?= html_escape($message) ?></span>
  <div class="announcement__track" aria-hidden="true">
    <div class="announcement__group">
      <?php for ($i = 0; $i < $repeat; $i++): ?><span class="announcement__item">✦ <?= html_escape($message) ?> ✦</span><?php endfor; ?>
    </div>
    <div class="announcement__group">
      <?php for ($i = 0; $i < $repeat; $i++): ?><span class="announcement__item">✦ <?= html_escape($message) ?> ✦</span><?php endfor; ?>
    </div>
  </div>
</div>
