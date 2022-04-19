<?php

namespace App\Services;


use App\Models\Client;
use App\Models\File;
use App\Models\ProcessedClient;
use App\Models\Setting;
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
        if (isset($request->databases) && !empty($request->databases)) {
            $table->whereIn('id', json_decode($request->databases));
        }
        if (isset($request->managers) && !empty($request->managers)) {
        }
        if (isset($request->statuses) && !empty($request->statuses)) {
        }
        if (isset($request->zones) && !empty($request->zones)) {
        }
        $table_clone = clone $table;
        $freeClients = clone $table;
        $databaseClients = clone $table;
        $statistics
            = $table_clone->select(DB::raw('count(*) as count, files.id, clients.database, clients.status, statuses.name, statuses.id as status_id'))
            ->join('clients', 'clients.database', '=', 'files.id')
            ->join('statuses', 'statuses.name', '=', 'clients.status')
            ->groupBy(['clients.database', 'clients.status'])->get();

        foreach ($statistics as $key => $stats) {
            unset($stats->id);
            unset($stats->name);
        }

        $databases = $table->paginate(15);

        $freeClients
            = $freeClients->select(DB::raw('count(*) as count, clients.database, files.id, clients.processed, clients.status, statuses.name, statuses.id as status_id'))
            ->join('clients', 'clients.database', '=', 'files.id')
            ->join('statuses', 'statuses.name', '=', 'clients.status')
            ->where('clients.processed', 0)->groupBy('clients.database')->get();
        foreach ($freeClients as $key => $freeClient) {
            $clientData = (array)$freeClients[$key];
            unset($clientData['name']);
            unset($clientData['processed']);
            unset($clientData['id']);
            $freeClients[$key] = array_merge(
                $clientData,
                ['status' => 'Осталось']
            );
            $statistics[] = $freeClients[$key];
        }

        $databaseClients =
            $databaseClients->select(DB::raw('count(*) as count, clients.database, files.id, clients.processed'))
            ->join('clients', 'clients.database', '=', 'files.id')->groupBy('files.id')->get();

        foreach ($databaseClients as $dclient) {
            unset($dclient->id);
            unset($dclient->processed);

            foreach ($databases as $database) {
                if ($database->id == $dclient->database) {
                    $database->total_clients = $dclient->count;
                }
            }
        }



        return response()->json([
            'status' => TRUE,
            'data' => [
                'databases' => $databases,
                'statistics' => $statistics
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
            $table = DB::table('files');

            $table_clone = clone $table;
            $calledClients = clone $table;

            $statistics
                = $table_clone->select(DB::raw('count(*) as count, files.id, clients.database, clients.status, statuses.name, statuses.id as status_id'))
                ->join('clients', 'clients.database', '=', 'files.id')
                ->join('statuses', 'statuses.name', '=', 'clients.status')
                ->where('files.id', $id)
                ->groupBy(['files.id', 'clients.status'])->get();

            foreach ($statistics as $key => $stats) {
                unset($stats->id);
                unset($stats->name);
            }

            $calledClients = $calledClients->select(DB::raw('clients.database, files.id, processed_clients.client_id, clients.id, count(*) as count'))
                ->join('clients', 'clients.database', '=', 'files.id')
                ->join('processed_clients', 'processed_clients.client_id', '=', 'clients.id')
                ->where('files.id', $id)
                ->where('processed_clients.processed', 1)
                ->groupBy('files.id')->count('processed_clients.id');


            $total_count = DB::table('clients')->where('database', $id)->count('id');
            $stats = [
                'statuses' => $statistics,
                'total_statistic' => [
                    'total_count' => $total_count,
                    'remainder' => $total_count - $calledClients,
                    'called_clients' => $calledClients
                ]
            ];

            $database = $table->where('id', $id)->paginate(15);
            $clients = Client::where('database', $id)
                ->paginate(20);

            foreach ($clients as $client) {

                $manager = DB::table('processed_clients')->select(DB::raw('processed_clients.client_id as id, processed_clients.manager_id as manager_id, users.id as manager_id, users.login as login'))
                    ->join('users', 'users.id', '=', 'processed_clients.manager_id')
                    ->where('processed_clients.client_id', $client->id)->orderBy('id', 'desc')->first();

                if ($manager) {
                    $client->manager = [
                        'id' => $manager->manager_id,
                        'login' => $manager->login
                    ];
                }
            }

            return response()->json([
                'status' => TRUE,
                'data' => [
                    'database' => $database,
                    'clients' => $clients,
                    'statistic' => $stats
                ],
            ]);
        } catch (Exception $exception) {
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
            if ($validator->fails()) {
                return response()->json([
                    'status' => FALSE,
                    'errors' => $validator->errors(),
                ]);
            }
            $file = $request->file('file');
            if (isset($file)) {
                if (!in_array(
                    $file->getClientOriginalExtension(),
                    ['xls', 'xlsx', 'txt']
                )) {
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
            $filename = md5(time() . '_' . pathinfo(
                $file->getClientOriginalName(),
                PATHINFO_FILENAME
            )) . '.'
                . $file->getClientOriginalExtension();
            Storage::disk('local')->putFileAs('databases', $file, $filename);
            $filePath = 'databases/' . $filename;
            $database = File::create([
                'name' => $request->name,
                'path' => $filePath,
            ]);
            $this->importClients((new ClientsImport)->toArray($file),
                $database->id
            );

            return response()->json([
                'status' => TRUE,
                'message' => 'File successfully uploaded',
            ]);
        } catch (Exception $exception) {
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
            foreach ($clients as $circle => $circle_clients) {
                if (count($circle_clients) < 2) {
                    break;
                }
                $processedClients = [];
                foreach ($circle_clients as $client) {
                    $bio = explode(' ', $client[0]);
                    $processedClients[] = [
                        'first_name' => $bio[1],
                        'last_name' => $bio[0],
                        'surname' => $bio[2],
                        'fullname' => $client[0],
                        'phone' => str_replace(" ", "", $client[1]),
                        'database' => $database,
                    ];
                }
                Client::insert($processedClients);
            }
            $freeManagers = User::where('current_client', '=', NULL)->get();
            $webSettings = Setting::select('preinstall_text')->where('id', 1)->first();
            foreach ($freeManagers as $manager) {
                $freeClient = Client::where('processed', 0)
                    ->where('database', $database)->inRandomOrder();
                $clientData = $freeClient->first();
                if (!$clientData) {
                    break;
                }
                User::where('id', $manager->id)
                    ->update(['current_client' => $clientData->id]);
                Client::where('id', $clientData->id)
                    ->update(['processed' => 1]);
                ProcessedClient::create([
                    'client_id' => $clientData->id,
                    'manager_id' => $manager->id,
                    'information' => $webSettings->preinstall_text
                ]);
            }

            return FALSE;
        } catch (Exception $exception) {
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
        if ($validator->fails()) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors(),
            ]);
        }
        $file = File::where('id', $id);
        if (!$file->exists()) {
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
        if (!$file->exists()) {
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
