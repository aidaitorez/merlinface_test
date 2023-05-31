<?php

namespace App\services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\api\MerlinfaceClient;

class QueueService
{
    private $channel;
    private $queueName;

    public function __construct()
    {
        $host = 'localhost';
        $port = 5672;
        $username = 'guest';
        $password = 'guest';
        $this->queueName = 'task_queue';

        $connection = new AMQPStreamConnection($host, $port, $username, $password);
        $this->channel = $connection->channel();
        $this->channel->queue_declare($this->queueName, false, true, false, false);
    }

    public function addToQueue($data)
    {
        $message = new AMQPMessage(json_encode($data), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

        $this->channel->basic_publish($message, '', $this->queueName);
    }

    public function close()
    {
        $this->channel->close();
    }
}
