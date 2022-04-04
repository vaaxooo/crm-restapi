<?php

namespace App\Services;


use App\Models\Client;
use App\Models\File;
use App\Models\ProcessedClient;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Imports\ClientsImport;
use Illuminate\Support\Facades\Storage;
use App\Services\ServiceClient;

class ServiceFiles
{

    /**
     * @param $request
     * @return JsonResponse
     */
    public function all($request): JsonResponse
    {
        $table = DB::table('files');
        if(isset($request->databases) && !empty($request->databases)) {
            $table->whereIn('id', json_decode($request->databases));
        }
        if(isset($request->managers) && !empty($request->managers)) {
        }
        if(isset($request->statuses) && !empty($request->statuses)) {
        }
        if(isset($request->zones) && !empty($request->zones)) {
        }
        $table_clone = clone $table;
        $freeClients = clone $table;
        $statistics
            = $table_clone->select(DB::raw('count(*) as count, files.id, clients.database, clients.status, statuses.name'))
            ->join('clients', 'clients.database', '=', 'files.id')
            ->join('statuses', 'statuses.name', '=', 'clients.status')
            ->groupBy('clients.status')->get();
        $databases = $table->paginate(15);

        $freeClients
            = $freeClients->select(DB::raw('count(*) as count, clients.database, files.id, clients.processed'))
            ->join('clients', 'clients.database', '=', 'files.id')
            ->where('clients.processed', 0)->groupBy('clients.database')->get();

        foreach($freeClients as $key => $freeClient) {
            $clientData = (array)$freeClients[$key];
            unset($clientData['processed']);
            unset($clientData['id']);
            $freeClients[$key] = array_merge($clientData,
                ['status' => 'Осталось']);
            $statistics[] = $freeClients[$key];
        }

        return response()->json([
            'status' => TRUE,
            'data' => [
                'databases' => $databases,
                'statistics' => $statistics,
            ],
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $database = File::where('id', $id)->first();
            $database['clients'] = Client::where('database', $id)->paginate(20);

            return response()->json([
                'status' => TRUE,
                'data' => [
                    'database' => $database,
                    'statistic' => (new ServiceClient())->statistic($id),
                ],
            ]);
        } catch(Exception $exception) {
            return response()->json([
                'status' => FALSE,
                'message' => 'An error occurred while executing the request',
            ]);
        }
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function upload($request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|min:4',
            ]);
            if($validator->fails()) {
                return response()->json([
                    'status' => FALSE,
                    'errors' => $validator->errors(),
                ]);
            }
            $file = $request->file('file');
            if(isset($file)) {
                if(!in_array($file->getClientOriginalExtension(),
                    ['xls', 'xlsx', 'txt'])
                ) {
                    return response()->json([
                        'status' => FALSE,
                        'message' => 'Invalid file format',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => FALSE,
                    'message' => 'The file has not been uploaded',
                ]);
            }
            $filename = md5(time().'_'.pathinfo($file->getClientOriginalName(),
                        PATHINFO_FILENAME)).'.'
                .$file->getClientOriginalExtension();
            Storage::disk('local')->putFileAs('databases', $file, $filename);
            $filePath = 'databases/'.$filename;
            $database = File::create([
                'name' => $request->name,
                'path' => $filePath,
            ]);
            $this->importClients((new ClientsImport)->toArray($file->getRealPath()),
                $database->id);

            return response()->json([
                'status' => TRUE,
                'message' => 'File successfully uploaded',
            ]);
        } catch(Exception $exception) {
            return response()->json([
                'status' => FALSE,
                'message' => 'There was an error when I downloaded the file!',
            ]);
        }
    }

    /**
     * Import clients in DataBase
     * @param  array  $clients
     * @param         $database
     * @return JsonResponse
     */
    private function importClients(array $clients, $database)
    {
        try {
            unset($clients[0][0]); //Remove headers (bio, phone)
            foreach($clients as $circle => $circle_clients) {
                if(count($circle_clients) < 2) {
                    break;
                }
                $processedClients = [];
                foreach($circle_clients as $client) {
                    $bio = explode(' ', $client[0]);
                    $processedClients[] = [
                        'first_name' => $bio[1],
                        'last_name' => $bio[0],
                        'surname' => $bio[2],
                        'phone' => str_replace(" ", "", $client[1]),
                        'database' => $database,
                    ];
                }
                Client::insert($processedClients);
            }
            $freeManagers = User::where('current_client', '=', NULL)->get();
            foreach($freeManagers as $manager) {
                $freeClient = Client::where('processed', 0)
                    ->where('database', $database)->inRandomOrder();
                $clientData = $freeClient->first();
                if(!$clientData) {
                    break;
                }
                User::where('id', $manager->id)
                    ->update(['current_client' => $clientData->id]);
                Client::where('id', $clientData->id)
                    ->update(['processed' => 1]);
                ProcessedClient::create([
                    'client_id' => $clientData->id,
                    'manager_id' => $manager->id,
                ]);
            }

            return FALSE;
        } catch(Exception $exception) {
            return response()->json([
                'status' => FALSE,
                'message' => 'An error occurred when importing clients',
            ]);
        }
    }


    /**
     * @param $request
     * @param $id
     * @return JsonResponse
     */
    public function rename($request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:4',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors(),
            ]);
        }
        $file = File::where('id', $id);
        if(!$file->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Database does not exists',
            ]);
        }
        $file->update(['name' => $request->name]);

        return response()->json([
            'status' => TRUE,
            'message' => 'Database name successfully changed',
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        $file = File::where('id', $id);
        if(!$file->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Database does not exist',
            ]);
        }
        $file->delete();

        return response()->json([
            'status' => TRUE,
            'message' => 'Database successfully deleted',
        ]);
    }

}