<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * In-Site Operating AI Assistant — floating chat widget.
 * Powered entirely by the local Site_operator_engine (zero external APIs).
 */
$chat_endpoint = site_url('chat');
?>
<div class="chat-widget" id="nw-chat" data-endpoint="<?= html_escape($chat_endpoint) ?>" data-name="NorthWest Assistant">
  <button class="chat-widget__fab" type="button" aria-label="Open AI assistant" aria-expanded="false">
    <span class="chat-widget__fab-icon"><img src="<?=base_url('public/img/ai/assistant-glyph.webp')?>" alt=""></span>
    <span class="chat-widget__label">Ask AI</span>
    <span class="chat-widget__dot"></span>
  </button>

  <div class="chat-widget__panel" hidden>
    <header class="chat-widget__head">
      <span class="chat-widget__avatar"><img src="<?=base_url('public/img/ai/assistant-avatar.webp')?>" alt="NorthWest Assistant"></span>
      <div class="chat-widget__meta">
        <b>NorthWest Assistant</b>
        <small><span class="chat-widget__online"></span> Online · In-site operator</small>
      </div>
      <button class="chat-widget__close" type="button" aria-label="Close">×</button>
    </header>

    <div class="chat-widget__body">
      <div class="chat-widget__messages" aria-live="polite"></div>
    </div>

    <div class="chat-widget__quick" role="group" aria-label="Suggested questions"></div>

    <form class="chat-widget__form">
      <input class="chat-widget__input" type="text" placeholder="Ask me anything…" autocomplete="off" aria-label="Message">
      <button class="chat-widget__send" type="submit" aria-label="Send">➤</button>
    </form>
    <p class="chat-widget__note">✦ 100% in-site · No external AI services · Your data stays on NorthWest servers</p>
  </div>
</div>
<script src="<?= base_url('public/js/chat.js') ?>"></script>
