<?php

namespace App\Services;

use App\Models\HistoryTest;
use App\Models\Test;
use App\Models\TestQuestions;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Redis;

class ServiceManager
{

    /**
     * @param $request
     * @return JsonResponse
     */
    public function all($request): JsonResponse
    {
        return response()->json([
            'status' => TRUE,
            'data' => User::select('id', 'login', 'first_name', 'last_name',
                'surname', 'role', 'email', 'created_at')->where('role',
                'manager')->paginate(20),
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function create($request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'surname' => 'required',
            'login' => 'required|unique:users',
            'email' => 'required|unique:users',
            'password' => 'required|min:8',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors(),
            ]);
        }

        $params = $validator->validated();
        $params['password'] = Hash::make($params['password']);
        User::create(array_merge($params, ['role' => 'manager']));

        return response()->json([
            'status' => TRUE,
            'message' => 'Manager successfully added in database!',
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update($request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'surname' => 'required',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors(),
            ]);
        }
        $params = $validator->validated();
        if(!empty($request->password)) {
            $params['password'] = Hash::make($request->password);
        }
        User::find($id)
            ->update($params);

        return response()->json([
            'status' => TRUE,
            'message' => 'Manager successfully updated!',
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        if(User::find($id)) {
            User::find($id)->delete();

            return response()->json([
                'status' => TRUE,
                'message' => 'Manager successfully deleted from database!',
            ]);
        }

        return response()->json([
            'status' => FALSE,
            'message' => 'Manager does not exist',
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function statistic(): JsonResponse
    {
        try {
            if(Redis::get('statistics')) {
                return response()->json([
                    'status' => TRUE,
                    'data' => json_decode(Redis::get('statistics')),
                ]);
            }
            $managers = DB::select(DB::raw('WITH m AS (SELECT * FROM users WHERE role = "manager")
SELECT m.id, pc.id, pc.manager_id, pc.status FROM m, processed_clients as pc GROUP BY pc.id'));
            $processedManagers = [];
            foreach($managers as $manager) {
                if(isset($processedManagers[$manager->manager_id][$manager->status])) {
                    $processedManagers[$manager->manager_id][$manager->status]
                        = (int)$processedManagers[$manager->manager_id][$manager->status] + 1;
                } else {
                    $processedManagers[$manager->manager_id][$manager->status] = 1;
                }
            }
            $managers = [];
            foreach($processedManagers as $key => $statistic) {
                $managers[] = [
                    'manager_id' => $key,
                    'processed_clients' => $statistic,
                ];
            }
            Redis::set('statistics', json_encode($managers), 'EX', 86400);

            return response()->json([
                'status' => TRUE,
                'data' => $managers,
            ]);
        } catch(\Exception $exception) {
            return response()->json([
                'status' => FALSE,
                'message' => "An error occurred while processing the statistics",
            ]);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function refreshOnline($id): JsonResponse
    {
        User::where('id', $id)->update(['last_online' => date('d.m.Y H:i:s')]);

        return response()->json([
            'status' => TRUE,
            'message' => 'Data successfully refreshed',
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function getPassedTests($id): JsonResponse
    {
        $tests = DB::table('history_tests')
            ->select('tests.id', 'history_tests.test_id',
                'history_tests.manager_id', 'tests.name')
            ->join('tests', 'tests.id', '=', 'history_tests.test_id')
            ->where('history_tests.manager_id', $id)->get();

        return response()->json([
            'status' => TRUE,
            'data' => $tests,
        ]);
    }

    /**
     * @param $id
     * @param $test_id
     * @return JsonResponse
     */
    public function getPassedTest($id, $test_id): JsonResponse
    {
        $test = DB::table('history_tests')
            ->join('tests', 'tests.id', '=', 'history_tests.test_id')
            ->join('test_questions', 'test_questions.test_id', '=',
                'history_tests.test_id')
            ->where('history_tests.test_id', $test_id)->where('manager_id', $id)
            ->first();
        if(!$test) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Test not found',
            ]);
        }

        return response()->json([
            'status' => TRUE,
            'data' => $test,
        ]);
    }

    /**
     * @param $request
     * @param $id
     * @param $test_id
     * @return JsonResponse
     */
    public function passingTest($request, $id, $test_id): JsonResponse
    {
        if(!isset($request->validator)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [answer] field is required',
            ]);
        }
        if(!Test::where('id', $test_id)->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Test not found',
            ]);
        }
        HistoryTest::create([
            'test_id' => $test_id,
            'manager_id' => $id,
            'answer' => $request->answer,
        ]);

        return response()->json([
            'status' => TRUE,
            'message' => 'The results of the passed test have been successfully saved',
        ]);
    }
}
