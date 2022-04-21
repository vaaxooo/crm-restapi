<?php

namespace App\Http\Controllers;

use App\Services\ServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    public $client;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->client = new ServiceClient();
    }

    /**
     * Show all duplicates with clients
     * @param  Request  $request
     * @return JsonResponse
     */
    public function duplicates(Request $request): JsonResponse
    {
        return $this->client
            ->duplicates();
    }

    /**
     * Delete duplicates clients with status Delete and failure to call
     * @return JsonResponse
     */
    public function deleteDuplicates(): JsonResponse
    {
        return $this->client->deleteDuplicates();
    }

    /**
     * Delete all duplicate clients from database
     * @return JsonResponse
     */
    public function deleteAllDuplicates(): JsonResponse
    {
        return $this->client->deleteAllDuplicates();
    }

    /**
     * Search customers by name or phone number
     * @param  Request  $request
     * @return JsonResponse
     */
    public function searchClient(Request $request): JsonResponse
    {
        return $this->client->search($request);
    }

    /**
     * Delete client from database
     * @param $id
     * @return JsonResponse
     */
    public function deleteClient($id): JsonResponse
    {
        return $this->client->delete($id);
    }


    /**
     * Return of all customer information
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        return $this->client->show($id);
    }

    /**
     * Updating client information
     * @param  Request  $request
     * @param           $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateClient(Request $request, $id): JsonResponse
    {
        return $this->client->update($request, $id);
    }

    /**
     * Updating the client status
     * Updating the customer action history table
     * @param  Request  $request
     * @param           $id
     * @return JsonResponse
     */
    public function setStatus(Request $request, $id): JsonResponse
    {
        return $this->client->setStatus($request, $id);
    }

    /**
     * Transferring a client to another manager
     * @param  Request  $request
     * @param           $id
     * @return JsonResponse
     */
    public function transferClient(Request $request, $id): JsonResponse
    {
        return $this->client->transfer($request, $id);
    }

    /**
     * Return list with active clients
     * @return JsonResponse
     */
    public function activeClients(): JsonResponse
    {
        return $this->client->activeClients();
    }


    public function getClientsByStatuses(Request $request, $id)
    {
        return $this->client->getClientsByStatuses($request, $id);
    }
}
