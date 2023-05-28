<?php
require_once __DIR__ . '/vendor/autoload.php';


use App\controllers\APIController;
use App\repositories\TaskRepository;
use App\api\MerlinfaceClient;
use App\services\PhotoService;

// Instantiate the dependencies
$taskRepository = new TaskRepository();
$merlinfaceClient = new MerlinfaceClient();
$photoService = new PhotoService;

// Instantiate the APIController with the dependencies
$apiController = new APIController($taskRepository, $merlinfaceClient, $photoService);

// Get the command-line arguments
$command = isset($argv[1]) ? $argv[1] : '';
$argument1 = isset($argv[2]) ? $argv[2] : '';
$argument2 = isset($argv[3]) ? $argv[3] : '';

// Handle the incoming request based on the command-line arguments
if ($command === 'post') {
    $name = $argument1;
    $photoPath = __DIR__ . '/public/' . $argument2;

    if (empty($argument1) || empty($argument2)) {
        echo 'Missing required arguments. Usage: php index.php post <name> <photo_name>' . PHP_EOL;
        exit;
    }

    if (!file_exists($photoPath)) {
        echo 'Photo file does not exist.';
        exit;
    }

    // Read the photo file contents
    $photoContents = file_get_contents($photoPath);

    // Determine the file MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $photoMime = finfo_file($finfo, $photoPath);
    finfo_close($finfo);

    // Prepare the POST request data
    $photo = [
        'name' => basename($photoPath),
        'type' => $photoMime,
        'tmp_name' => $photoPath,
        'error' => 0,
        'size' => filesize($photoPath)
    ];

    $apiController->handlePostRequest($name, $photo);
} elseif ($command === 'get') {
    $taskId = $argument1;
    if (empty($argument1)) {
        echo 'Missing required argument taskId. Usage: php index.php get <taskId>' . PHP_EOL;
        exit;
    }
    $apiController->handleGetRequest($taskId);
} else {
    // Invalid command
    echo 'Invalid command.';
}
