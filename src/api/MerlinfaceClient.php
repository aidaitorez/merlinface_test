<?php

namespace App\api;


use App\repositories\TaskRepository;

class MerlinfaceClient
{
    public function sendPhoto($name, $photoPath)
    {

        $url = 'http://merlinface.com:12345/api/';
        $fields = [
            'name' => $name,
            'photo' => curl_file_create($photoPath['tmp_name'], $photoPath['type'], $photoPath['name'])
        ];
        file_put_contents('logs/api.log', var_export($fields, true), FILE_APPEND);
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


    public function handleUnexpectedResponse($response)
    {
        $logMessage = 'MerlinFace API Response: ' . json_encode($response);

        // Log to a file
        $logFile = 'logs/api.log';
        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);

        // Or log to the console
        echo $logMessage . PHP_EOL;
    }
}
