<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ServiceAccount {

    /**
     * @param $request
     * @return JsonResponse
     */
    public function changePassword($request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8'
        ]);
        if($validator->fails()) {
            return response()->json([
                "status" => false,
                "errors" => $validator->errors()
            ]);
        }
        if(!Hash::check($request->current_password, auth()->user()->password)) {
            return response()->json([
                "status" => false,
                "message" => "Invalid current password!"
            ]);
        }
        if($request->current_password == $request->new_password) {
            return response()->json([
                "status" => false,
                "message" => "The new password cannot match the old one!"
            ]);
        }
        User::find(auth()->user()->id)->update(['password' => Hash::make($request->new_password)]);
        return response()->json([
            "status" => true,
            "message" => "Password successfully changed"
        ]);
    }

}
