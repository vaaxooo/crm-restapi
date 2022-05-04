<?php

namespace App\Services;

use App\Models\HistoryTest;
use App\Models\Test;
use App\Models\TestQuestions;
use App\Models\Callsback;
use App\Models\User;
use App\Models\Client;
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
            'data' => User::select(
                'id',
                'login',
                'first_name',
                'last_name',
                'surname',
                'role',
                'email',
                'last_online',
                'created_at'
            )->where(
                'role',
                'manager'
            )->paginate(20),
        ]);
    }

    public function getCurrentClient()
    {
        if (!isset(auth()->user()->current_client)) {
            return response()->json([
                'status' => TRUE,
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => TRUE,
            'data' => Client::where('id', auth()->user()->current_client)
                ->first(),
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
        if ($validator->fails()) {
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
        if ($validator->fails()) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors(),
            ]);
        }
        $params = $validator->validated();
        if (!empty($request->password)) {
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
        if (User::find($id)) {
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
     * @param $request
     * @return JsonResponse
     */
    public function statisticsForToday($request): JsonResponse
    {
        $managers = DB::table('processed_clients')
            ->select(DB::raw("count(*) as count, processed_clients.status, processed_clients.created_at as date, users.id as manager_id, users.first_name, users.last_name, users.surname, users.login, processed_clients.manager_id, clients.id as client_id, processed_clients.client_id, clients.fullname as client"))

            ->join('users', 'users.id', '=', 'processed_clients.manager_id')
            ->join('clients', 'clients.id', '=', 'processed_clients.client_id')

            ->where('processed_clients.processed', 1)
            ->where('processed_clients.created_at', date('Y-m-d'))
            ->whereNotIn('processed_clients.status', ['Не прозвонен'])
            ->groupBy('processed_clients.status')
            ->get();
        $processedManagers = [];
        foreach ($managers as $key => $manager) {
            if (isset($processedManagers[$manager->manager_id]['statistics'][$manager->status])) {
                $processedManagers[$manager->manager_id]['statistics'][$manager->status]
                    = (int)$processedManagers[$manager->manager_id]['statistics'][$manager->status]
                    + 1;
            } else {
                if (!isset($processedManagers[$manager->manager_id])) {
                    $processedManagers[$manager->manager_id] = [
                        'id' => $manager->manager_id,
                        'fullname' => $manager->first_name . " " . $manager->last_name . " " . $manager->surname,
                        'login' => $manager->login
                    ];
                    if (isset($manager->client) && isset($manager->client_id)) {
                        $processedManagers[$manager->manager_id]['client'] = [
                            'id' => $manager->client_id,
                            'fullname' => $manager->client
                        ];
                    }
                }
                $processedManagers[$manager->manager_id]['statistics'][$manager->status] = 1;
            }
        }
        sort($processedManagers);
        return response()->json([
            'status' => TRUE,
            'data' => $processedManagers
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function statistic($request): JsonResponse
    {
        if (isset($request->start_date) && isset($request->end_date)) {
            $start_date = (string) $request->start_date;
            $end_date = (string) $request->end_date;
            $managers = DB::select(
                'WITH m AS (SELECT * FROM users WHERE role = "manager") SELECT m.id, m.login, m.first_name, m.last_name, m.surname, pc.id, pc.manager_id, pc.status, pc.created_at, c.id as client_id, c.fullname as client FROM m, processed_clients as pc, clients as c WHERE DATE(pc.created_at) BETWEEN ? AND ? AND NOT pc.status = ? AND m.id = pc.manager_id AND c.id = m.current_client GROUP BY pc.id',
                [$start_date, $end_date, "Не прозвонен"]
            );
        } else {
            if (Redis::get('statistics')) {
                return response()->json([
                    'status' => TRUE,
                    'data' => json_decode(Redis::get('statistics')),
                ]);
            }
            $managers = DB::select(
                'WITH m AS (SELECT * FROM users WHERE role = "manager") SELECT m.id, m.login, m.first_name, m.last_name, m.surname, pc.id, pc.manager_id, pc.status, pc.created_at, c.id as client_id, c.fullname as client FROM m, processed_clients as pc, clients as c WHERE m.id = pc.manager_id AND c.id = m.current_client AND NOT pc.status = ? GROUP BY pc.id',
                ["Не прозвонен"]
            );
        }
        $processedManagers = [];
        foreach ($managers as $key => $manager) {
            if (isset($processedManagers[$manager->manager_id]['statistics'][$manager->status])) {
                $processedManagers[$manager->manager_id]['statistics'][$manager->status]
                    = (int)$processedManagers[$manager->manager_id]['statistics'][$manager->status]
                    + 1;
            } else {
                if (!isset($processedManagers[$manager->manager_id])) {
                    $processedManagers[$manager->manager_id] = [
                        'id' => $manager->manager_id,
                        'fullname' => $manager->first_name . " " . $manager->last_name . " " . $manager->surname,
                        'login' => $manager->login
                    ];

                    if (isset($manager->client) && isset($manager->client_id)) {
                        $processedManagers[$manager->manager_id]['client'] = [
                            'id' => $manager->client_id,
                            'fullname' => $manager->client
                        ];
                    }
                }
                $processedManagers[$manager->manager_id]['statistics'][$manager->status] = 1;
            }
        }
        sort($processedManagers);
        Redis::set('statistics', json_encode($processedManagers), 'EX', 24200);
        return response()->json([
            'status' => TRUE,
            'data' => $processedManagers,
        ]);
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
            ->select(
                'tests.id',
                'history_tests.test_id',
                'history_tests.manager_id',
                'tests.name'
            )
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
            ->join(
                'test_questions',
                'test_questions.test_id',
                '=',
                'history_tests.test_id'
            )
            ->where('history_tests.test_id', $test_id)->where('manager_id', $id)
            ->first();
        if (!$test) {
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
        if (!isset($request->answers)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [answers] field is required',
            ]);
        }
        if (!Test::where('id', $test_id)->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Test not found',
            ]);
        }
        HistoryTest::create([
            'test_id' => $test_id,
            'manager_id' => $id,
            'answers' => $request->answers,
        ]);

        return response()->json([
            'status' => TRUE,
            'message' => 'The results of the passed test have been successfully saved',
        ]);
    }


    public function callbacks($id)
    {
        $data = DB::table('callsbacks')
            ->select('clients.id as client_id', 'callsbacks.client_id as client_id', 'clients.phone as client_phone', 'callsbacks.date', 'callsbacks.date as callback_id', 'clients.fullname as client', 'clients.information', 'users.id as manager_id', 'callsbacks.manager_id as manager_id', 'users.login as manager')
            ->join('clients', 'clients.id', '=', 'callsbacks.client_id')
            ->join('users', 'users.id', '=', 'callsbacks.manager_id')
            ->where('manager_id', $id)
            ->get();
        return response()->json([
            'status' => TRUE,
            'data' => $data
        ]);
    }
}
