<?php

namespace App\Message;

use Doctrine\ORM\EntityManagerInterface;

class MessageFactory
{
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function newMessage($serviceName, $url, $id, $body, $handler)
    {
        $bodyArray = json_decode($body);
//        var_dump($bodyArray);
        $messageObj = json_decode($bodyArray->Message);
        $version = $messageObj->version;

        $class = 'App\\Message\\Entity\\V'.$version.'\\'.ucfirst(strtolower($serviceName)).'Message';
        /** @var Message $message */
        $message = new $class;
        $message->url = $url;
        $message->id = $id;
        $message->receiptHandle = $handler;
        $message->body = $body;

        $message->version = $version;
        $message->data = $messageObj->data;
        return $message;
    }

}