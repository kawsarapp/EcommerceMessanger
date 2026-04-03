<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;

class TelegramApiService
{
    public function sendMessage($token, $chatId, $text)
    {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ]);
    }

    public function sendMessageWithReplyKeyboard($token, $chatId, $text, $keyboard)
    {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => false
            ])
        ]);
    }

    public function sendMessageWithInlineKeyboard($token, $chatId, $text, $keyboard)
    {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }

    public function updateMessageButtons($token, $chatId, $messageId, $text, $keyboard)
    {
        Http::post("https://api.telegram.org/bot{$token}/editMessageText", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }

    public function answerCallback($token, $callbackId, $text)
    {
        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $callbackId,
            'text' => $text
        ]);
    }

    /**
     * Get file path from Telegram (for voice/photo download)
     */
    public function getFile($token, $fileId): array
    {
        $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getFile", [
            'file_id' => $fileId,
        ]);
        return $response->json() ?? [];
    }

    /**
     * Send a photo to a Telegram chat
     */
    public function sendPhoto($token, $chatId, $photoUrl, $caption = '')
    {
        Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendPhoto", [
            'chat_id'    => $chatId,
            'photo'      => $photoUrl,
            'caption'    => $caption,
            'parse_mode' => 'Markdown',
        ]);
    }
}