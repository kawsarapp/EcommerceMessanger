<x-filament-panels::page>

<style>
.pg-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.pg-card h2 {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pg-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}
.pg-table th {
    background: #f3f4f6;
    padding: 10px 14px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}
.pg-table td {
    padding: 10px 14px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
    color: #374151;
}
.pg-table tr:hover td { background: #fafafa; }
.badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-green  { background: #dcfce7; color: #166534; }
.badge-red    { background: #fee2e2; color: #991b1b; }
.badge-blue   { background: #dbeafe; color: #1e40af; }
.badge-yellow { background: #fef9c3; color: #854d0e; }
.badge-gray   { background: #f3f4f6; color: #374151; }
.badge-purple { background: #ede9fe; color: #5b21b6; }
.step-box {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding: 14px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    margin-bottom: 10px;
}
.step-num {
    min-width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #3b82f6;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    flex-shrink: 0;
}
.step-body h4 { font-weight: 700; margin-bottom: 4px; color: #1e293b; }
.step-body p  { font-size: 0.85rem; color: #64748b; margin: 0; line-height: 1.5; }
.alert-box {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-size: 0.875rem;
    border-left: 4px solid;
}
.alert-info    { background: #eff6ff; border-color: #3b82f6; color: #1e40af; }
.alert-warning { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
.alert-success { background: #f0fdf4; border-color: #22c55e; color: #166534; }
.alert-danger  { background: #fef2f2; border-color: #ef4444; color: #991b1b; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
.mini-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
}
.mini-card h4 { font-weight: 700; font-size: 0.9rem; margin-bottom: 6px; color: #1e293b; }
.mini-card ul { margin: 0; padding-left: 18px; }
.mini-card ul li { font-size: 0.82rem; color: #64748b; margin-bottom: 3px; }
code {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #0f172a;
    font-family: monospace;
}
.flow-chain {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin: 12px 0;
}
.flow-box {
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
}
.flow-arrow { color: #9ca3af; font-size: 1.2rem; }
</style>

<div style="max-width: 960px; margin: 0 auto; padding: 8px 0;">

    {{-- ============ HEADER ============ --}}
    <div class="pg-card" style="background: linear-gradient(135deg,#1e3a5f 0%,#1e40af 100%); border:none; color:#fff;">
        <h2 style="color:#fff; font-size:1.5rem; margin-bottom:8px;">🔐 Permission System — সম্পূর্ণ গাইড</h2>
        <p style="color:#bfdbfe; font-size:0.9rem; max-width:700px; line-height:1.6;">
            এই পেজে Seller, Staff এবং Super Admin দের জন্য permission system কিভাবে কাজ করে তার বিস্তারিত ব্যাখ্যা দেওয়া হয়েছে।
            কীভাবে Admin Override দিতে হয়, Plans কীভাবে কাজ করে এবং কোথায় কোন permission check হয় — সব বিস্তারিত।
        </p>
    </div>

    {{-- ============ PRIORITY CHAIN ============ --}}
    <div class="pg-card">
        <h2>⛓️ Permission Priority Chain</h2>
        <p style="color:#64748b; font-size:0.85rem; margin-bottom:16px;">
            যখনই কোনো seller বা staff কোনো feature access করতে চায়, নিচের এই order এ check হয়:
        </p>

        <div class="step-box">
            <div class="step-num" style="background:#ef4444;">1</div>
            <div class="step-body">
                <h4>❌ Client Status = <code>inactive</code> → সব বন্ধ</h4>
                <p>যদি seller এর shop status "Suspended/Inactive" থাকে, তাহলে Admin Override সহ সবকিছু বন্ধ। এটাই hardest block। শুধু Super Admin direct DB থেকে বা Admin Panel এর "Plan Extension" থেকে status "active" করলে আবার চালু হবে।</p>
            </div>
        </div>

        <div class="step-box">
            <div class="step-num" style="background:#8b5cf6;">2</div>
            <div class="step-body">
                <h4>🔑 Admin Permission Override আছে? → Override Value ব্যবহার হবে</h4>
                <p>
                    যদি আপনি Seller এর edit page এ গিয়ে "Admin Permission Overrides" tab থেকে কোনো feature toggle/limit set করেন, তাহলে সেটাই final।
                    Plan active আছে কি নেই — কোনো ব্যাপার না। Override সবসময় Plan কে override করে।
                </p>
            </div>
        </div>

        <div class="step-box">
            <div class="step-num" style="background:#f59e0b;">3</div>
            <div class="step-body">
                <h4>📦 Plan Active আছে? → Plan এর Feature Flag দেখো</h4>
                <p>Override নেই — এখন check করা হবে seller এর assigned Plan এ সেই feature আছে কিনা এবং plan expired কিনা। Expired হলে feature বন্ধ।</p>
            </div>
        </div>

        <div class="step-box">
            <div class="step-num" style="background:#22c55e;">4</div>
            <div class="step-body">
                <h4>✅ Plan এর Feature = true → Access দাও</h4>
                <p>Plan active এবং feature enabled → seller access পাবে।</p>
            </div>
        </div>

        <div class="step-box" style="background:#fff1f2; border-color:#fecaca;">
            <div class="step-num" style="background:#94a3b8;">5</div>
            <div class="step-body">
                <h4>⛔ কিছুই না → Access নেই</h4>
                <p>Plan নেই, override নেই, feature disabled → seller কে সেই section দেখাবে না।</p>
            </div>
        </div>

        <div class="flow-chain" style="margin-top:20px; background:#f8fafc; padding:14px; border-radius:10px; border:1px solid #e2e8f0;">
            <div class="flow-box" style="background:#fee2e2; color:#991b1b;">Status = inactive?</div>
            <span class="flow-arrow">→</span>
            <div class="flow-box" style="background:#ef4444; color:#fff;">❌ All Blocked</div>
            <span class="flow-arrow" style="margin-left:10px;">|</span>
            <div class="flow-box" style="background:#ede9fe; color:#5b21b6;">Admin Override?</div>
            <span class="flow-arrow">→</span>
            <div class="flow-box" style="background:#8b5cf6; color:#fff;">✅ Use Override</div>
            <span class="flow-arrow" style="margin-left:10px;">|</span>
            <div class="flow-box" style="background:#fef9c3; color:#854d0e;">Plan Active?</div>
            <span class="flow-arrow">→</span>
            <div class="flow-box" style="background:#22c55e; color:#fff;">✅ Use Plan</div>
            <span class="flow-arrow" style="margin-left:10px;">|</span>
            <div class="flow-box" style="background:#6b7280; color:#fff;">⛔ No Access</div>
        </div>
    </div>

    {{-- ============ USER ROLES ============ --}}
    <div class="pg-card">
        <h2>👥 User Roles — কে কী করতে পারে</h2>

        <div class="grid-3">
            <div class="mini-card" style="border-top: 3px solid #ef4444;">
                <h4>🦸 Super Admin</h4>
                <ul>
                    <li>সব seller এর সব data দেখতে পারে</li>
                    <li>Plan assign/expire করতে পারে</li>
                    <li>Admin Permission Override দিতে পারে</li>
                    <li>যেকোনো seller এর shop manage করতে পারে</li>
                    <li>কোনো permission check apply হয় না</li>
                </ul>
            </div>
            <div class="mini-card" style="border-top: 3px solid #3b82f6;">
                <h4>🏪 Seller</h4>
                <ul>
                    <li>শুধু নিজের shop এর data দেখে</li>
                    <li>Plan অনুযায়ী feature access পায়</li>
                    <li>Admin Override থাকলে plan bypass হয়</li>
                    <li>নিজের Staff create করতে পারে</li>
                    <li>Staff এর permission নিজে set করে</li>
                </ul>
            </div>
            <div class="mini-card" style="border-top: 3px solid #22c55e;">
                <h4>👤 Staff</h4>
                <ul>
                    <li>শুধু নিজের assigned shop এর data দেখে</li>
                    <li>Seller যে permission দেয় শুধু সেটুকুই পারে</li>
                    <li>Seller এর Admin Override inherit করে</li>
                    <li>Staff নিজে অন্য staff বানাতে পারে না</li>
                    <li>Feature access = Seller plan এর subset</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ============ ADMIN OVERRIDE GUIDE ============ --}}
    <div class="pg-card">
        <h2>🔑 Admin Override — কীভাবে দেবেন?</h2>

        <div class="alert-info" style="margin-bottom:16px;">
            <strong>📍 কোথায় পাবেন:</strong> Admin Panel → Clients → (যেকোনো Seller) Edit → <strong>"Admin Permission Overrides"</strong> Tab
        </div>

        <div class="grid-2">
            <div>
                <h4 style="font-weight:700; margin-bottom:12px; color:#1e293b;">🤖 AI Access Override</h4>
                <table class="pg-table">
                    <tr>
                        <th>Field</th>
                        <th>কাজ</th>
                    </tr>
                    <tr>
                        <td><code>allow_ai</code></td>
                        <td>Plan ছাড়াও AI Bot চালু করে</td>
                    </tr>
                    <tr>
                        <td><code>allow_own_api_key</code></td>
                        <td>নিজের OpenAI/Gemini key ব্যবহার করতে পারবে</td>
                    </tr>
                    <tr>
                        <td><code>allowed_ai_models</code></td>
                        <td>কোন AI মডেল ব্যবহার করতে পারবে</td>
                    </tr>
                </table>
            </div>
            <div>
                <h4 style="font-weight:700; margin-bottom:12px; color:#1e293b;">📊 Limit Override</h4>
                <table class="pg-table">
                    <tr>
                        <th>Field</th>
                        <th>মান</th>
                    </tr>
                    <tr>
                        <td><code>product_limit</code></td>
                        <td><code>0</code> = Unlimited, <code>50</code> = 50টি</td>
                    </tr>
                    <tr>
                        <td><code>order_limit</code></td>
                        <td><code>0</code> = Unlimited monthly orders</td>
                    </tr>
                    <tr>
                        <td><code>ai_message_limit</code></td>
                        <td>মাসে কতটি AI reply পাবে</td>
                    </tr>
                    <tr>
                        <td><code>staff_account_limit</code></td>
                        <td>কতজন staff বানাতে পারবে</td>
                    </tr>
                </table>
            </div>
        </div>

        <h4 style="font-weight:700; margin: 20px 0 12px; color:#1e293b;">💼 Feature Override — সব features</h4>
        <table class="pg-table">
            <thead>
                <tr>
                    <th>Feature Key</th>
                    <th>কী করে</th>
                    <th>ON করলে</th>
                    <th>OFF করলে</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>allow_coupon</code></td>
                    <td>Coupon System</td>
                    <td><span class="badge badge-green">✅ Coupon বানাতে পারবে</span></td>
                    <td><span class="badge badge-red">❌ Menu থেকে hidden</span></td>
                </tr>
                <tr>
                    <td><code>allow_review</code></td>
                    <td>Review System</td>
                    <td><span class="badge badge-green">✅ Reviews দেখতে/যোগ করতে পারবে</span></td>
                    <td><span class="badge badge-red">❌ Menu থেকে hidden</span></td>
                </tr>
                <tr>
                    <td><code>allow_custom_domain</code></td>
                    <td>Custom Domain</td>
                    <td><span class="badge badge-green">✅ নিজস্ব domain যোগ করতে পারবে</span></td>
                    <td><span class="badge badge-red">❌ Domain option দেখবে না</span></td>
                </tr>
                <tr>
                    <td><code>allow_analytics</code></td>
                    <td>Analytics Dashboard</td>
                    <td><span class="badge badge-green">✅ Sales analytics দেখবে</span></td>
                    <td><span class="badge badge-red">❌ Analytics hidden</span></td>
                </tr>
                <tr>
                    <td><code>allow_marketing_broadcast</code></td>
                    <td>Broadcast</td>
                    <td><span class="badge badge-green">✅ Mass message পাঠাতে পারবে</span></td>
                    <td><span class="badge badge-red">❌ Broadcast hidden</span></td>
                </tr>
                <tr>
                    <td><code>allow_abandoned_cart</code></td>
                    <td>Abandoned Cart</td>
                    <td><span class="badge badge-green">✅ Incomplete orders দেখবে</span></td>
                    <td><span class="badge badge-red">❌ Section hidden</span></td>
                </tr>
                <tr>
                    <td><code>allow_whatsapp</code></td>
                    <td>WhatsApp Bot</td>
                    <td><span class="badge badge-green">✅ WhatsApp connect করতে পারবে</span></td>
                    <td><span class="badge badge-red">❌ WhatsApp section hidden</span></td>
                </tr>
                <tr>
                    <td><code>allow_telegram</code></td>
                    <td>Telegram Bot</td>
                    <td><span class="badge badge-green">✅ Telegram Bot চালু করতে পারবে</span></td>
                    <td><span class="badge badge-red">❌ Telegram hidden</span></td>
                </tr>
                <tr>
                    <td><code>allow_api_access</code></td>
                    <td>API Access</td>
                    <td><span class="badge badge-green">✅ API Token ব্যবহার করতে পারবে</span></td>
                    <td><span class="badge badge-red">❌ API access blocked</span></td>
                </tr>
                <tr>
                    <td><code>allow_delivery_integration</code></td>
                    <td>Courier Integration</td>
                    <td><span class="badge badge-green">✅ Steadfast/Pathao/RedX connect</span></td>
                    <td><span class="badge badge-red">❌ Courier Reports hidden</span></td>
                </tr>
                <tr>
                    <td><code>allow_facebook_messenger</code></td>
                    <td>Facebook Messenger</td>
                    <td><span class="badge badge-green">✅ FB Comments manage করতে পারবে</span></td>
                    <td><span class="badge badge-red">❌ Social section hidden</span></td>
                </tr>
                <tr>
                    <td><code>remove_branding</code></td>
                    <td>Remove Branding</td>
                    <td><span class="badge badge-green">✅ "Powered by" footer সরে যাবে</span></td>
                    <td><span class="badge badge-gray">Footer branding থাকবে</span></td>
                </tr>
                <tr>
                    <td><code>allow_premium_themes</code></td>
                    <td>Premium Themes</td>
                    <td><span class="badge badge-green">✅ সব premium theme access</span></td>
                    <td><span class="badge badge-red">❌ শুধু free theme</span></td>
                </tr>
                <tr>
                    <td><code>allow_payment_gateway</code></td>
                    <td>Payment Gateway</td>
                    <td><span class="badge badge-green">✅ Online payment চালু করতে পারবে</span></td>
                    <td><span class="badge badge-red">❌ শুধু COD</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ============ STAFF PERMISSIONS ============ --}}
    <div class="pg-card">
        <h2>👤 Staff Permission System</h2>
        <p style="color:#64748b; font-size:0.85rem; margin-bottom:16px;">
            Seller তার Staff এর জন্য granular permission দিতে পারে। Staff শুধুমাত্র Seller যা দেয় তাই করতে পারবে —
            Seller এর Plan এর বাইরে কিছু পারবে না।
        </p>

        <div class="alert-warning">
            <strong>⚠️ Important:</strong> Staff এর permission = Seller Plan এর <strong>subset</strong>।
            Seller এর plan এ যদি Coupon না থাকে, তাহলে Staff কে Coupon permission দিলেও কাজ করবে না।
        </div>

        <table class="pg-table" style="margin-top:16px;">
            <thead>
                <tr>
                    <th>Staff Permission Key</th>
                    <th>কোন Section</th>
                    <th>কী করতে পারবে</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>view_orders</code></td>
                    <td>Orders</td>
                    <td>অর্ডার লিস্ট দেখতে পারবে</td>
                </tr>
                <tr>
                    <td><code>edit_orders</code></td>
                    <td>Orders</td>
                    <td>অর্ডার edit ও নতুন অর্ডার তৈরি করতে পারবে</td>
                </tr>
                <tr>
                    <td><code>delete_orders</code></td>
                    <td>Orders</td>
                    <td>অর্ডার delete করতে পারবে</td>
                </tr>
                <tr>
                    <td><code>view_products</code></td>
                    <td>Products / Categories</td>
                    <td>প্রোডাক্ট ও ক্যাটাগরি দেখতে পারবে</td>
                </tr>
                <tr>
                    <td><code>edit_products</code></td>
                    <td>Products / Categories</td>
                    <td>প্রোডাক্ট তৈরি ও edit করতে পারবে</td>
                </tr>
                <tr>
                    <td><code>delete_products</code></td>
                    <td>Products</td>
                    <td>প্রোডাক্ট delete করতে পারবে</td>
                </tr>
                <tr>
                    <td><code>view_customers</code></td>
                    <td>Inbox / Social Comments</td>
                    <td>Customer conversations ও comments দেখতে পারবে</td>
                </tr>
                <tr>
                    <td><code>view_coupons</code></td>
                    <td>Coupons</td>
                    <td>Coupon তৈরি, edit, delete করতে পারবে</td>
                </tr>
                <tr>
                    <td><code>view_reviews</code></td>
                    <td>Reviews</td>
                    <td>Review manage করতে পারবে</td>
                </tr>
                <tr>
                    <td><code>view_reports</code></td>
                    <td>Courier Reports</td>
                    <td>Courier delivery রিপোর্ট দেখতে পারবে</td>
                </tr>
                <tr>
                    <td><code>view_abandoned</code></td>
                    <td>Abandoned Carts</td>
                    <td>অসম্পূর্ণ কার্ট দেখতে ও reminder পাঠাতে পারবে</td>
                </tr>
                <tr>
                    <td><code>add_notes</code></td>
                    <td>Orders</td>
                    <td>অর্ডারে internal note যোগ করতে পারবে</td>
                </tr>
                <tr>
                    <td><code>export_orders</code></td>
                    <td>Orders</td>
                    <td>Google Sheets বা CSV এ export করতে পারবে</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ============ PLAN SYSTEM ============ --}}
    <div class="pg-card">
        <h2>📦 Plan System — কীভাবে কাজ করে</h2>
        <div class="grid-2">
            <div>
                <div class="step-box">
                    <div class="step-num" style="background:#3b82f6;">①</div>
                    <div class="step-body">
                        <h4>Plan Assign করুন</h4>
                        <p>Clients → Edit → "Plan Extension" section → Plan select করুন এবং <strong>Plan Expires On</strong> date set করুন।</p>
                    </div>
                </div>
                <div class="step-box">
                    <div class="step-num" style="background:#3b82f6;">②</div>
                    <div class="step-body">
                        <h4>Status "Active" রাখুন</h4>
                        <p>Shop Status অবশ্যই <code>✅ Active</code> থাকতে হবে। <code>❌ Suspended</code> হলে সব বন্ধ।</p>
                    </div>
                </div>
                <div class="step-box">
                    <div class="step-num" style="background:#3b82f6;">③</div>
                    <div class="step-body">
                        <h4>Plan Renew</h4>
                        <p>মেয়াদ শেষ হলে "Plan Expires On" date update করুন। Seller automatically নতুন মেয়াদ পাবে।</p>
                    </div>
                </div>
            </div>
            <div>
                <div class="alert-success">
                    <strong>✅ Plan থাকলে কী হয়?</strong><br>
                    Plan এ যে features ON করা আছে, seller সেগুলো access করতে পারবে।
                    Limits (products, orders, AI) plan অনুযায়ী enforce হবে।
                </div>
                <div class="alert-warning" style="margin-top:10px;">
                    <strong>⚠️ Plan Expire হলে?</strong><br>
                    Seller panel এ login করতে পারবে কিন্তু feature-specific sections (Coupon, Review etc.) hidden হয়ে যাবে। Orders এবং Products দেখতে পারবে।
                </div>
                <div class="alert-info" style="margin-top:10px;">
                    <strong>💡 Admin Override + No Plan?</strong><br>
                    Plan না থাকলেও Admin Override দিলে এবং status <code>active</code> রাখলে seller কাজ করতে পারবে।
                    এটা trial/test seller দের জন্য useful।
                </div>
            </div>
        </div>
    </div>

    {{-- ============ QUICK SCENARIOS ============ --}}
    <div class="pg-card">
        <h2>💡 Common Scenarios — কোন কাজ কীভাবে করবেন</h2>

        <table class="pg-table">
            <thead>
                <tr>
                    <th>Scenario</th>
                    <th>কী করবেন</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>🆓 Seller কে trial দিতে চাই, plan নেই</td>
                    <td>Clients → Edit → Admin Overrides এ প্রয়োজনীয় features ON করুন + Status = <code>active</code> রাখুন</td>
                </tr>
                <tr>
                    <td>⬆️ Seller কে extra features দিতে চাই plan এর বাইরে</td>
                    <td>Admin Override এ সেই specific feature toggle ON করুন — plan change করতে হবে না</td>
                </tr>
                <tr>
                    <td>⬇️ Seller এর একটি feature বন্ধ করতে চাই (plan এ থাকলেও)</td>
                    <td>Admin Override এ সেই feature explicitly OFF করুন — plan ignore হবে</td>
                </tr>
                <tr>
                    <td>🚫 Seller কো সম্পূর্ণ বন্ধ করতে চাই</td>
                    <td>Status = <code>❌ Suspended</code> করুন — Admin Override সহ সব বন্ধ হবে</td>
                </tr>
                <tr>
                    <td>♾️ Seller কে unlimited products দিতে চাই</td>
                    <td>Admin Override → Max Products = <code>0</code> সেট করুন (0 = Unlimited)</td>
                </tr>
                <tr>
                    <td>📅 Plan মেয়াদ বাড়াতে চাই</td>
                    <td>Clients → Edit → Plan Extension → "Plan Expires On" date update করুন</td>
                </tr>
                <tr>
                    <td>👤 Staff এর একটি permission সরাতে চাই</td>
                    <td>Staff → Edit → সেই permission checkbox uncheck করুন</td>
                </tr>
                <tr>
                    <td>🔄 Admin Override সরিয়ে শুধু Plan এ ফিরতে চাই</td>
                    <td>Admin Override এর সেই field clear/delete করুন — তাহলে আবার Plan follow করবে</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ============ TECHNICAL NOTE ============ --}}
    <div class="pg-card" style="background:#1e293b; border-color:#334155;">
        <h2 style="color:#f1f5f9;">⚙️ Technical Reference — Developer Notes</h2>
        <div class="grid-2">
            <div>
                <p style="color:#94a3b8; font-size:0.82rem; margin-bottom:8px; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Key Methods (Client Model)</p>
                <table style="width:100%; font-size:0.8rem; border-collapse:collapse;">
                    <tr>
                        <td style="padding:6px 0; color:#7dd3fc; font-family:monospace; border-bottom:1px solid #334155;">hasActivePlan()</td>
                        <td style="padding:6px 0; color:#94a3b8; border-bottom:1px solid #334155; padding-left:12px;">Plan বা Admin Override দিয়ে active কিনা</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#7dd3fc; font-family:monospace; border-bottom:1px solid #334155;">canAccessFeature('key')</td>
                        <td style="padding:6px 0; color:#94a3b8; border-bottom:1px solid #334155; padding-left:12px;">Override → Plan check করে feature access দেয়</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#7dd3fc; font-family:monospace; border-bottom:1px solid #334155;">getEffectiveLimit('key')</td>
                        <td style="padding:6px 0; color:#94a3b8; border-bottom:1px solid #334155; padding-left:12px;">Override বা Plan থেকে effective limit বের করে</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#7dd3fc; font-family:monospace;">hasAdminOverrideFor('key')</td>
                        <td style="padding:6px 0; color:#94a3b8; padding-left:12px;">ওই feature এ override set আছে কিনা</td>
                    </tr>
                </table>
            </div>
            <div>
                <p style="color:#94a3b8; font-size:0.82rem; margin-bottom:8px; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Data Storage</p>
                <table style="width:100%; font-size:0.8rem; border-collapse:collapse;">
                    <tr>
                        <td style="padding:6px 0; color:#86efac; font-family:monospace; border-bottom:1px solid #334155;">clients.admin_permissions</td>
                        <td style="padding:6px 0; color:#94a3b8; border-bottom:1px solid #334155; padding-left:12px;">JSON — Admin Override values</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#86efac; font-family:monospace; border-bottom:1px solid #334155;">clients.plan_id</td>
                        <td style="padding:6px 0; color:#94a3b8; border-bottom:1px solid #334155; padding-left:12px;">Assigned Plan</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#86efac; font-family:monospace; border-bottom:1px solid #334155;">clients.plan_ends_at</td>
                        <td style="padding:6px 0; color:#94a3b8; border-bottom:1px solid #334155; padding-left:12px;">Plan expiry datetime</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#86efac; font-family:monospace; border-bottom:1px solid #334155;">clients.status</td>
                        <td style="padding:6px 0; color:#94a3b8; border-bottom:1px solid #334155; padding-left:12px;">active / inactive / trial</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#86efac; font-family:monospace;">users.staff_permissions</td>
                        <td style="padding:6px 0; color:#94a3b8; padding-left:12px;">JSON — Staff granular permissions</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</div>

</x-filament-panels::page>
