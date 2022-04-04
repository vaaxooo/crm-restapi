<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class ServiceSettings {

    /**
     * @param $request
     * @return JsonResponse
     */
    public function setPreinstallText($request): JsonResponse
    {
        if(empty($request->preinstall_text)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [preinstall_text] field is mandatory'
            ]);
        }
        Setting::where('id', 1)->update([
            'preinstall_text' => $request->preinstall_text
        ]);
        return response()->json([
            'status' => TRUE,
            'message' => 'The information has been successfully updated'
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function setJivoUrl($request): JsonResponse
    {
        if(empty($request->jivo)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [jivo] field is mandatory'
            ]);
        }
        Setting::where('id', 1)->update([
            'jivo' => $request->jivo
        ]);
        return response()->json([
            'status' => TRUE,
            'message' => 'The information has been successfully updated'
        ]);
    }

}