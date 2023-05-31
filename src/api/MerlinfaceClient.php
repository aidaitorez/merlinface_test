<?php

namespace App\api;


use App\repositories\TaskRepository;

class MerlinfaceClient
{
    public function sendPhoto($name, $photoPath)
    {
        // file_put_contents('logs/api.log', var_export($photoPath, true), FILE_APPEND);
        $url = 'http://merlinface.com:12345/api/';
        $fields = [
            'name' => $name,
            'photo' => curl_file_create($photoPath['tmp_name'], $photoPath['type'], $photoPath['name'])
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        return $data;
    }



    // public function processMerlinfaceResponse($response, $taskId)
    // {
    //     $taskRepository = new TaskRepository();
    //     $status = $response['status'];


    //     if ($status === 'success') {
    //         $result = $response['result'];
    //         $taskRepository->markTaskAsCompleted($taskId, $result);
    //         // $this->sendResponse('ready', $result, $taskId);
    //     } elseif ($status === 'wait') {
    //         $retryId = $response['retry_id'];
    //         $this->waitForResult($retryId, $taskId);
    //     } else {
    //         $this->handleUnexpectedResponse($response);
    //     }
    // }

    public function retry($retry_id)
    {
        $url = 'http://merlinface.com:12345/api/';
        $fields = [
            'retry_id' => $retry_id
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        return $data;
    }

    // public function waitForResult($retryId, $taskId)
    // {
    //     sleep(2); // Wait for a few seconds before sending the request again

    //     // Send a request to check the result using the retryId
    //     $response = $this->retry($retryId);

    //     // Process the response from the MerlinFace API
    //     $this->processMerlinfaceResponse($response, $taskId);
    // }

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
