/**
 * AI Commerce Bot — Embeddable Chatbot Widget v1.0
 * SaaS Multi-tenant Chatbot for any website.
 * 
 * Usage: This file is loaded by the JS snippet returned from /api/connector/js-snippet
 * Config is set via window.AICB_CONFIG before this script loads.
 */
(function () {
    'use strict';

    // ─── Config (injected by snippet) ───────────────────────────────────────────
    var cfg = window.AICB_CONFIG || {};
    var apiKey       = cfg.apiKey       || '';
    var baseUrl      = cfg.baseUrl      || window.location.origin;
    var primaryColor = cfg.primaryColor || '#4f46e5';
    var greeting     = cfg.greeting     || 'আমি আপনাকে সাহায্য করতে পারি! 👋';
    var shopName     = cfg.shopName     || 'AI Shop Assistant';
    var position     = cfg.position     || 'bottom-right';

    var chatEndpoint = baseUrl + '/api/v1/chat/widget';

    // ─── Prevent double init ────────────────────────────────────────────────────
    if (window.__AICB_LOADED__) return;
    window.__AICB_LOADED__ = true;

    // ─── State ──────────────────────────────────────────────────────────────────
    var isOpen   = false;
    var isTyping = false;
    var history  = [];

    // ✅ Persistent session ID — survives page reload so AI remembers the conversation
    var STORAGE_KEY = 'aicb_session_' + (apiKey.substr(-8) || 'default');
    var sessionId = (function () {
        try {
            var stored = localStorage.getItem(STORAGE_KEY);
            if (stored) return stored;
            var id = 'aicb_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
            localStorage.setItem(STORAGE_KEY, id);
            return id;
        } catch (e) {
            // localStorage blocked (private mode / iframe) — fallback to tab-scoped ID
            return 'aicb_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
        }
    })();


    // ─── Styles ─────────────────────────────────────────────────────────────────
    var css = `
        #aicb-widget * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        #aicb-toggle {
            position: fixed; ${position === 'bottom-left' ? 'left: 24px' : 'right: 24px'}; bottom: 24px;
            width: 60px; height: 60px; border-radius: 50%; background: ${primaryColor};
            border: none; cursor: pointer; box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            display: flex; align-items: center; justify-content: center;
            z-index: 999998; transition: transform 0.2s ease, box-shadow 0.2s ease;
            color: white; font-size: 24px;
        }
        #aicb-toggle:hover { transform: scale(1.1); box-shadow: 0 6px 28px rgba(0,0,0,0.25); }
        #aicb-badge {
            position: absolute; top: 0; right: 0; width: 18px; height: 18px;
            background: #ef4444; border-radius: 50%; border: 2px solid white;
            display: none; font-size: 10px; color: white;
            align-items: center; justify-content: center; font-weight: 700;
        }
        #aicb-window {
            position: fixed; ${position === 'bottom-left' ? 'left: 24px' : 'right: 24px'}; bottom: 96px;
            width: 380px; max-width: calc(100vw - 32px); height: 520px; max-height: calc(100vh - 120px);
            background: #ffffff; border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 1px 3px rgba(0,0,0,0.08);
            display: none; flex-direction: column; overflow: hidden; z-index: 999997;
            transform: translateY(16px) scale(0.97); opacity: 0;
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), opacity 0.2s ease;
        }
        #aicb-window.aicb-open {
            transform: translateY(0) scale(1); opacity: 1;
        }
        #aicb-header {
            background: ${primaryColor}; padding: 16px 20px;
            display: flex; align-items: center; gap: 12px; flex-shrink: 0;
        }
        #aicb-avatar {
            width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center; font-size: 18px;
            flex-shrink: 0;
        }
        #aicb-header-info { flex: 1; overflow: hidden; }
        #aicb-header-name { color: white; font-weight: 700; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #aicb-status { color: rgba(255,255,255,0.8); font-size: 12px; display: flex; align-items: center; gap: 4px; }
        .aicb-status-dot { width: 7px; height: 7px; background: #34d399; border-radius: 50%; animation: aicb-pulse 2s infinite; }
        #aicb-close { color: rgba(255,255,255,0.8); background: none; border: none; cursor: pointer; font-size: 18px; padding: 4px; line-height: 1; }
        #aicb-close:hover { color: white; }
        #aicb-messages {
            flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px;
            background: #f8fafc;
            scrollbar-width: thin; scrollbar-color: #e2e8f0 transparent;
        }
        #aicb-messages::-webkit-scrollbar { width: 4px; }
        #aicb-messages::-webkit-scrollbar-track { background: transparent; }
        #aicb-messages::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }
        .aicb-msg { display: flex; gap: 8px; max-width: 100%; animation: aicb-fadein 0.2s ease; }
        .aicb-msg.aicb-user { flex-direction: row-reverse; }
        .aicb-bubble {
            max-width: 80%; padding: 10px 14px; border-radius: 16px; font-size: 14px;
            line-height: 1.5; word-wrap: break-word; white-space: pre-wrap;
        }
        .aicb-bot .aicb-bubble { background: white; color: #1e293b; border-bottom-left-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .aicb-user .aicb-bubble { background: ${primaryColor}; color: white; border-bottom-right-radius: 4px; }
        .aicb-msg-icon { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; margin-top: 4px; }
        .aicb-bot .aicb-msg-icon { background: ${primaryColor}; color: white; }
        .aicb-user .aicb-msg-icon { background: #e2e8f0; }
        .aicb-typing { display: flex; gap: 4px; padding: 10px 14px; }
        .aicb-typing span { width: 8px; height: 8px; background: #94a3b8; border-radius: 50%; animation: aicb-bounce 1.2s infinite; }
        .aicb-typing span:nth-child(2) { animation-delay: 0.2s; }
        .aicb-typing span:nth-child(3) { animation-delay: 0.4s; }
        #aicb-footer { padding: 12px 16px; background: white; border-top: 1px solid #f1f5f9; flex-shrink: 0; }
        #aicb-form { display: flex; gap: 8px; align-items: center; }
        #aicb-input {
            flex: 1; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 14px;
            font-size: 14px; outline: none; background: #f8fafc; transition: border-color 0.2s;
            resize: none; min-height: 42px; max-height: 100px;
        }
        #aicb-input:focus { border-color: ${primaryColor}; background: white; }
        #aicb-send {
            width: 42px; height: 42px; border-radius: 12px; background: ${primaryColor};
            border: none; cursor: pointer; color: white; font-size: 16px;
            display: flex; align-items: center; justify-content: center;
            transition: opacity 0.2s, transform 0.1s; flex-shrink: 0;
        }
        #aicb-send:hover { opacity: 0.9; transform: scale(1.05); }
        #aicb-send:disabled { opacity: 0.5; cursor: not-allowed; }
        #aicb-powered { text-align: center; font-size: 10px; color: #94a3b8; padding-top: 6px; }
        #aicb-powered a { color: ${primaryColor}; text-decoration: none; }
        @keyframes aicb-pulse { 0%,100%{opacity:1;} 50%{opacity:0.5;} }
        @keyframes aicb-bounce { 0%,60%,100%{transform:translateY(0);} 30%{transform:translateY(-6px);} }
        @keyframes aicb-fadein { from{opacity:0;transform:translateY(6px);} to{opacity:1;transform:translateY(0);} }
        @media (max-width: 480px) { #aicb-window { width: 100vw; height: 72vh; bottom: 80px; right: 0; left: 0 !important; border-radius: 20px 20px 0 0; } }
    `;

    // ─── DOM Build ───────────────────────────────────────────────────────────────
    var styleEl = document.createElement('style');
    styleEl.textContent = css;
    document.head.appendChild(styleEl);

    var container = document.createElement('div');
    container.id = 'aicb-widget';
    container.innerHTML = `
        <button id="aicb-toggle" aria-label="Open Chat">
            <span id="aicb-icon">💬</span>
            <span id="aicb-badge"></span>
        </button>
        <div id="aicb-window" role="dialog" aria-label="${shopName} Chat">
            <div id="aicb-header">
                <div id="aicb-avatar">🤖</div>
                <div id="aicb-header-info">
                    <div id="aicb-header-name">${shopName}</div>
                    <div id="aicb-status"><span class="aicb-status-dot"></span> Online</div>
                </div>
                <button id="aicb-close" aria-label="Close">✕</button>
            </div>
            <div id="aicb-messages" aria-live="polite"></div>
            <div id="aicb-footer">
                <form id="aicb-form" autocomplete="off">
                    <textarea id="aicb-input" placeholder="Type a message..." rows="1" aria-label="Message"></textarea>
                    <button type="submit" id="aicb-send" aria-label="Send">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </form>
                <div id="aicb-powered">Powered by <a href="${baseUrl}" target="_blank">AI Commerce Bot</a></div>
            </div>
        </div>
    `;
    document.body.appendChild(container);

    // ─── Element Refs ────────────────────────────────────────────────────────────
    var toggleBtn  = document.getElementById('aicb-toggle');
    var chatWindow = document.getElementById('aicb-window');
    var messagesEl = document.getElementById('aicb-messages');
    var inputEl    = document.getElementById('aicb-input');
    var sendBtn    = document.getElementById('aicb-send');
    var form       = document.getElementById('aicb-form');
    var badge      = document.getElementById('aicb-badge');
    var icon       = document.getElementById('aicb-icon');
    var closeBtn   = document.getElementById('aicb-close');

    // ─── Helpers ─────────────────────────────────────────────────────────────────
    function addMessage(text, role) {
        var msg = document.createElement('div');
        msg.className = 'aicb-msg ' + (role === 'user' ? 'aicb-user' : 'aicb-bot');
        msg.innerHTML = `
            <span class="aicb-msg-icon">${role === 'user' ? '👤' : '🤖'}</span>
            <div class="aicb-bubble">${text.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')}</div>
        `;
        messagesEl.appendChild(msg);
        scrollToBottom();
        return msg;
    }

    function showTyping() {
        if (isTyping) return;
        isTyping = true;
        var el = document.createElement('div');
        el.className = 'aicb-msg aicb-bot';
        el.id = 'aicb-typing-indicator';
        el.innerHTML = `<span class="aicb-msg-icon">🤖</span><div class="aicb-bubble aicb-typing"><span></span><span></span><span></span></div>`;
        messagesEl.appendChild(el);
        scrollToBottom();
    }

    function hideTyping() {
        isTyping = false;
        var el = document.getElementById('aicb-typing-indicator');
        if (el) el.remove();
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    // ─── Toggle Open/Close ────────────────────────────────────────────────────────
    function openChat() {
        isOpen = true;
        chatWindow.style.display = 'flex';
        // Force reflow for animation
        chatWindow.offsetHeight;
        chatWindow.classList.add('aicb-open');
        icon.textContent = '✕';
        badge.style.display = 'none';
        inputEl.focus();

        if (messagesEl.children.length === 0) {
            addMessage(greeting, 'bot');
            history.push({ role: 'assistant', content: greeting });
        }
    }

    function closeChat() {
        isOpen = false;
        chatWindow.classList.remove('aicb-open');
        icon.textContent = '💬';
        setTimeout(function () { chatWindow.style.display = 'none'; }, 250);
    }

    toggleBtn.addEventListener('click', function () { isOpen ? closeChat() : openChat(); });
    closeBtn.addEventListener('click', closeChat);

    // ─── Send Message ─────────────────────────────────────────────────────────────
    async function sendMessage(text) {
        text = text.trim();
        if (!text) return;

        addMessage(text, 'user');
        history.push({ role: 'user', content: text });
        inputEl.value = '';
        inputEl.style.height = 'auto';
        sendBtn.disabled = true;
        showTyping();

        try {
            var resp = await fetch(chatEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Api-Key': apiKey,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    message  : text,
                    session_id: sessionId,
                    history  : history.slice(-8), // Last 8 messages for context
                })
            });

            var data = await resp.json();

            hideTyping();

            if (data.reply) {
                addMessage(data.reply, 'bot');
                history.push({ role: 'assistant', content: data.reply });
            } else if (data.error) {
                addMessage('দুঃখিত, এই মুহূর্তে সাড়া দিতে পারছি না।', 'bot');
            }
        } catch (err) {
            hideTyping();
            addMessage('Connection error. Please try again.', 'bot');
        }

        sendBtn.disabled = false;
        scrollToBottom();
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        sendMessage(inputEl.value);
    });

    // Ctrl+Enter or Enter to send (Shift+Enter for newline)
    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage(inputEl.value);
        }
    });

    // Auto-resize textarea
    inputEl.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });

    // ─── Optional: Show badge after delay ─────────────────────────────────────────
    setTimeout(function () {
        if (!isOpen) {
            badge.style.display = 'flex';
            badge.textContent = '1';
        }
    }, 5000);

    // ─── Seller Reply Polling ─────────────────────────────────────────────────────
    // Polls every 4 seconds. When seller (human agent) sends a reply from dashboard,
    // this picks it up and shows it in the chat bubble automatically.
    var lastPollTime = Math.floor(Date.now() / 1000);
    var pollEndpoint = baseUrl + '/api/v1/chat/widget/poll';

    function pollForSellerReplies() {
        if (!apiKey || !sessionId) return;

        fetch(pollEndpoint + '?api_key=' + encodeURIComponent(apiKey)
            + '&session_id=' + encodeURIComponent(sessionId)
            + '&since=' + lastPollTime, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(data) {
            if (!data) return;

            // Update our clock from server to stay in sync
            if (data.server_time) lastPollTime = data.server_time;

            // Show new seller messages
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(function(msg) {
                    addMessage('👤 ' + msg.text, 'bot');
                    history.push({ role: 'assistant', content: msg.text });
                });

                // If chat is closed, show badge
                if (!isOpen) {
                    badge.style.display = 'flex';
                    badge.textContent = (parseInt(badge.textContent) || 0) + data.messages.length;
                }
            }
        })
        .catch(function() { /* ignore poll errors silently */ });
    }

    // Start polling every 4 seconds
    setInterval(pollForSellerReplies, 4000);

})();

