// Read config from the main Laravel .env in the parent directory
require('dotenv').config({ path: require('path').resolve(__dirname, '../.env'), override: true });
const express = require('express');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ limit: '50mb', extended: true }));

// All active WhatsApp sessions stored here
const sessions = {};
const sessionReadyPromises = {};

// Send webhook back to Laravel
async function sendWebhookToLaravel(endpoint, data) {
    try {
        // Send via APP_URL directly. TLS loops are mitigated by rejectUnauthorized=false
        const appUrl = process.env.APP_URL || 'http://127.0.0.1:8000';
        const myDomain = appUrl;
        const secretKey = process.env.WA_WEBHOOK_SECRET || 'super-secret-key';
        
        let hostHeader = 'localhost';
        try { hostHeader = new URL(appUrl).hostname; } catch(e) {}

        const http = require('http');
        const https = require('https');
        
        const isHttps = myDomain.startsWith('https');
        const targetUrl = `${myDomain}/api/v1/whatsapp/${endpoint}`;
        
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${secretKey}`,
                'Host': hostHeader
            }
        };

        // Bypass SSL errors for local or self-signed VPS loopbacks
        if (isHttps) options.rejectUnauthorized = false;

        const req = (isHttps ? https : http).request(targetUrl, options, (res) => {
            let resData = '';
            res.on('data', chunk => { resData += chunk; });
            res.on('end', () => {
                console.log(`[Webhook] [${endpoint}] -> ${res.statusCode}: ${resData.substring(0, 120)}`);
            });
        });

        req.on('error', (error) => {
            console.error(`[Webhook] Error [${endpoint}]:`, error.message);
        });

        req.write(JSON.stringify(data));
        req.end();
    } catch (error) {
        console.error(`[Webhook] Error [${endpoint}]:`, error.message);
    }
}

// Format a phone number to @c.us (whatsapp-web.js format)
function formatToWhatsApp(rawNumber) {
    // Strip any existing suffix
    let clean = String(rawNumber)
        .replace('@s.whatsapp.net', '')
        .replace('@c.us', '')
        .replace('@g.us', '')
        .replace('@lid', '')
        .replace(/[^0-9]/g, '')  // keep only digits
        .trim();

    // Bangladeshi numbers: 01XXXXXXXX -> 8801XXXXXXXX
    if (clean.startsWith('01')) clean = '88' + clean;

    return `${clean}@c.us`;
}

// Core: initialize a WhatsApp session
function initializeWhatsAppClient(instance_id, res = null) {
    if (sessions[instance_id]) {
        if (res) return res.json({ success: true, status: 'connected' });
        return sessions[instance_id];
    }

    console.log(`[WA] Initializing session for: ${instance_id}`);

    let resolveReady;
    sessionReadyPromises[instance_id] = new Promise((resolve) => {
        resolveReady = resolve;
    });
    sessionReadyPromises[instance_id].resolve = resolveReady;

    const client = new Client({
        authStrategy: new LocalAuth({ clientId: instance_id }),
        puppeteer: {
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
        }
    });

    let isQrSent = false;

    client.on('qr', async (qr) => {
        console.log(`[WA] QR Code ready for ${instance_id}`);
        if (res && !isQrSent) {
            const qrImage = await qrcode.toDataURL(qr);
            res.json({ success: true, qr_code: qrImage, status: 'scan_required' });
            isQrSent = true;
        }
    });

    client.on('ready', () => {
        console.log(`[WA] READY for ${instance_id}`);
        sessions[instance_id] = client;
        sendWebhookToLaravel('status', { instance_id, status: 'connected' });

        if (sessionReadyPromises[instance_id]?.resolve) {
            sessionReadyPromises[instance_id].resolve();
        }
        if (res && !res.headersSent) {
            res.json({ success: true, status: 'connected' });
        }
    });

    client.on('message', async (msg) => {
        if (msg.from === 'status@broadcast') return;
        if (msg.from.includes('@g.us') || msg.from.includes('@newsletter')) return;

        try {
            const chat = await msg.getChat();
            await chat.sendSeen();
        } catch (e) {}

        let messageBody = msg.body;
        let attachmentBase64 = null;

        if (msg.hasMedia) {
            try {
                const media = await msg.downloadMedia();
                if (media) {
                    messageBody = `[Received a ${media.mimetype.split('/')[0]} file]`;
                    attachmentBase64 = `data:${media.mimetype};base64,${media.data}`;
                }
            } catch (err) {
                console.log('[WA] Media download error:', err.message);
            }
        }

        let senderName = msg._data?.notifyName || 'Customer';
        let cleanNumber = msg.from.replace('@c.us', '');

        try {
            const contact = await msg.getContact();
            senderName = contact.pushname || contact.name || senderName;
            cleanNumber = contact.number || cleanNumber;
        } catch (e) {
            console.log('[WA] Contact fetch error:', e.message);
        }

        console.log(`[WA] New message from ${cleanNumber} (${senderName}): "${messageBody.substring(0, 60)}"`);

        let quotedText = null;
        if (msg.hasQuotedMsg) {
            try {
                const quotedMsg = await msg.getQuotedMessage();
                if (quotedMsg.hasMedia) {
                    quotedText = '[Replied to an image/media]';
                } else if (quotedMsg.body) {
                    quotedText = quotedMsg.body;
                }
            } catch (err) {
                console.log('[WA] Error reading quoted message:', err.message);
            }
        }

        sendWebhookToLaravel('receive', {
            instance_id,
            from: cleanNumber,
            body: messageBody,
            sender_name: senderName,
            attachment: attachmentBase64,
            quoted_message: quotedText
        });
    });

    client.on('disconnected', (reason) => {
        console.log(`[WA] Disconnected ${instance_id}:`, reason);
        delete sessions[instance_id];
        sendWebhookToLaravel('status', { instance_id, status: 'disconnected' });
    });

    client.initialize();
    sessions[instance_id] = client;
    return client;
}

// === API Routes ===

// Generate QR code to connect WhatsApp
app.post('/api/generate-qr', async (req, res) => {
    const { instance_id } = req.body;
    if (!instance_id) return res.status(400).json({ success: false, message: 'instance_id required' });
    initializeWhatsAppClient(instance_id, res);
});

// Send message (called from Laravel)
app.post('/api/send-message', async (req, res) => {
    const { instance_id, to, message, media } = req.body;
    console.log(`[SendMsg] To: ${to}`);

    let client = sessions[instance_id];
    if (!client) {
        console.log(`[SendMsg] Auto-connecting from saved auth for: ${instance_id}`);
        client = initializeWhatsAppClient(instance_id);
        if (sessionReadyPromises[instance_id]) {
            await sessionReadyPromises[instance_id];
        }
    }

    try {
        const formattedTo = formatToWhatsApp(to);
        console.log(`[SendMsg] Sending to: ${formattedTo}`);

        let chat = null;
        try {
            chat = await client.getChatById(formattedTo);
            if (chat) {
                await chat.sendStateTyping();
                await new Promise(resolve => setTimeout(resolve, 800));
            }
        } catch (e) {}

        if (media && media.mimetype && media.data) {
            const attachment = new MessageMedia(media.mimetype, media.data, media.filename || 'attachment');
            await client.sendMessage(formattedTo, message || '', { media: attachment });
        } else {
            await client.sendMessage(formattedTo, message);
        }

        if (chat) await chat.clearState();

        console.log(`[SendMsg] SUCCESS: Sent to ${formattedTo}`);
        res.json({ success: true, message: 'Message sent successfully' });

    } catch (error) {
        console.error('[SendMsg] Error:', error.message);
        res.status(500).json({ success: false, error: error.message });
    }
});

// Disconnect a session
app.post('/api/disconnect', async (req, res) => {
    const { instance_id } = req.body;
    if (!instance_id) return res.status(400).json({ success: false, message: 'instance_id required' });

    const client = sessions[instance_id];
    if (client) {
        try {
            await client.logout();
            await client.destroy();
            delete sessions[instance_id];
            console.log(`[WA] Session destroyed: ${instance_id}`);
            return res.json({ success: true });
        } catch (error) {
            delete sessions[instance_id];
            return res.status(500).json({ success: false, error: error.message });
        }
    }
    res.json({ success: true, message: 'Already disconnected' });
});

const PORT = process.env.WA_PORT || 3001;
app.listen(PORT, '127.0.0.1', () => {
    console.log(`[WA Server] Running on http://127.0.0.1:${PORT}`);
});