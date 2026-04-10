<?php
$file = 'resources/views/shop/themes/pikabo/cart-checkout.blade.php';
$content = file_get_contents($file);

$c1_old = "        couponCode: '', couponDiscount: 0, couponApplied: false, couponMsg: '', couponOk: false,\n\n        get delivery() {";
$c1_new = "        couponCode: '', couponDiscount: 0, couponApplied: false, couponMsg: '', couponOk: false,\n\n        pointsBalance: {{ \$loyaltyBalance ?? 0 }},\n        pointsValueRate: {{ (int)(\$client->widgets['loyalty']['redemption_value'] ?? 1) }},\n        pointsRedeemed: '', pointsDiscount: 0, pointsMsg: '', pointsOk: false,\n\n        get delivery() {";
$content = str_replace($c1_old, $c1_new, $content);

$c2_old = "get total() { return this.subtotal + this.delivery - this.couponDiscount; },";
$c2_new = "get total() { return Math.max(0, this.subtotal + this.delivery - this.couponDiscount - this.pointsDiscount); },\n\n        applyPoints() {\n            let pts = parseInt(this.pointsRedeemed) || 0;\n            this.pointsMsg = ''; this.pointsOk = false;\n            if (pts <= 0) {\n                this.pointsDiscount = 0;\n                return;\n            }\n            if (pts > this.pointsBalance) {\n                this.pointsMsg = 'Insufficient points!';\n                this.pointsDiscount = 0;\n                return;\n            }\n            this.pointsDiscount = pts * this.pointsValueRate;\n            this.pointsOk = true;\n            this.pointsMsg = `Applied discount of ৳\${this.pointsDiscount}`;\n        },";
$content = str_replace($c2_old, $c2_new, $content);

$c3_old = "<input type=\"hidden\" name=\"coupon_code\" :value=\"couponApplied ? couponCode : ''\">";
$c3_new = "<input type=\"hidden\" name=\"coupon_code\" :value=\"couponApplied ? couponCode : ''\">\n        <input type=\"hidden\" name=\"redeem_points\" :value=\"pointsOk ? pointsRedeemed : 0\">";
$content = str_replace($c3_old, $c3_new, $content);

$c4_old = "{{-- Totals --}}\n                    <div class=\"space-y-2.5 text-sm border-t border-gray-100 pt-4\">";
$c4_new = "{{-- Redeem Points --}}\n                    @if(\$client->widget('loyalty.active') && auth('customer')->check())\n                    <div class=\"mb-5 border-t border-gray-100 pt-5\">\n                        <div class=\"flex justify-between items-center mb-2\">\n                            <span class=\"text-xs font-bold text-dark\">Redeem Points</span>\n                            <span class=\"text-[10px] text-gray-500\">Balance: <strong class=\"text-primary\">{{ \$loyaltyBalance ?? 0 }} pts</strong></span>\n                        </div>\n                        <div class=\"flex gap-2\">\n                            <input type=\"number\" x-model=\"pointsRedeemed\" placeholder=\"Amount to redeem\" min=\"1\" :max=\"pointsBalance\" class=\"vg-input !rounded-lg !py-2.5 text-xs\">\n                            <button type=\"button\" @click=\"applyPoints()\"\n                                    class=\"bg-dark text-white font-bold px-5 rounded-lg hover:bg-primary transition text-xs shrink-0\">Apply</button>\n                        </div>\n                        <p x-show=\"pointsMsg\" class=\"text-xs mt-1\" :class=\"pointsOk ? 'text-green-500' : 'text-red-400'\" x-text=\"pointsMsg\"></p>\n                    </div>\n                    @endif\n\n                    {{-- Totals --}}\n                    <div class=\"space-y-2.5 text-sm border-t border-gray-100 pt-4\">";
$content = str_replace($c4_old, $c4_new, $content);

$c5_old = "                        <div x-show=\"couponDiscount > 0\" class=\"flex justify-between text-green-500\">\n                            <span>Coupon discount</span>\n                            <span class=\"font-medium\">- ৳<span x-text=\"couponDiscount.toLocaleString()\"></span></span>\n                        </div>\n                        <div class=\"flex justify-between font-bold text-dark text-base pt-2 border-t border-gray-100\">";
$c5_new = "                        <div x-show=\"couponDiscount > 0\" class=\"flex justify-between text-green-500\">\n                            <span>Coupon discount</span>\n                            <span class=\"font-medium\">- ৳<span x-text=\"couponDiscount.toLocaleString()\"></span></span>\n                        </div>\n                        <div x-show=\"pointsDiscount > 0\" class=\"flex justify-between text-green-500\">\n                            <span>Points discount</span>\n                            <span class=\"font-medium\">- ৳<span x-text=\"pointsDiscount.toLocaleString()\"></span></span>\n                        </div>\n                        <div class=\"flex justify-between font-bold text-dark text-base pt-2 border-t border-gray-100\">";
$content = str_replace($c5_old, $c5_new, $content);

file_put_contents($file, $content);
echo "Hooks patched successfully.\n";
