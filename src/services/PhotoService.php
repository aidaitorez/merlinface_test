<?php

namespace App\services;

use App\api\MerlinfaceClient;

class PhotoService
{

    public function uploadPhoto($photo)
    {
        $tempFolder = 'sys_get_temp_dir()';
        $photoPath = $tempFolder . '/' . $photo['tmp_name'] . '.' . $photo['type'];

        move_uploaded_file($photo['tmp_name'], $photoPath);

        return $photoPath;
    }
}
