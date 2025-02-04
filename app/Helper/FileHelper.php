<?php

namespace App\Helper;

use Illuminate\Http\JsonResponse;

class FileHelper
{
    public static function getFullPathUrl($path, $disk = 'public')
    {
        $minio_url = env('AWS_URL');
        $bucket_name = env('AWS_BUCKET');

        return $minio_url . '/' . $bucket_name . '/' . $path;
    }
}
