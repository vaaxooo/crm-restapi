<?php

namespace App\Http\Controllers;

use App\Services\ServiceSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private $settings;

    public function __construct() {
        $this->middleware('auth:api');
        $this->middleware('permissions');
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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function setJivoUrl(Request $request): JsonResponse
    {
        return $this->settings->setJivoUrl($request);
    }
}
