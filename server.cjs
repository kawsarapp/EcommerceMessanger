const express = require('express');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js'); 
const qrcode = require('qrcode');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ limit: '50mb', extended: true }));

// 🌟 মেমোরিতে সেশন রাখার অবজেক্ট
const sessions = {};

async function sendWebhookToLaravel(endpoint, data) {
    try {
        await fetch(`http://127.0.0.1:8000/api/v1/whatsapp/${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
    } catch (error) {
        console.error(`Webhook Error to ${endpoint}:`, error.message);
    }
}

// 🌟 কোর ফাংশন: হোয়াটসঅ্যাপ সেশন ইনিশিয়ালাইজ করা (যাতে বারবার কোড লিখতে না হয়)
function initializeWhatsAppClient(instance_id, res = null) {
    if (sessions[instance_id]) {
        if (res) return res.json({ success: true, status: 'connected' });
        return sessions[instance_id];
    }

    console.log(`\n🔄 Initializing session for: ${instance_id}`);

    const client = new Client({
        authStrategy: new LocalAuth({ clientId: instance_id }),
        puppeteer: { headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] }
    });

    let isQrSent = false;

    client.on('qr', async (qr) => {
        console.log(`📡 QR Code generated for ${instance_id}. Please scan!`);
        if (res && !isQrSent) {
            const qrImage = await qrcode.toDataURL(qr);
            res.json({ success: true, qr_code: qrImage, status: 'scan_required' });
            isQrSent = true;
        }
    });

    client.on('ready', () => {
        console.log(`✅ WhatsApp is READY for ${instance_id}`);
        sessions[instance_id] = client; // মেমোরিতে সেভ করা হলো
        sendWebhookToLaravel('status', { instance_id: instance_id, status: 'connected' });
        
        // যদি ড্যাশবোর্ড থেকে રিকোয়েস্ট এসে থাকে
        if (res && !res.headersSent) {
            res.json({ success: true, status: 'connected' });
        }
    });

    client.on('message', async (msg) => {
        if (msg.from === 'status@broadcast') return;

        try {
            const chat = await msg.getChat();
            await chat.sendSeen(); // Auto Seen
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
                console.log("Error downloading media", err);
            }
        }

        console.log(`📩 New message from ${msg.from}`);

        sendWebhookToLaravel('receive', {
            instance_id: instance_id,
            from: msg.from,
            body: messageBody,
            sender_name: msg._data?.notifyName || 'Customer',
            attachment: attachmentBase64
        });
    });

    client.on('disconnected', (reason) => {
        console.log(`❌ Client ${instance_id} was disconnected:`, reason);
        delete sessions[instance_id];
        sendWebhookToLaravel('status', { instance_id: instance_id, status: 'disconnected' });
    });

    client.initialize();
    
    // মেমোরিতে না থাকলেও ইনিশিয়ালাইজ হওয়ার আগেই রিটার্ন করে দেওয়া যাতে send-message ফাংশন এটিকে ব্যবহার করতে পারে
    sessions[instance_id] = client; 
    return client;
}

// 🟢 QR কোড জেনারেট করার API
app.post('/api/generate-qr', async (req, res) => {
    const { instance_id } = req.body;
    if (!instance_id) return res.status(400).json({ success: false });
    initializeWhatsAppClient(instance_id, res);
});

// 🟢 লারাভেল ড্যাশবোর্ড থেকে ম্যানুয়ালি মেসেজ পাঠানোর API (Smart Auto-Connect)
app.post('/api/send-message', async (req, res) => {
    const { instance_id, to, message, media } = req.body;
    console.log(`\n🚀 [SEND MESSAGE API CALLED] To: ${to}`);

    // 🔥 ম্যাজিক: যদি মেমোরিতে সেশন না থাকে, তবে অটোমেটিক ইনিশিয়ালাইজ করে নিবে!
    let client = sessions[instance_id];
    if (!client) {
        console.log(`⚠️ Client not in memory. Auto-connecting from LocalAuth files...`);
        client = initializeWhatsAppClient(instance_id);
        
        // যেহেতু ইনিশিয়ালাইজ হতে ৩-৪ সেকেন্ড সময় লাগে, তাই একটু অপেক্ষা করব
        console.log("⏳ Waiting 5 seconds for WhatsApp to get Ready...");
        await new Promise(resolve => setTimeout(resolve, 5000));
    }

    try {
        let formattedTo = to;
        if (!formattedTo.includes('@c.us') && !formattedTo.includes('@g.us') && !formattedTo.includes('@lid')) {
             formattedTo = `${formattedTo}@c.us`;
        }

        // Typing Indicator
        let chat = null;
        try {
            chat = await client.getChatById(formattedTo);
            if (chat) {
                await chat.sendStateTyping();
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
        } catch(e) {}

        if (media && media.mimetype && media.data) {
            const attachment = new MessageMedia(media.mimetype, media.data, media.filename || 'attachment');
            await client.sendMessage(formattedTo, message || '', { media: attachment });
        } else {
            await client.sendMessage(formattedTo, message);
        }
        
        if (chat) await chat.clearState();

        console.log(`✅ Success: Message sent to ${formattedTo}!`);
        res.json({ success: true, message: 'Message sent successfully' });

    } catch (error) {
        console.error('❌ Error sending message:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

const PORT = 3001;
app.listen(PORT, () => {
    console.log(`🚀 WhatsApp Node Server is running on http://127.0.0.1:${PORT}`);
});