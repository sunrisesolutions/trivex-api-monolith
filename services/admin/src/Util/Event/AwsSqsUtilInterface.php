<?php
declare(strict_types=1);

namespace App\Util\Event;

use App\Message\Message;

interface AwsSqsUtilInterface
{
    public function createQueue(string $name): ?string;
    
    public function listQueues(): iterable;
    
    public function getQueueUrl(string $name): ?string;
    
    public function sendMessage(string $url, string $message): ?string;
    
    public function getTotalMessages(string $url): string;
    
    public function purgeQueue(string $url): void;
    
    public function deleteQueue(string $url): void;
    
    public function createClient(iterable $config, iterable $credentials): void;
    
//    public function getQueueUrl(string $name): ?string;
    
    public function receiveMessage(string $url, string $name): ?Message;
    
    public function deleteMessage(Message $message): void;
    
    public function requeueMessage(Message $message): void;
}
