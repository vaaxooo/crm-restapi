<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ServiceManager;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class ManagerController extends Controller
{
    private $manager;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permissions', ['except' => ['getPassedTests', 'getPassedTest']]);
        $this->manager = new ServiceManager();
    }

    /**
     * Get list with all managers
     * @param  Request  $request
     * @return JsonResponse
     */
    public function allManagers(Request $request): JsonResponse
    {
        return $this->manager->all($request);
    }

    /**
     * Show all information about the manager
     * @param $id
     * @return JsonResponse
     */
    public function showManager($id): JsonResponse
    {
        return response()->json([
            'status' => TRUE,
            'data' => User::select('login', 'first_name', 'last_name',
                'surname', 'role', 'email', 'created_at')->where('id', $id)->where('role',
                'manager')->first(),
        ]);
    }

    /**
     * Adding a new manager to the database
     * @param  Request  $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function addManager(Request $request): JsonResponse
    {
        return $this->manager->create($request);
    }

    /**
     * Update manager data
     * @param  Request  $request
     * @param           $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateManager(Request $request, $id): JsonResponse
    {
        return $this->manager->update($request, $id);
    }

    /**
     * Delete manager from database
     * @param $id
     * @return JsonResponse
     */
    public function deleteManager($id): JsonResponse
    {
        return $this->manager->delete($id);
    }

    /**
     * Returns all statistics for managers
     * @return JsonResponse
     */
    public function statistic(): JsonResponse
    {
        return $this->manager->statistic();
    }

    /**
     * Refresh Manager last online (timestamp)
     * @param $id
     * @return JsonResponse
     */
    public function refreshOnline($id): JsonResponse
    {
        return $this->manager->refreshOnline($id);
    }

    /**
     * Get a list of the tests passed by the manager
     * @param $id
     * @return JsonResponse
     */
    public function getPassedTests($id): JsonResponse
    {
        return $this->manager->getPassedTests($id);
    }

    /**
     * Get information about the test passed by the manager
     * @param $id
     * @param $test_id
     * @return JsonResponse
     */
    public function getPassedTest($id, $test_id): JsonResponse
    {
        return $this->manager->getPassedTest($id, $test_id);
    }


    /**
     * Saving the test results
     * @param  Request  $request
     * @param           $id
     * @param           $test_id
     * @return JsonResponse
     */
    public function passingTest(Request $request, $id, $test_id): JsonResponse
    {
        return $this->manager->passingTest($request, $id, $test_id);
    }
}
