<?php
declare(strict_types=1);

namespace App\Message;

use App\Message\ConsumerStrategy\StrategyInterface;
use App\Message\Message;
use Traversable;

class Consumer implements ConsumerInterface
{
    private $strategies;
    
    public function __construct(Traversable $strategies)
    {
        $this->strategies = $strategies;
    }
    
    public function consume(Message $message, string $queue): void
    {
        /** @var StrategyInterface $strategy */
        foreach ($this->strategies as $strategy) {
            if ($strategy->canProcess($message, $queue)) {
                $strategy->process($message);
                break;
            }
        }
    }
}
