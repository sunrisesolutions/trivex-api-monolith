<?php

declare(strict_types=1);

namespace App\Message\ConsumerStrategy;

use App\Message\Message;

interface StrategyInterface
{
    public function canProcess(Message $message = null, string $queue = null): bool;
    
    public function process(Message $message): void;
}
