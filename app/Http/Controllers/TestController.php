<?php

namespace App\Http\Controllers;

use App\Services\ServiceTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public $test;

    public function __construct()
    {
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
     * @param $id
     * @return JsonResponse
     */
    public function answers($id): JsonResponse
    {
        return $this->test->answers($id);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function statistics($id): JsonResponse
    {
        return $this->test->statistics($id);
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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        return $this->test->update($request, $id);
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
