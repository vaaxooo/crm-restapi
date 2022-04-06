<?php

namespace App\Services;

use App\Models\HistoryTest;
use App\Models\Test;
use App\Models\TestQuestions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceTest
{

    /**
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        return response()->json([
            'status' => TRUE,
            'data' => Test::paginate(20),
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $test = DB::table('tests')
            ->join('test_questions', 'test_questions.test_id', '=', 'tests.id')
            ->where('tests.id', $id)->first();
        if(!$test->exists()) {
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
     * @return JsonResponse
     */
    public function create($request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'questions' => 'required',
            ]);
            if($validator->fails()) {
                return response()->json([
                    'status' => FALSE,
                    'errors' => $validator->errors(),
                ]);
            }
            $test = Test::create(['name' => $request->name]);
            $params = [];
            foreach(json_decode($request->questions) as $data) {
                $params[] = [
                    'test_id' => $test->id,
                    'question' => $data->question,
                    'answers' => json_encode($data->answers),
                    'right_answers' => json_encode($data->answers),
                ];
            }
            TestQuestions::insert($params);

            return response()->json([
                'status' => TRUE,
                'message' => 'The test was successfully created',
            ]);
        } catch(\Exception $exception) {
            return response()->json([
                'status' => FALSE,
                'message' => 'An error occurred while creating the test',
            ]);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        $test = Test::where('id', $id);
        if(!$test->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Test not found',
            ]);
        }
        $test->delete();
        HistoryTest::where('test_id', $id)->delete();
        TestQuestions::where('test_id', $id)->delete();

        return response()->json([
            'status' => TRUE,
            'message' => "Test have been successfully deleted",
        ]);
    }
}