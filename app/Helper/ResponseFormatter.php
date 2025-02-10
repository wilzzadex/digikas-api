<?php

namespace App\Helper;

use Illuminate\Http\JsonResponse;

class ResponseFormatter
{
    public static array $response = [
        'meta' => [
            'code' => 200,
            'status' => 'success',
            'message' => null
        ],
        'data' => null,
    ];

    public static function success($data = null, $message = null, $data_static = null): JsonResponse
    {
        self::$response['meta']['message'] = $message;
        self::$response['data'] = $data;
        self::$response['time'] = date('Y-m-d H:i:s');


        return response()->json(self::$response, self::$response['meta']['code']);
    }

    public static function error($data = null, $message = null, $code = 400): JsonResponse
    {
        self::$response['meta']['status'] = 'error';
        self::$response['meta']['code'] = $code;
        self::$response['meta']['message'] = $message;
        self::$response['data'] = $data;

        return response()->json(self::$response, self::$response['meta']['code']);
    }
}
 