<?php

namespace App\repositories;

use App\services\QueueService;
use PDO;
use PDOException;

class TaskRepository
{
    private $database;


    public function __construct(private QueueService $queueService)
    {
        $host = '0.0.0.0';
        $port = 3307;
        $dbName = 'testdb';
        $username = 'root';
        $password = 'secret';

        $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";

        try {
            $this->database = new PDO($dsn, $username, $password);
            $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->handleDBException($e);
        }
    }

    public function createTask($name, $photoName, $photoPath)
    {
        try {
            $stmt = $this->database->prepare('INSERT INTO tasks (name, photo_name, photo_path) VALUES (?, ?, ?)');
            $stmt->execute([$name, $photoName, $photoPath]);
            $taskId = $this->database->lastInsertId();
            // Добавление задачи в очередь
            $data = [
                'task_id' => $taskId,
                'name' => $name,
                'photo' => $photoName
            ];
            $this->queueService->addToQueue($data);
            return $taskId;
        } catch (PDOException $e) {
            $this->handleDBException($e);
        }
    }

    public function markTaskAsCompleted($taskId, $result)
    {
        try {
            $stmt = $this->database->prepare('UPDATE tasks SET result = ?, status = ? WHERE id = ?');
            $stmt->execute([$result, 'ready', $taskId]);
        } catch (PDOException $e) {
            $this->handleDBException($e);
        }
    }

    public function findTaskById($taskId)
    {
        try {
            $stmt = $this->database->prepare('SELECT id, status, result FROM tasks WHERE id = ?');
            $stmt->execute([$taskId]);
            $result = $stmt->fetch();

            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            $this->handleDBException($e);
        }
    }

    public function findTaskByPhotoAndName($name, $photoName)
    {
        try {
            $stmt = $this->database->prepare('SELECT id, result FROM tasks WHERE photo_name = ? AND name = ?');
            $stmt->execute([$photoName, $name]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($task !== false) {
                return $task; // Return the task if it exists
            } else {
                return null; // Return null if the task does not exist
            }
        } catch (PDOException $e) {
            $this->handleDBException($e);
        }
    }

    private function handleDBException($error)
    {
        $logMessage = 'Database exception: ' . $error;

        // Log to a file
        $logFile = 'logs/db.log';
        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);

        // Or log to the console
        echo $logMessage . PHP_EOL;
    }
}
