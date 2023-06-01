<?php

namespace App\controllers;


use App\repositories\TaskRepository;
use App\services\PhotoService;
use App\services\QueueService;
use App\api\MerlinfaceClient;


class APIController
{

    public function __construct(
        private TaskRepository $taskRepository,
        private MerlinfaceClient $merlinfaceClient,
        private PhotoService $photoService,
        private QueueService $queueService
    ) {
    }



    public function handlePostRequest($name, $photo)
    {
        $this->photoService->uploadPhoto($photo);
        $photoPath = $photo['tmp_name'];
        $photoName = basename($photo['name']);
        // Check if the user already submitted the same photo
        $existingTask = $this->taskRepository->findTaskByPhotoAndName($name, $photoName);
        if ($existingTask !== null) {
            $result = $existingTask['result'];
            $taskId = $existingTask['id'];
            $this->sendResponse('ready', $result, $taskId);
            exit;
        }

        $taskId = $this->taskRepository->createTask($name, $photoName, $photoPath);

        $photo = [
            'name' => $photoName,
            'type' => $photo['type'],
            'tmp_name' => $photoPath
        ];
        $taskData = ['task_id' => $taskId, 'name' => $name, 'photo' =>  $photo];

        // Add the task to the queue for processing
        $this->queueService->addToQueue($taskData);

        $this->sendResponse('received', null, $taskId);
    }


    public function handleGetRequest($taskId)
    {
        // Retrieve the task result from the database
        $task = $this->taskRepository->findTaskById($taskId);

        if ($task === null) {
            // Task not found
            $this->sendResponse('not_found');
            return;
        }

        if ($task['status'] === 'ready') {
            // Task result is ready
            $result = $task['result'];
            $this->sendResponse('ready', $result, $task['id']);
        } else {
            // Task result is not ready yet
            $this->sendResponse('wait');
        }
    }


    private function sendResponse($status, $result = null, $taskId = null)
    {
        $response = [
            'status' => $status,
            'result' => $result,
            'task' => $taskId,
        ];

        // Set the appropriate HTTP response code
        $statusCode = $status === 'ready' ? 200 : 202;
        http_response_code($statusCode);

        // Send the response as JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    }
}
