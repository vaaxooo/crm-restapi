<?php

namespace App\Services;

use App\Models\Callsback;
use App\Models\Client;
use App\Models\File;
use App\Models\ProcessedClient;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ServiceClient
{

    /**
     * @return JsonResponse
     */
    public function duplicates(): JsonResponse
    {
        $clients = Client::select(
            'clients.status',
            'clients.first_name',
            'clients.last_name',
            'clients.surname',
            'clients.phone',
            'clients.database',
            'files.id',
            'files.name'
        )
            ->join('files', 'files.id', '=', 'clients.database')
            ->groupBy('clients.phone')->havingRaw('count(clients.phone) > 1')
            ->paginate(20);

        return response()->json([
            'status' => TRUE,
            'data' => $clients,
        ]);
    }

    public function deleteDuplicates()
    {
        Client::where('status', 'Недозвон')->orWhere('status', 'Удалить')->delete();
        return response()->json(['status' => TRUE, 'message' => 'All duplicates were successfully deleted']);
    }

    /**
     * @return JsonResponse
     */
    public function deleteAllDuplicates(): JsonResponse
    {
        $data = Client::select('id')->groupBy('phone')
            ->havingRaw('count(phone) > 1')->get();
        if (!$data) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The list of duplicates is empty.',
            ]);
        }
        $clients = [];
        foreach ($data as $key => $value) {
            $clients[] = $value['id'];
        }
        Client::destroy($clients);

        return response()->json([
            'status' => TRUE,
            'message' => 'All duplicates have been deleted',
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function search($request): JsonResponse
    {
        $table = DB::table('clients')
            ->select(DB::raw('clients.id, clients.fullname, clients.city, clients.region, clients.address, clients.phone, clients.additional_field1, clients.status, clients.database as database_id, files.id as database_id, files.name as database_name'))
            ->join('files', 'files.id', '=', 'clients.database');
        if (empty($request->client)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Customer information cannot be empty',
            ]);
        }
        if (!empty($request->database)) {
            $table->where('files.name', $request->database);
        }
        $table->where('clients.fullname', "LIKE", "%" . $request->client . "%")
            ->orWhere('clients.phone', "LIKE", "%" . $request->client . "%");

        return response()->json([
            'status' => TRUE,
            'data' => $table->paginate(20),
        ]);
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        $client = Client::where('id', $id);
        if (!$client->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Client not found',
            ]);
        }
        $client->delete();

        return response()->json([
            'status' => TRUE,
            'message' => 'Client have been successfully deleted',
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $client = Client::find($id);
        if (!$client) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Client not found',
            ]);
        }

        return response()->json([
            'status' => TRUE,
            'data' => $client,
        ]);
    }


    /**
     * @param $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update($request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'surname' => 'required',
            'phone' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors(),
            ]);
        }
        $params = array_merge($validator->validated(), [
            'region' => $request->region,
            'city' => $request->city,
            'address' => $request->address,
            'timezone' => $request->timezone,
            'age' => $request->age,
            'additional_field1' => $request->additional_field1,
            'additional_field2' => $request->additional_field2,
            'additional_field3' => $request->additional_field3,
            'information' => $request->information,
            'fullname' => $request->first_name . " " . $request->last_name . " " . $request->surname
        ]);
        Client::where('id', $id)->update($params);

        return response()->json([
            'status' => TRUE,
            'message' => 'The information has been successfully updated',
        ]);
    }

    /**
     * @param $request
     * @param $id
     * @return JsonResponse
     */
    public function setStatus($request, $id): JsonResponse
    {
        if (!isset($request->status)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [status] field is mandatory',
            ]);
        }
        $client = Client::where('id', $id);
        if (!$client->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Client not found',
            ]);
        }
        $status_name = (Status::select('id', 'name')->where('id', $request->status)->first())['name'];
        $client->update(['status' => $status_name]);
        $processedClient = ProcessedClient::where('client_id', $id);
        if ($processedClient->exists()) {
            $processedClient->update([
                'status' => $status_name,
                'processed' => 1,
            ]);
            $freeClient = Client::select('id')->where('processed', 0)
                ->inRandomOrder()->first();
            $current_client = NULL;
            if ($freeClient) {
                $current_client = $freeClient->id;
            }
            $manager = $processedClient->first();
            User::where('id', $manager->manager_id)
                ->update(['current_client' => $current_client]);
            Client::where('id', $freeClient->id)->update(['processed' => 1]);
            ProcessedClient::create([
                'client_id' => $current_client,
                'manager_id' => $manager->manager_id,
            ]);
        }


        return response()->json([
            'status' => TRUE,
            'message' => 'Client status successfully updated',
        ]);
    }

    /**
     * @param $request
     * @param $id
     * @return JsonResponse
     */
    public function transfer($request, $id): JsonResponse
    {
        try {
            if (!isset($request->manager_id)) {
                return response()->json([
                    'status' => FALSE,
                    'message' => 'The [manager_id] field is mandatory',
                ]);
            }
            $current_manager = User::where('current_client', $id);
            if ($current_manager->exists()) {
                $freeClient = Client::select('id')->where('processed', 0)
                    ->inRandomOrder()->first();
                $current_client = NULL;
                if ($freeClient) {
                    $current_client = $freeClient->id;
                }
                $current_manager->update(['current_client' => $current_client]);
                Client::where('id', $freeClient->id)
                    ->update(['processed' => 1]);
                ProcessedClient::create([
                    'client_id' => $current_client,
                    'manager_id' => $current_manager->first()['id'],
                ]);
            }
            User::where('id', $request->manager_id)
                ->update(['current_client' => $id]);

            return response()->json([
                'status' => TRUE,
                'message' => 'Client successfully transferred to another manager',
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => FALSE,
                'message' => 'An error occurred while transferring a client to another manager',
            ]);
        }
    }

    /**
     * @return JsonResponse
     */
    public function activeClients(): JsonResponse
    {
        $activeClients = DB::table('processed_clients')
            ->select(
                'clients.id as client_id',
                'users.id as manager_id',
                'processed_clients.processed',
                'processed_clients.client_id',
                'processed_clients.manager_id',
                'clients.first_name as client_first_name',
                'clients.last_name as client_last_name',
                'clients.surname as client_surname',
                'clients.phone as client_phone',
                'users.login as manager_login',
                'files.id as database_id',
                'files.name as database'
            )
            ->join('clients', 'clients.id', '=', 'processed_clients.client_id')
            ->join(
                'users',
                'users.id',
                '=',
                'processed_clients.manager_id'
            )
            ->join('files', 'files.id', '=', 'clients.database')
            ->where('processed_clients.processed', 0)->get();


        $clientsList = [];
        foreach ($activeClients as $client) {
            $clientsList[] = [
                'client' => [
                    'id' => (int) $client->client_id,
                    'first_name' => $client->client_first_name,
                    'last_name' => $client->client_last_name,
                    'surname' => $client->client_surname,
                    'phone' => $client->client_phone
                ],
                'manager' => $client->manager_login,
                'manager_id' => (int) $client->manager_id,
                'database' => $client->database,
                'database_id' => (int) $client->database_id
            ];
        }

        return response()->json([
            'status' => TRUE,
            'data' => $clientsList,
        ]);
    }


    public function getClientsByStatuses($request, $id)
    {

        if (!isset($request->statuses)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [statuses] field required'
            ]);
        }
        $clients = Client::whereIn('status', json_decode($request->statuses))->where('database', $id)->paginate(20);
        return response()->json([
            'status' => TRUE,
            'data' => $clients
        ]);
    }



    public function createCallback($request, $id)
    {
        if (!isset($request->date)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [date] field required'
            ]);
        }
        $manager_id = auth()->user()->id;
        Callsback::create([
            'client_id' => $id,
            'manager_id' => $manager_id,
            'date' => $request->date
        ]);
        return response()->json([
            'status' => TRUE,
            'message' => 'Call back successfully created'
        ]);
    }

    public function updateCallback($request, $id)
    {
        if (!isset($request->date)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [date] field required'
            ]);
        }
        Callsback::where('id', $id)->update([
            'date' => $request->date
        ]);
        return response()->json([
            'status' => TRUE,
            'message' => 'Call back successfully updated'
        ]);
    }


    public function deleteCallback($request)
    {
        if (!isset($request->date)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The [date] field required'
            ]);
        }
        Callsback::whereDate('date', '<=', $request->date)->delete();
        return response()->json([
            'status' => TRUE,
            'message' => 'Calls back successfully deleted'
        ]);
    }

    public function callbacks()
    {
        $data = DB::table('callsbacks')
            ->select('clients.id as client_id', 'clients.phone as client_phone', 'callsbacks.client_id as client_id', 'callsbacks.date', 'callsbacks.date as callback_id', 'clients.fullname as client', 'clients.information', 'users.id as manager_id', 'callsbacks.manager_id as manager_id', 'users.login as manager')
            ->join('clients', 'clients.id', '=', 'callsbacks.client_id')
            ->join('users', 'users.id', '=', 'callsbacks.manager_id')
            ->get();
        return response()->json([
            'status' => TRUE,
            'data' => $data
        ]);
    }

    public function callbackById($id)
    {
        $data = DB::table('callsbacks')
            ->select('clients.id as client_id', 'clients.phone as client_phone', 'callsbacks.client_id as client_id', 'callsbacks.date', 'callsbacks.date as callback_id', 'clients.fullname as client', 'clients.information', 'users.id as manager_id', 'callsbacks.manager_id as manager_id', 'users.login as manager')
            ->join('clients', 'clients.id', '=', 'callsbacks.client_id')
            ->join('users', 'users.id', '=', 'callsbacks.manager_id')
            ->where('client_id', $id)->get();
        return response()->json([
            'status' => TRUE,
            'data' => $data
        ]);
    }
}
