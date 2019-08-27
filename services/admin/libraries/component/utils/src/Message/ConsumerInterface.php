<?php
declare(strict_types=1);

namespace App\Message;

use App\Message\Message;

interface ConsumerInterface
{
    public function consume(Message $message, string $queue): void;
}