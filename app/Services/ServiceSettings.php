<?php

namespace App\Services;

use App\Models\DialogueTemplates;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class ServiceSettings
{

    /**
     * @param $request
     * @return JsonResponse
     */
    public function setPreinstallText($request): JsonResponse
    {
        if (empty($request->preinstall_text)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [preinstall_text] field is mandatory',
            ]);
        }
        Setting::where('id', 1)->update([
            'preinstall_text' => $request->preinstall_text,
        ]);

        return response()->json([
            'status' => TRUE,
            'message' => 'The information has been successfully updated',
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function setJivoUrl($request): JsonResponse
    {
        if (empty($request->jivo)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [jivo] field is mandatory',
            ]);
        }
        Setting::where('id', 1)->update([
            'jivo' => $request->jivo,
        ]);

        return response()->json([
            'status' => TRUE,
            'message' => 'The information has been successfully updated',
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function dialogueTemplates(): JsonResponse
    {
        $dialogueTemplates = DialogueTemplates::paginate(15);

        return response()->json([
            'status' => TRUE,
            'data' => $dialogueTemplates,
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function showDialogueTemplate($id): JsonResponse
    {
        $dialogueTemplate = DialogueTemplates::where('id', $id);
        if (!$dialogueTemplate->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Dialogue template not found',
            ]);
        }

        return response()->json([
            'status' => TRUE,
            'data' => $dialogueTemplate->first(),
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function createDialogueTemplate($request): JsonResponse
    {
        if (!isset($request->name)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [name] field is required',
            ]);
        }
        DialogueTemplates::create([
            'name' => $request->name,
            'text' => $request->text,
        ]);

        return response()->json([
            'status' => TRUE,
            'message' => 'Dialogue template have been successfully created',
        ]);
    }

    /**
     * @param $request
     * @param $id
     * @return JsonResponse
     */
    public function updateDialogueTemplate($request, $id): JsonResponse
    {
        if (!isset($request->name)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [name] field is required',
            ]);
        }
        DialogueTemplates::where('id', $id)->update([
            'name' => $request->name,
            'text' => $request->text,
        ]);

        return response()->json([
            'status' => TRUE,
            'message' => 'Dialogue template have been successfully refreshed',
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteDialogueTemplate($id): JsonResponse
    {
        $dialogueTemplate = DialogueTemplates::where('id', $id);
        if ($dialogueTemplate->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Dialogue template not found',
            ]);
        }
        $dialogueTemplate->delete();

        return response()->json([
            'status' => TRUE,
            'message' => 'Dialogue template have been successfully deleted',
        ]);
    }
}
