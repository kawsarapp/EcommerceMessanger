const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());

// সব সেলারের হোয়াটসঅ্যাপ সেশন এখানে সেভ থাকবে
const sessions = {};

// কিউআর কোড জেনারেট করার API
app.post('/api/generate-qr', async (req, res) => {
    const { instance_id } = req.body;

    if (!instance_id) {
        return res.status(400).json({ success: false, message: 'instance_id is required' });
    }

    // যদি আগে থেকেই কানেক্টেড থাকে
    if (sessions[instance_id]) {
        return res.json({ success: true, message: 'Already connected', status: 'connected' });
    }

    console.log(`Initializing session for: ${instance_id}`);

    const client = new Client({
        authStrategy: new LocalAuth({ clientId: instance_id }),
        puppeteer: { headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] }
    });

    let isQrSent = false;

    // যখন হোয়াটসঅ্যাপ QR Code দিবে
    client.on('qr', async (qr) => {
        console.log(`QR Code generated for ${instance_id}`);
        if (!isQrSent) {
            const qrImage = await qrcode.toDataURL(qr);
            res.json({ success: true, qr_code: qrImage, status: 'scan_required' });
            isQrSent = true;
        }
    });

    // যখন সেলার স্ক্যান করবে এবং কানেক্ট হবে
    client.on('ready', () => {
        console.log(`WhatsApp is READY for ${instance_id}`);
        sessions[instance_id] = client;
        
        // পরবর্তীতে এখানে আমরা লারাভেলে একটি Webhook পাঠাবো স্ট্যাটাস আপডেট করার জন্য
    });

    client.on('disconnected', (reason) => {
        console.log(`Client ${instance_id} was disconnected:`, reason);
        delete sessions[instance_id];
    });

    client.initialize();
});

// সার্ভার চালু করা
const PORT = 3001;
app.listen(PORT, () => {
    console.log(`🚀 WhatsApp Node Server is running on http://127.0.0.1:${PORT}`);
});