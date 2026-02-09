<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;

interface OrderStepInterface
{
    public function process(OrderSession $session, string $userMessage): array;
}