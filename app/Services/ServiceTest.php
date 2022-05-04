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
        $data = Test::paginate(20);

        foreach ($data as $row) {
            $row->passed_the_test = false;
            if (HistoryTest::where('test_id', $row->id)->where('manager_id', auth()->user()->id)->exists()) {
                $row->passed_the_test = true;
            }
        }

        return response()->json([
            'status' => TRUE,
            'data' => $data,
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $test = DB::table('tests')
            ->where('tests.id', $id);
        if (!$test->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Test not found',
            ]);
        }
        $test = $test->first();
        $test->questions = DB::table('test_questions')->select('id',  'question', 'answers', 'wide_answer')->where('test_id', $id)->get();

        foreach ($test->questions as $question) {
            if ($question->wide_answer) {
                unset($question->answers);
            }
            unset($question->right_answers);
        }

        return response()->json([
            'status' => TRUE,
            'data' => $test,
        ]);
    }

    /** */
    public function answers($id)
    {
        $data = DB::table('history_tests')
            ->select('users.login as manager_login', 'history_tests.manager_id', 'history_tests.answers as manager_answers')
            ->join('users', 'users.id', '=', 'history_tests.manager_id')
            ->where('history_tests.test_id', $id)
            ->get();


        foreach ($data as $test) {
            $test->manager_answers = json_decode($test->manager_answers);
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }


    /**
     * I get all the answers from the history_tests table, then I get the right answers from the
     * test_questions table, then I compare them and get the number of correct answers.
     * 
     * @param id test id
     */
    public function statistics($id)
    {
        $history_tests = DB::table('history_tests')
            ->select('users.login', 'history_tests.answers', 'test_questions.right_answers')
            ->join('users', 'users.id', '=', 'history_tests.manager_id')
            ->join('test_questions', 'test_questions.test_id', '=', 'history_tests.test_id')
            ->where('history_tests.test_id', $id)
            ->groupBy('users.id')
            ->get();

        $total = DB::table('test_questions')->where('test_id', $id)->count();
        $survey_count = DB::table('test_questions')->where('test_id', $id)->where('wide_answer', false)->count();
        $wide_answers_count = DB::table('test_questions')->where('test_id', $id)->where('wide_answer', true)->count();


        $data = [];
        foreach ($history_tests as $user) {
            $answers = json_decode($user->answers, true);
            $right_answers = json_decode($user->right_answers, true);

            $total_right_answers = count(array_intersect($answers, $right_answers));

            $data[] = [
                'login' => $user->login,
                'statistics' => [
                    'total' => $total,
                    'survey_count' => $survey_count,
                    'wide_answers_count' => $wide_answers_count,
                    'total_right_answers' => $total_right_answers
                ]
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }


    /**
     * 
     * @param request the request object
     * @param id test id
     * 
     * @return The test with the questions.
     */
    public function update($request, $id)
    {
        if ($request->isMethod('get')) {
            $test = DB::table('tests')->select('id', 'name')
                ->where('tests.id', $id);
            if (!$test->exists()) {
                return response()->json([
                    'status' => FALSE,
                    'message' => 'Test not found',
                ]);
            }
            $test = $test->first();
            $test->questions = DB::table('test_questions')->select('question', 'answers', 'right_answers', 'wide_answer')->where('test_id', $id)->get();

            return response()->json([
                'status' => TRUE,
                'data' => $test,
            ]);
        }

        if ($request->isMethod('patch')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'questions' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => FALSE,
                    'errors' => $validator->errors(),
                ]);
            }
            Test::where('id', $id)->update(['name' => $request->name]);

            $params = [];
            foreach (json_decode($request->questions) as $data) {
                $params[] = [
                    'test_id' => $id,
                    'question' => $data->question,
                    'answers' => $data->answers,
                    'right_answers' => $data->right_answers,
                    'wide_answer' => $data->wide_answer
                ];
            }
            DB::table('test_questions')->where('test_id', $id)->delete();
            DB::table('test_questions')->insert($params);

            return response()->json([
                'status' => TRUE,
                'message' => 'The test was successfully updated',
            ]);
        }
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function create($request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'questions' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors(),
            ]);
        }
        $test = Test::create(['name' => $request->name]);
        $params = [];


        // return response()->json(json_decode($request->questions, true)[0]);
        foreach (json_decode($request->questions) as $data) {

            $params[] = [
                'test_id' => $test->id,
                'question' => $data->question,
                'answers' => json_encode($data->answers),
                'right_answers' => json_encode($data->answers),
                'wide_answer' => $data->wide_answer
            ];
        }
        TestQuestions::insert($params);

        return response()->json([
            'status' => TRUE,
            'message' => 'The test was successfully created',
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        $test = Test::where('id', $id);
        if (!$test->exists()) {
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
