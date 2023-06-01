<?php

namespace App\services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\api\MerlinfaceClient;

class QueueService
{
    public $channel;
    public $queueName;
    public $connection;

    public function __construct()
    {
        $host = 'localhost';
        $port = 5672;
        $username = 'guest';
        $password = 'guest';
        $this->queueName = 'task_queue';

        $this->connection = new AMQPStreamConnection($host, $port, $username, $password);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueName, false, true, false, false);
    }

    public function addToQueue($data)
    {
        // file_put_contents('logs/api.log', var_export($data, true), FILE_APPEND);
        $message = new AMQPMessage(json_encode($data), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

        $this->channel->basic_publish($message, '', $this->queueName);
    }

    public function addToRetryQueue($taskId, $retryId)
    {
        $channel = $this->connection->channel();

        $channel->queue_declare('retry_queue', false, true, false, false);

        $retryData = [
            'id' => $retryId,
            'task_id' => $taskId,
        ];

        $message = new AMQPMessage(json_encode($retryData), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

        $channel->basic_publish($message, '', 'retry_queue');

        $channel->close();
    }

    public function getNextTaskFromQueue()
    {
        $channel = $this->connection->channel();

        $channel->queue_declare('task_queue', false, true, false, false);

        $message = $channel->basic_get('task_queue');

        if ($message) {
            $taskData = json_decode($message->body, true);

            $channel->basic_ack($message->delivery_info['delivery_tag']);

            $channel->close();

            return $taskData;
        }

        $channel->close();

        return null;
    }

    public function getNextRetryFromQueue()
    {
        $channel = $this->connection->channel();

        $channel->queue_declare('retry_queue', false, true, false, false);

        $message = $channel->basic_get('retry_queue');

        if ($message) {
            $retryData = json_decode($message->body, true);

            $channel->basic_ack($message->delivery_info['delivery_tag']);

            $channel->close();

            return $retryData;
        }

        $channel->close();

        return null;
    }
    public function close()
    {
        $this->channel->close();
    }
}
