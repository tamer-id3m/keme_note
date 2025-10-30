<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    protected function apiResponse(string $message, int $status = 200, $data = [], $pagination = null): JsonResponse
    {
        $response = [
            'message' => $message,
            'status_code' => $status,
            'data' => $data,
        ];

        if ($pagination && request()->all != 1) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $status);
    }

    /**
     * Method successResponse
     * @param string $message
     * @param mixed|null $data
     * @return JsonResponse
     */

    protected function successResponse($message = '', $data = null)
    {
        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'status_code' => 200,
                'data' => $data

            ],
            200
        );
    }

    /**
     * Method errorResponse
     *
     * @param string $message
     * @param int $errorCode default HTTP_STATUS_CODE = 400
     * @param mixed $data default null
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message = '',
        $errorCode = 400,
        $data = null
    ) {
        $response = [
            'success' => false,
            'message' => $message,
            'status_code' => $errorCode,
            'data' => $data
        ];
        return $errorCode ? response()->json($response, $errorCode) : response()->json($response);
    }
}