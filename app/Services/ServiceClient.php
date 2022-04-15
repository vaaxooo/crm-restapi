<?php

namespace App\Services;

use App\Models\Client;
use App\Models\File;
use App\Models\ProcessedClient;
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
        $data = Client::where('status', 'Недозвон')->orWhere('Удалить')->delete();
        return response()->json($data);
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
        $table = DB::table('clients');
        if (empty($request->client)) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Customer information cannot be empty',
            ]);
        }
        if (str_word_count($request->client) > 2) {
            $bio = explode(" ", $request->client);
            $table->where('first_name', "LIKE", "%" . $bio[0] . "%")
                ->where('last_name', "LIKE", "%" . $bio[1] . "%")
                ->where('surname', "LIKE", "%" . $bio[2] . "%");
        } else {
            $table->where('phone', "LIKE", "%" . $request->client . "%");
        }
        if (!empty($request->database)) {
            $database = File::where('name', 'LIKE', '%' . $request->database . '%')
                ->first('id');
            $table->where('database', $database['id']);
        }

        return response()->json([
            'status' => TRUE,
            'data' => $table->first(),
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
            'data' => $client->first(),
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
        $client->update(['status' => $request->status]);
        $processedClient = ProcessedClient::where('client_id', $id);
        $processedClient->update([
            'status' => $request->status,
            'processed' => 1,
        ]);
        $freeClient = Client::select('id')->where('processed', 0)
            ->inRandomOrder()->first();
        $current_client = NULL;
        if ($freeClient) {
            $current_client = $freeClient->id;
        }
        $manager_id = $processedClient->first()['manager_id'];
        User::where('id', $manager_id)
            ->update(['current_client' => $current_client]);
        Client::where('id', $freeClient->id)->update(['processed' => 1]);
        ProcessedClient::create([
            'client_id' => $current_client,
            'manager_id' => $manager_id,
        ]);

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
                'clients.id',
                'users.id',
                'processed_clients.processed',
                'processed_clients.client_id',
                'processed_clients.manager_id',
                'clients.first_name as client_first_name',
                'clients.last_name as client_last_name',
                'clients.surname as client_surname',
                'clients.phone as client_phone',
                'users.login as manager_login',
                'files.id',
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
                    'first_name' => $client->client_first_name,
                    'last_name' => $client->client_last_name,
                    'surname' => $client->client_surname,
                    'phone' => $client->client_phone
                ],
                'manager' => $client->manager_login,
                'database' => $client->database
            ];
        }

        return response()->json([
            'status' => TRUE,
            'data' => $clientsList,
        ]);
    }
}
