<?php

namespace App\Http\Controllers;

use App\Services\ServiceTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public $test;

    public function __construct() {
        $this->middleware('auth:api');
        $this->middleware('permissions');
        $this->test = new ServiceTest();
    }

    /**
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        return $this->test->all();
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        return $this->test->show($id);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        return $this->test->create($request);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        return $this->test->delete($id);
    }
}
