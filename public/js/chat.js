(function () {
  'use strict';

  var root = document.getElementById('nw-chat');
  if (!root) return;

  var fab = root.querySelector('.chat-widget__fab');
  var panel = root.querySelector('.chat-widget__panel');
  var close = root.querySelector('.chat-widget__close');
  var messages = root.querySelector('.chat-widget__messages');
  var quick = root.querySelector('.chat-widget__quick');
  var form = root.querySelector('.chat-widget__form');
  var input = root.querySelector('.chat-widget__input');
  var endpoint = root.getAttribute('data-endpoint') || '/chat';
  var open = false;
  var busy = false;
  var initGreeted = false;

  var WELCOME = 'Hi, I\u2019m your NorthWest Assistant \u2726 I run entirely inside this website \u2014 no external AI services. Ask me about your balance, transactions, transfers, cards, loans, fees or security.';

  var GREET_QUICK = [
    { label: '💰 My balance', value: 'What is my balance?' },
    { label: '↗ Send money', value: 'How do I send money?' },
    { label: '🔐 Security', value: 'How is my account secure?' },
    { label: '🛟 Support', value: 'I need help from support' }
  ];

  function openPanel() {
    open = true;
    panel.hidden = false;
    fab.setAttribute('aria-expanded', 'true');
    root.classList.add('open');
    if (!initGreeted) {
      initGreeted = true;
      addMessage('assistant', WELCOME);
      renderQuick(GREET_QUICK);
    }
    setTimeout(function () { if (input) input.focus(); }, 60);
  }

  function closePanel() {
    open = false;
    panel.hidden = true;
    root.classList.remove('open');
    fab.setAttribute('aria-expanded', 'false');
  }

  fab.addEventListener('click', open ? closePanel : openPanel);
  close.addEventListener('click', closePanel);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var text = (input.value || '').trim();
    if (!text || busy) return;
    input.value = '';
    addMessage('user', text);
    renderQuick([]);
    send(text);
  });

  function send(text) {
    busy = true;
    showTyping();
    var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    fetch(endpoint, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify({ message: text })
    })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
      .then(function (res) {
        removeTyping();
        busy = false;
        var data = res.d || {};
        if (res.ok && data.ok) {
          addMessage('assistant', data.text || '...');
          renderQuick(data.quick || []);
          renderActions(data.actions || []);
        } else {
          addMessage('assistant', 'Sorry, I hit a snag. Please try again in a moment.');
        }
      })
      .catch(function () {
        removeTyping();
        busy = false;
        addMessage('assistant', 'Sorry, I could not reach the assistant. Please try again.');
      });
  }

  function addMessage(role, text) {
    var el = document.createElement('div');
    el.className = 'chat-msg ' + role;
    var b = document.createElement('div');
    b.className = 'chat-bubble';
    b.textContent = text;
    el.appendChild(b);
    messages.appendChild(el);
    scrollDown();
  }

  function showTyping() {
    var el = document.createElement('div');
    el.className = 'chat-msg assistant typing';
    el.innerHTML = '<div class="chat-bubble"><i></i><i></i><i></i></div>';
    el.setAttribute('data-typing', '1');
    messages.appendChild(el);
    scrollDown();
  }

  function removeTyping() {
    var t = messages.querySelector('[data-typing]');
    if (t) t.remove();
  }

  function renderQuick(list) {
    quick.innerHTML = '';
    (list || []).forEach(function (q) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'chat-chip';
      b.textContent = q.label || q.value;
      b.addEventListener('click', function () {
        input.value = q.value || q.label || '';
        form.dispatchEvent(new Event('submit'));
      });
      quick.appendChild(b);
    });
  }

  function renderActions(actions) {
    if (!actions || !actions.length) return;
    var wrap = document.createElement('div');
    wrap.className = 'chat-actions';
    actions.forEach(function (a) {
      var link = document.createElement('a');
      link.href = a.url;
      link.textContent = '→ ' + (a.label || 'Open');
      wrap.appendChild(link);
    });
    messages.appendChild(wrap);
    scrollDown();
  }

  function scrollDown() {
    messages.scrollTop = messages.scrollHeight;
  }
})();
