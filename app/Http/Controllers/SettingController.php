<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ServiceSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Settings;

class SettingController extends Controller
{
    private $settings;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permissions', ['except' => ['dialogueTemplates', 'showDialogueTemplate', 'createDialogueTemplate', 'updateDialogueTemplate', 'deleteDialogueTemplate']]);
        $this->settings = new ServiceSettings();
    }

    /**
     * Changes the preset text for additional client fields in the settings
     * @param  Request  $request
     * @return JsonResponse
     */
    public function setPreinstallText(Request $request): JsonResponse
    {
        return $this->settings->setPreinstallText($request);
    }

    /**
     * Get all settings website
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getSettings(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => Setting::where('id', 1)->first()
        ]);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function setJivoUrl(Request $request): JsonResponse
    {
        return $this->settings->setJivoUrl($request);
    }

    /**
     * @return JsonResponse
     */
    public function dialogueTemplates(): JsonResponse
    {
        return $this->settings->dialogueTemplates();
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function showDialogueTemplate($id): JsonResponse
    {
        return $this->settings->showDialogueTemplate($id);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function createDialogueTemplate(Request $request): JsonResponse
    {
        return $this->settings->createDialogueTemplate($request);
    }

    /**
     * @param  Request  $request
     * @param           $id
     * @return JsonResponse
     */
    public function updateDialogueTemplate(Request $request, $id): JsonResponse
    {
        return $this->settings->updateDialogueTemplate($request, $id);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteDialogueTemplate($id): JsonResponse
    {
        return $this->settings->deleteDialogueTemplate($id);
    }
}
