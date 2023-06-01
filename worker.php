<?php

namespace App;

use App\Services\QueueService;
use App\Api\MerlinfaceClient;
use App\repositories\TaskRepository;

class Worker
{
    private QueueService $queueService;
    private MerlinfaceClient $merlinfaceClient;
    private TaskRepository $taskRepository;

    public function __construct(
        QueueService $queueService,
        MerlinfaceClient $merlinfaceClient,
        TaskRepository $taskRepository
    ) {
        $this->queueService = $queueService;
        $this->merlinfaceClient = $merlinfaceClient;
        $this->taskRepository = $taskRepository;
    }

    public function run()
    {
        while (true) {
            // Retrieve a task from the queue
            $task = $this->queueService->getNextTaskFromQueue();

            if ($task) {
                // file_put_contents('logs/api.log', var_export($task, true), FILE_APPEND);
                $this->processTask($task);
            } else {
                // Sleep for some time if the queue is empty
                sleep(1);
            }
        }
    }

    private function processTask($task)
    {

        $taskId = $task['task_id'];
        $name = $task['name'];
        $photo = $task['photo'];


        // Send the photo for processing
        $response = $this->merlinfaceClient->sendPhoto($name, $photo);

        $status = $response['status'];

        if ($status === 'success') {
            $result = $response['result'];
            $this->taskRepository->markTaskAsCompleted($taskId, $result);
        } elseif ($status === 'wait') {
            $retryId = $response['retry_id'];
            $this->retryTask($taskId, $retryId);
        } else {
            $this->merlinfaceClient->handleUnexpectedResponse($response);
        }
    }

    private function retryTask($taskId, $retryId)
    {
        // Add the task back to the queue with the same taskId and retryId
        $this->queueService->addToRetryQueue($taskId, $retryId);
    }
}
