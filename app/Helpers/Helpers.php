<?php

namespace App\Helpers;

use App\Constants\Constants;
use App\Models\v3\AiPipeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Helpers
{
    public static function getBasicRequestParams(Request $request)
    {
        return [
            "sortBy" => $request->sortBy ?? 'id',
            "per_page" => $request->per_page ?? Helpers::getPagination(),
            "sortDirection" => $request->sortDirection ?? 'desc',
        ];
    }
    public static function getElasticQuerySize($model, $builder)
    {
        $searchResults = $model::searchQuery($builder)
            ->size(0)
            ->execute()->raw();
        return $searchResults['hits']['total']['value'];
    }
    //Return the day name if given day number or return the day number if given day name
    public static function getDayNameOrNumber($day)
    {
        $daysOfWeek = [1 => 'Saturday', 2 => 'Sunday', 3 => 'Monday', 4 => 'Tuesday', 5 => 'Wednesday', 6 => 'Thursday', 7 => 'Friday'];
        if (is_numeric($day) && $day > 0) {
            return $daysOfWeek[$day];
        } else if (is_string($day) && $day !== '') {
            $keys = array_keys(array_filter($daysOfWeek, fn($val) => stripos($val, $day) !== false));
            if (count($keys) > 0) return $keys[0];
        }
        return 'Invalid parameter';
    }

    public static function getLanguages()
    {
        return ['ar', 'en'];
    }

    public static function getPagination()
    {
        return request('paginate') ?? Constants::DASHBOARD_PAGINATION;
    }

    public static function ContextPagination()
    {
        $pagination = request('paginate');
        if ($pagination === 'off') {
            return PHP_INT_MAX;
        }

        return $pagination ?? Constants::DASHBOARD_PAGINATION;
    }

    public static function getCurrentLang()
    {
        return App()->getlocale();
    }

    public static function checkPermissions($permission, $role)
    {
        return in_array($permission->id, $role->permissions()->pluck('id')->toArray()) ? 'checked' : '';
    }

    public static function sendMail($header, $body, $to, $name = 'partner')
    {

        $headers = [
            'Authorization: Bearer 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',
            'Content-Type: application/json',
        ];

        $data = [
            'personalizations' => [
                [
                    'to' => [
                        [
                            'email' => $to,
                            'name' => $name,
                        ],
                    ],
                ],
            ],
            'from' => [
                'email' => 'abgegypt@abgegypt.com',
            ],
            'subject' => $header,
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => '<h1>' . $body . '</h1>',
                ],
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return print_r($response);
    }

    public static function getPaginationAndSorting(Request $request)
    {
        $perPage = $request->input('per_page', Helpers::getPagination());
        $sortBy = $request->input('sortBy', 'id');
        $sortDirection = strtolower($request->input('sort', 'desc'));

        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        return compact('perPage', 'sortBy', 'sortDirection');
    }

    /**
     * Generate a unique cache key for the current patient's request.
     *
     * The cache key is based on the serialized request parameters and the authenticated user's ID.
     * This ensures the cache is specific to the user and request context.
     *
     * @param  \Illuminate\Http\Request  $request  The current incoming request.
     * @return string
     *                - Returns a string that uniquely identifies the cache key for the request.
     */
    public static function generateCacheKey(Request $request, $data, $isPublic = false)
    {
        $requestData = collect($request->all())->except(['photo', 'file', 'attachment']);

        // Ensure no UploadedFile objects are present in the request data
        $requestData = $requestData->filter(function ($value) {
            return !($value instanceof \Illuminate\Http\UploadedFile);
        });


        $requestData = $requestData->toArray();
        $serializedRequest = md5(serialize($requestData));
        $user_id = null;
        if (!$isPublic) {
            $user_id = auth()->user()->id;
        }
        $scoutPrefix = env('SCOUT_PREFIX');
        return "{$data}_user{$user_id}_{$serializedRequest}_{$scoutPrefix}";
    }
    public static function getPaginationMeta($items, $count)
    {
        if (request()->all == 1)  return;
        return [
            'path' => url()->current(),
            'per_page' => $items->perPage(),
            'current_page' => $items->currentPage(),
            'next_page_url' => $items->nextPageUrl(),
            'prev_page_url' => $items->previousPageUrl(),
            'total' => $count ?? $items->total(),
            'last_page' => $items->lastPage(),
        ];
    }
}