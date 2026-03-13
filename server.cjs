require('dotenv').config();
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
const sessionReadyPromises = {}; // 🌟 Ready ইভেন্ট ট্র্যাক করার জন্য

async function sendWebhookToLaravel(endpoint, data) {
    try {
        // 🔥 Environment variable থেকে লাইভ লিংক ও সিক্রেট নেওয়া হলো
        const myDomain = process.env.APP_URL || 'http://127.0.0.1:8000';
        const secretKey = process.env.WA_WEBHOOK_SECRET || 'super-secret-key';

        await fetch(`${myDomain}/api/v1/whatsapp/${endpoint}`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${secretKey}`
            },
            body: JSON.stringify(data)
        });
    } catch (error) {
        console.error(`Webhook Error to ${endpoint}:`, error.message);
    }
}

// 🌟 কোর ফাংশন: হোয়াটসঅ্যাপ সেশন ইনিশিয়ালাইজ করা (যাতে বারবার কোড লিখতে না হয়)
function initializeWhatsAppClient(instance_id, res = null) {
    if (sessions[instance_id]) {
        if (res) return res.json({ success: true, status: 'connected' });
        return sessions[instance_id];
    }

    console.log(`\n🔄 Initializing session for: ${instance_id}`);

    // Promise setup to track when it becomes ready dynamically
    let resolveReady;
    sessionReadyPromises[instance_id] = new Promise((resolve) => {
        resolveReady = resolve;
    });
    sessionReadyPromises[instance_id].resolve = resolveReady;

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
        
        // Promise resolve করা হলো যাতে manual send message wait করতে পারে
        if (sessionReadyPromises[instance_id] && sessionReadyPromises[instance_id].resolve) {
            sessionReadyPromises[instance_id].resolve();
        }

        // যদি ড্যাশবোর্ড থেকে રিকোয়েস্ট এসে থাকে
        if (res && !res.headersSent) {
            res.json({ success: true, status: 'connected' });
        }
    });

    // 🌟 Message create ইভেন্ট ট্র্যাক (যদি নিজের ফোন থেকে মেসেজ দেয়)
    client.on('message_create', async (msg) => {
        if (msg.fromMe) {
            // Optional: Log outgoing mobile app messages to Laravel if needed 
            // console.log(`📤 Quick reply tracked from mobile to ${msg.to}`);
        }
    });

    client.on('message', async (msg) => {
        if (msg.from === 'status@broadcast') return;

        // 🔥 Group message বা অন্য কোনো system message আসলে ইগনোর করবে
        if (msg.from.includes('@g.us') || msg.from.includes('@newsletter')) {
            console.log("Ignored group/newsletter message");
            return;
        }

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

        // 🌟 আসল ম্যাজিক: কাস্টমারের সঠিক নাম্বার আর আসল নাম বের করা
        let senderName = msg._data?.notifyName || 'Customer';
        let cleanNumber = msg.from.replace('@c.us', ''); // '@c.us' কেটে ক্লিন নাম্বার করা

        try {
            const contact = await msg.getContact();
            senderName = contact.pushname || contact.name || senderName; // WhatsApp profile name
            cleanNumber = contact.number || cleanNumber; // Original phone number
        } catch (e) {
            console.log("Contact fetch error:", e.message);
        }

        console.log(`📩 New message from ${cleanNumber} (${senderName})`);

        sendWebhookToLaravel('receive', {
            instance_id: instance_id,
            from: cleanNumber, // একদম ক্লিন নাম্বার যাবে
            body: messageBody,
            sender_name: senderName, // আসল প্রোফাইল নেম যাবে
            attachment: attachmentBase64
        });
    });

    client.on('disconnected', (reason) => {
        console.log(`❌ Client ${instance_id} was disconnected:`, reason);
        delete sessions[instance_id];
        sendWebhookToLaravel('status', { instance_id: instance_id, status: 'disconnected' });
    });

    client.initialize();
    
    // মেমোরিতে না থাকলেও ইনিশিয়ালাইজ হওয়ার আগেই রিটার্ন করে দেওয়া যাতে send-message ফাংশন এটিকে ব্যবহার করতে পারে
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

    // 🔥 ম্যাজিক: যদি মেমোরিতে সেশন না থাকে, তবে অটোমেটিক ইনিশিয়ালাইজ করে নিবে!
    let client = sessions[instance_id];
    if (!client) {
        console.log(`⚠️ Client not in memory. Auto-connecting from LocalAuth files...`);
        client = initializeWhatsAppClient(instance_id);
        
        console.log("⏳ Waiting dynamically for WhatsApp Ready event...");
        if (sessionReadyPromises[instance_id]) {
            await sessionReadyPromises[instance_id]; 
        }
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
app.post('/api/disconnect', async (req, res) => {
    const { instance_id } = req.body;
    if (!instance_id) return res.status(400).json({ success: false, message: 'Instance ID needed' });

    const client = sessions[instance_id];
    if (client) {
        try {
            await client.logout();
            await client.destroy();
            delete sessions[instance_id];
            console.log(`✅ Session logged out & destroyed for: ${instance_id}`);
            return res.json({ success: true });
        } catch (error) {
            console.error('❌ Error during disconnect:', error);
            delete sessions[instance_id];
            return res.status(500).json({ success: false, error: error.message });
        }
    }
    res.json({ success: true, message: 'Already disconnected' });
});

app.listen(PORT, () => {
    console.log(`🚀 WhatsApp Node Server is running on http://127.0.0.1:${PORT}`);
});