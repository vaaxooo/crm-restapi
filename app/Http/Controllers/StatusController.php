<?php

namespace App\Http\Controllers;

use App\Services\ServiceStatuses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * @var ServiceStatuses
     */
    private $statuses;

    public function __construct() {
        $this->middleware('auth:api');
        $this->statuses = new ServiceStatuses();
    }

    /**
     * Return all statuses
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        return $this->statuses->all();
    }

    /**
     * Update status name
     * @param  Request  $request
     * @return JsonResponse
     */
    public function updateStatuses(Request $request): JsonResponse
    {
        return $this->statuses->update($request);
    }

}
