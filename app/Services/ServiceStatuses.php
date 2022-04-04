<?php


namespace App\Services;

use App\Models\Status;
use Illuminate\Http\JsonResponse;

class ServiceStatuses {

    /**
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        $statuses = Status::get();
        return response()->json([
            'status' => true,
            'data' => $statuses
        ]);
    }

    /**
     * Example $request - ['id' => 'name', 'id' => 'name']
     * @param $request
     * @return JsonResponse
     */
    public function update($request): JsonResponse
    {
        $statuses = json_decode($request->statuses);
        if(!isset($statuses)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [statuses] field is mandatory'
            ]);
        }
        foreach($statuses as $id => $name) {
            Status::where('id', (int) $id)->update(['name' => $name]);
        }
        return response()->json([
            'status' => TRUE,
            'message' => 'The names of the statuses have been successfully updated'
        ]);
    }

}