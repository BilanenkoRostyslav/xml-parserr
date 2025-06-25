<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    public function json(Arrayable $data, string $message = '', int $statusCode = 200)
    {
        $response = [
            'data' => $data->toArray(),
            'message' => $message,
        ];
        return new JsonResponse($response, $statusCode);
    }
}
