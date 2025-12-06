<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Send a standardized API response
     *
     * @param bool $success
     * @param string $message
     * @param mixed|null $data
     * @param int $code
     * @return JsonResponse
     */
    public function sendResponse(bool $success, string $message, $data = null, int $code = 200): JsonResponse
    {
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];

        return response()->json($response, $code);
    }

    /**
     * Send a standardized error response
     *
     * @param string $message
     * @param int $code
     * @param mixed|null $errors
     * @return JsonResponse
     */
    public function sendError(string $message, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ];

        return response()->json($response, $code);
    }
}
