<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StartStep implements OrderStepInterface
{
    use OrderTraits;

    public function process(OrderSession $session, string $userMessage, ?string $imageUrl = null): array
    {
        $customerInfo = $session->customer_info ?? [];
        $clientId = $session->client_id;
        
        $msgLower = mb_strtolower(trim($userMessage), 'UTF-8');
        $isGeneralInquiry = preg_match('/(ki ki|ace|menu|list|offer|product|boi|dress|item|ache|koto|dam|price|picture|pic|chobi|ase|ki|details)/i', $msgLower);
        // 🔥 Add 'want to buy' to explicitly capture FB default intents
        $isBuyingIntent = preg_match('/(nibo|kinbo|chai|order|daw|deo|kinte|confirm|pathan|order korbo|want to buy)/i', $msgLower);

        $product = $this->findProductSystematically($clientId, $userMessage);

        if (!$product) {
            $product = $this->getProductFromSession($session->sender_id, $clientId);
        }

        if ($isGeneralInquiry && !$isBuyingIntent && !$product) {
            return [
                'instruction' => "কাস্টমার তথ্য জানতে চাচ্ছে। 'Inventory' ডাটা দেখে সুন্দরভাবে উত্তর দাও। প্রোডাক্টের সঠিক দাম জানাও।",
                'context' => "General Query"
            ];
        }

        if ($product) {
            $finalPrice = ($product->sale_price > 0 && $product->sale_price < $product->regular_price) 
                ? $product->sale_price 
                : $product->regular_price;

            $contextData = [
                'product' => $product->name,
                'price' => $finalPrice . " Tk",
                'detected_variant' => [] 
            ];

            if ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0) {
                return [
                    'instruction' => "দুঃখিত, '{$product->name}' বর্তমানে স্টকে নেই। Inventory থেকে অন্য প্রোডাক্ট সাজেস্ট করো।",
                    'context' => json_encode($contextData)
                ];
            }

            $dbColors = $this->decodeVariants($product->colors);
            $dbSizes = $this->decodeVariants($product->sizes);
            
            $detectedVariant = $this->extractVariant($userMessage, $product);
            $contextData['detected_variant'] = $detectedVariant; 
            
            $missingAttributes = [];
            if (!empty($dbColors) && empty($detectedVariant['color'])) $missingAttributes[] = 'কালার (Color)';
            if (!empty($dbSizes) && empty($detectedVariant['size'])) $missingAttributes[] = 'সাইজ (Size)';

            if (!empty($detectedVariant)) {
                $customerInfo['variant'] = $detectedVariant;
            }
            
            $customerInfo['product_id'] = $product->id;
            
            $nextStep = !empty($missingAttributes) ? 'select_variant' : 'collect_info';
            $customerInfo['step'] = $nextStep;
            $session->update(['customer_info' => $customerInfo]);

            if (!empty($missingAttributes)) {
                $missingStr = implode(' এবং ', $missingAttributes);
                return [
                    'instruction' => "কাস্টমার '{$product->name}' (দাম: {$finalPrice} টাকা) কিনতে আগ্রহী। 🚨 অত্যন্ত জরুরি: কাস্টমারের কাছে এখন কোনো ঠিকানা বা মোবাইল নম্বর চাইবে না! শুধুমাত্র তাকে জিজ্ঞেস করো সে কোন {$missingStr} নিতে চায়।",
                    'context' => json_encode($contextData)
                ];
            } else {
                return [
                    'instruction' => "কাস্টমার '{$product->name}' পছন্দ করেছে। এখন অর্ডারের জন্য তার নাম, ফোন নম্বর এবং ঠিকানা চাও।",
                    'context' => json_encode($contextData)
                ];
            }
        }

        return [
            'instruction' => "কাস্টমার যা খুঁজছে তা Inventory-তে পাওয়া যায়নি। তাকে জানাও যে এটি আমাদের স্টকে নেই এবং অন্য প্রোডাক্ট সাজেস্ট করো।",
            'context' => "Product Not Found"
        ];
    }

    private function extractVariant($msg, $product)
    {
        $msg = mb_strtolower(trim($msg), 'UTF-8');
        $variant = [];

        $colors = $this->decodeVariants($product->colors);
        foreach ($colors as $color) {
            if (str_contains($msg, mb_strtolower($color, 'UTF-8'))) {
                $variant['color'] = $color;
                break;
            }
        }

        $sizes = $this->decodeVariants($product->sizes);
        foreach ($sizes as $size) {
            $s = mb_strtolower($size, 'UTF-8');
            if (preg_match("/\b{$s}\b/", $msg) || $msg === $s) {
                $variant['size'] = $size;
                break;
            }
        }

        return $variant;
    }
}