<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Section 1: WhatsApp -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <span class="p-2 bg-green-100 rounded-lg text-green-600">💬</span>
                WhatsApp Connection (QR Scanner)
            </h2>
            <div class="mt-4 prose max-w-none text-gray-600">
                <p>আপনার হোয়াটসঅ্যাপ অ্যাকাউন্টটি কানেক্ট করার জন্য নিচের ধাপগুলো অনুসরণ করুন:</p>
                <ol>
                    <li>বামে <b>Clients</b> মেনু থেকে আপনার প্রোফাইলে যান অথবা <b>Settings</b> এ যান।</li>
                    <li><b>WhatsApp API</b> ট্যাবটি সিলেক্ট করুন।</li>
                    <li><b>Select Connection Method</b> থেকে "QR Code Scan" পছন্দ করুন।</li>
                    <li><b>Generate QR Code</b> বাটনে ক্লিক করুন। কিছুক্ষণ অপেক্ষা করলে স্ক্রিনে একটি QR কোড আসবে।</li>
                    <li>আপনার মোবাইলে হোয়াটসঅ্যাপ অ্যাপের <b>Settings -> Linked Devices</b> এ গিয়ে কোডটি স্ক্যান করুন।</li>
                </ol>
                <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400 mt-2">
                    <strong>নোট:</strong> একবার কানেক্ট হয়ে গেলে মোবাইল ইন্টারনেট থেকে ডিসকানেক্ট হলেও AI কাজ করতে থাকবে। তবে সেশন শেষ হয়ে গেলে আপনাকে আবার Rescan করতে হতে পারে।
                </div>
            </div>
        </div>

        <!-- Section 2: WordPress / WooCommerce -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <span class="p-2 bg-blue-100 rounded-lg text-blue-600">🛒</span>
                Real-time Product Sync (SaaS Feature)
            </h2>
            <div class="mt-4 prose max-w-none text-gray-600">
                <p>আপনার ওয়ার্ডপ্রেস বা কাস্টম শপের ডাটা AI বটের সাথে কানেক্ট করার নিয়ম:</p>
                
                <h3 class="text-lg font-semibold mt-4">১. ওয়ার্ডপ্রেস ইউজারদের জন্য:</h3>
                <ul>
                    <li>আমাদের <a href="{{ asset('plugins/ecommerce-messenger-ai.zip') }}" class="text-blue-600 font-bold underline" download>AI Agent Store Sync Plugin</a> টি ডাউনলোড করুন।</li>
                    <li>আপনার ওয়ার্ডপ্রেস ড্যাশবোর্ডে গিয়ে প্লাগিনটি Upload ও Activate করুন।</li>
                    <li>মেনুতে থাকা <b>AI Agent Sync</b> প্যানেল থেকে আপনার সাইটের API URL টি কপি করুন।</li>
                </ul>

                <h3 class="text-lg font-semibold mt-4">২. প্যানেলে কানেক্ট করা:</h3>
                <ul>
                    <li>আপনার এই ড্যাশবোর্ডের <b>Clients -> Store Sync</b> ট্যাবে যান।</li>
                    <li><b>Real-time AI Product Lookup</b> সেকশনে আপনার কপি করা API URL টি বসান।</li>
                    <li>সাথেই থাকা <b>Secret API Key</b> টি যদি প্লাগিনে দিয়ে থাকেন তবে সেটিও এখানে দিন, নয়তো ফাঁকা রাখুন।</li>
                    <li>Save করুন। ব্যাস! এখন থেকে AI আপনার সাইট থেকে লাইভ প্রোডাক্ট ও প্রাইস চেক করবে।</li>
                </ul>
            </div>
        </div>

        <!-- Section 3: Logging & Status -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <span class="p-2 bg-purple-100 rounded-lg text-purple-600">📜</span>
                How to Check Logs & Issues
            </h2>
            <div class="mt-4 prose max-w-none text-gray-600">
                <p>সব কিছু ঠিকমতো কাজ করছে কিনা তা আপনি নিচের উপায়ে চেক করতে পারেন:</p>
                <ul class="space-y-4">
                    <li>
                        <strong>টার্মিনাল লগ (Server Logs):</strong><br>
                        হোয়াটসঅ্যাপ সার্ভার ক্র্যাশ করলে বা কোনো এরর আসলে টার্মিনালে এই কমান্ডটি দিন:<br>
                        <code>npx pm2 logs whatsapp-bot</code>
                    </li>
                    <li>
                        <strong>লারাভেল লগ (Application Logs):</strong><br>
                        AI কি রিপ্লাই দিচ্ছে বা কোনো API এরর হচ্ছে কিনা তা দেখতে আপনার সার্ভারের এই ফাইলে যান:<br>
                        <code>storage/logs/laravel.log</code>
                    </li>
                    <li>
                        <strong>লাইভ ইনবক্স:</strong><br>
                        বামে থাকা <b>Live Inbox</b> থেকে আপনি কাস্টমারের সাথে AI এর লাইভ কথাবার্তা মেসেজ সহ দেখতে পারবেন।
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-filament-panels::page>
