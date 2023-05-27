<?php

namespace App\controllers;

use App\api\MerlinfaceClient;
use App\repositories\TaskRepository;
use App\services\PhotoService;

class APIController
{

    public function __construct(
        private TaskRepository $taskRepository,
        private MerlinfaceClient $merlinfaceClient,
        private PhotoService $photoService
    ) {
        $this->taskRepository = $taskRepository;
        $this->merlinfaceClient = $merlinfaceClient;
        $this->photoService = $photoService;
    }

    public function handlePostRequest($name, $photo)
    {
        $photoPath = $photo['tmp_name'];

        // Get the file name from the photo path
        $photoName = basename($photo['name']);
        // Check if the user already submitted the same photo
        $existingTask = $this->taskRepository->findTaskByPhotoAndName($name, $photoName);
        if ($existingTask !== null) {
            $result = $existingTask['result'];
            $taskId = $existingTask['id'];
            $this->sendResponse('ready', $result, $taskId);
            exit;
        }
        $this->photoService->uploadPhoto($photo);
        $taskId = $this->taskRepository->createTask($name, $photoName, $photoPath);

        // Return the response
        $this->sendResponse('received', null, $taskId);
        // Send the photo for processing
        $response = $this->merlinfaceClient->sendPhoto($name, $photo);

        // Process the response from the MerlinFace API
        $status = $response['status'];
        if ($status === 'success') {
            $result = $response['result'];
            $this->taskRepository->markTaskAsCompleted($taskId, $result);
            // $this->sendResponse('ready', $result, $taskId);
        } elseif ($status === 'wait') {
            $retryId = $response['retry_id'];
            $this->waitForResult($retryId, $taskId);
        } else {
            // Handle unexpected response
            $this->handleUnexpectedResponse($response);
        }
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

    private function waitForResult($retryId, $taskId)
    {
        sleep(2); // Wait for a few seconds before sending the request again

        // Send a request to check the result using the retryId
        $response = $this->merlinfaceClient->retry($retryId);

        // Process the response from the MerlinFace API
        $status = $response['status'];
        if ($status === 'success') {
            $result = $response['result'];
            $this->taskRepository->markTaskAsCompleted($taskId, $result);
            // $this->sendResponse('ready', $result, $taskId);
        } elseif ($status === 'wait') {
            // Retry recursively until the result is ready
            $this->waitForResult($retryId, $taskId);
        } else {
            $this->handleUnexpectedResponse($response);
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

    private function handleUnexpectedResponse($response)
    {
        $logMessage = 'MerlinFace API Response: ' . json_encode($response);

        // Log to a file
        $logFile = 'logs/api.log';
        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);

        // Or log to the console
        echo $logMessage . PHP_EOL;
    }
}
