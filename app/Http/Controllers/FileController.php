<?php

namespace App\Http\Controllers;

use App\Services\ServiceFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public $file;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permissions');
        $this->file = new ServiceFiles();
    }

    /**
     * Return all databases with client list
     * @return JsonResponse
     */
    public function all(Request $request): JsonResponse
    {
        return $this->file->all($request);
    }

    /**
     * Return information about a specific database
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        return $this->file->show($id);
    }

    /**
     * Importing a database with customers
     * @param  Request  $request
     * @return JsonResponse
     */
    public function uploadFile(Request $request): JsonResponse
    {
        return $this->file->upload($request);
    }

    /**
     * Changing the name of the database
     * @param  Request  $request
     * @param           $id
     * @return JsonResponse
     */
    public function renameDatabase(Request $request, $id): JsonResponse
    {
        return $this->file->rename($request, $id);
    }

    /**
     * Deleting the customer database
     * @param $id
     * @return JsonResponse
     */
    public function deleteDatabase($id): JsonResponse
    {
        return $this->file->delete($id);
    }

    public function action(Request $request, $id)
    {
        return $this->file->action($request, $id);
    }
}
