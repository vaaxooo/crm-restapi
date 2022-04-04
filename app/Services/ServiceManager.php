<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
        $managers = DB::table('users')
            ->select(DB::raw('count(*) as count, users.role, users.id, processed_clients.manager_id, processed_clients.status'))
            ->join('processed_clients', 'processed_clients.manager_id', '=',
                'users.id')->where('users.role', 'manager')
            ->groupByRaw('processed_clients.manager_id, processed_clients.status')
            ->get();
        return response()->json([
            'status' => TRUE,
            'data' => $managers
        ]);
    }
}
