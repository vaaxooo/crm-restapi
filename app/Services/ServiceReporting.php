<?php

namespace App\Services;

use App\Models\PayoutsHistory;
use App\Models\User;
use App\Models\ReportingIncome;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Redis;

class ServiceReporting
{
    private $kurs;

    public function __construct()
    {
        if (!Redis::get('kurs')) {
            $kurs = json_decode(@file_get_contents('http://api.currencylayer.com/live?access_key=56cbeab727c4d31acbb87b32604ee8c5&format=1'));
            $btc_kurs = json_decode(@file_get_contents('https://apirone.com/api/v2/ticker?currency=btc'));
            $kurs->quotes->BTCUSD = $btc_kurs->usd;
            Redis::set('kurs', json_encode($kurs->quotes), 'EX', 86400);
        }
        $this->kurs = (object) json_decode(Redis::get('kurs'));
    }

    /**
     * @param $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function income($request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required', #Дата
            'payout' => 'required', #Сумма выплаты
            'currency' => 'required', #Валюта
            'percent' => 'required', #Общий процент
            'payouts_list' => 'required', #Массив с менеджерами
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors()
            ]);
        }
        $params = (object) $validator->validated();
        /*[
            {"managers": {"1": "BIO", "2": "BIO"}, "percent": 7.5, "comment": "TEST"},
            {"managers": {"1": "BIO", "2": "BIO"}, "percent": 7.5, "comment": "TEST"},
        ]*/
        $processedData = [];
        $total_percent = 0;
        foreach (json_decode($params->payouts_list) as $payout) {
            $payout = (object) $payout;
            foreach ($payout->managers as $manager_id => $manager) {
                $processedData[] = [
                    'date' => $params->date,
                    'comment' => $payout->comment,
                    'manager_bio' => $manager,
                    'manager_id' => $manager_id,
                    'total_amount' => $params->payout,
                    'temp_percent' => $payout->percent
                ];
                $total_percent += $payout->percent;
            }
        }
        if ($total_percent > $params->percent) {
            return response()->json([
                'status' => FALSE,
                'message' => 'The divided percentage between managers cannot exceed the fixed percentage'
            ]);
        }
        foreach ($processedData as $key => $manager) {
            $manager = (object) $manager;
            $processedData[$key]['payout'] = $params->payout / count($processedData); //Выплата = кол-во менеджеров / общая сумма
            $processedData[$key]['salary'] = $params->payout * $manager->temp_percent / 100; //Доход = Общая сумма / процент менеджера
            unset($processedData[$key]['temp_percent']);
        }
        ReportingIncome::insert($processedData);
        $generalManager = User::select('first_name', 'last_name', 'surname', 'login', 'id')->where('role', 'admin')->first();
        ReportingIncome::create([
            'date' => $params->date,
            'comment' => 'Percent',
            'manager_bio' => $generalManager->first_name . " " . $generalManager->last_name . " " . $generalManager->surname,
            'manager_id' => $generalManager->id,
            'total_amount' => $params->payout,
            'payout' => 0,
            'salary' => $params->payout * 10 / 100,
            'created_at' => date('Y-m-d H:i:s'),
            'role' => 'general_manager'
        ]);
        return response()->json([
            'status' => TRUE,
            'message' => 'The report was successfully saved',
            'data' => $processedData
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function incomeHistory($request): JsonResponse
    {
        $table = DB::table('reporting_incomes')->select('date', 'comment', 'manager_bio', 'currency', 'salary', 'payout', 'created_at');
        if (isset($request->dates)) {
            $table->whereDate('date', '>=', $request->dates['start_date'])
                ->whereDate('date', '<=', $request->dates['end_date']);
        } else {
            $table->whereDate('date', '>=', \Carbon\Carbon::now()->weekday(1))
                ->whereDate('date', '<=', new Carbon("Sunday"));
        }
        $week_payouts = clone $table;
        $total_btc = clone $table;
        $total_usdt = clone $table;
        $total_salary_fond = clone $table;
        $salary_general_manager = clone $table;
        $salary_managers = clone $table;

        $data = $table->paginate(20);
        return response()->json([
            'status' => TRUE,
            'data' => $data,
            'statistics' => [
                'week_payouts' => (int) $week_payouts->sum('payout'),
                'total_btc' => $total_btc->where('currency', 'btc')->sum('payout'),
                'total_usdt' => $total_usdt->where('currency', 'usdt')->sum('payout'),
                'total_salary_fond' => $total_salary_fond->sum('salary'),
                'salary_general_manager' => $salary_general_manager->where('role', 'general_manager')->sum('salary'),
                'salary_managers' => $salary_managers->where('role', 'manager')->sum('salary')
            ]
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function expense($request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'comment' => 'required',
            'sum' => 'required|integer',
            'currency' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors()
            ]);
        }

        if (strtoupper($request->currency) != 'BTC') {
            $sum_in_dollar = $request->sum;
            if (strtoupper($request->currency) == 'UAH') {
                $exchange_rate = (float) round($this->kurs->USDUAH, 2);
                $sum_in_dollar = $request->sum / $exchange_rate;
            }
        } else {
            $exchange_rate = $this->kurs->BTCUSD;
            $sum_in_dollar = $request->sum * $exchange_rate;
        }

        Expense::create(array_merge($validator->validated(), ['sum_in_dollar' => $sum_in_dollar]));
        return response()->json([
            'status' => TRUE,
            'message' => 'Expense entered into the database'
        ]);
    }


    /**
     * @return JsonResponse
     */
    public function expenseHistory($request): JsonResponse
    {
        $table = DB::table('expenses');
        if (isset($request->dates)) {
            $table->whereDate('date', '>=', $request->dates['start_date'])
                ->whereDate('date', '<=', $request->dates['end_date']);
        } else {
            $table->whereDate('date', '>=', \Carbon\Carbon::now()->weekday(1))
                ->whereDate('date', '<=', new Carbon("Sunday"));
        }

        $btc = clone $table;
        $usdt = clone $table;
        $uah = clone $table;

        return response()->json([
            'status' => TRUE,
            'data' => $table->get(),
            'statistics' => [
                'btc' => $btc->where('currency', 'btc')->sum('sum'),
                'usdt' => $usdt->where('currency', 'usdt')->sum('sum'),
                'uah' => $uah->where('currency', 'uah')->sum('sum')
            ]
        ]);
    }


    /**
     * @return JsonResponse
     */
    public function kurs()
    {
        return response()->json([
            'status' => TRUE,
            'data' => $this->kurs
        ]);
    }


    /**
     * @return JsonResponse
     */
    public function salaries($request): JsonResponse
    {
        $table = DB::table('reporting_incomes')->select('manager_id', 'manager_bio', 'salary');
        if (isset($request->dates)) {
            $table->whereDate('date', '>=', $request->dates['start_date'])
                ->whereDate('date', '<=', $request->dates['end_date']);
        } else {
            $table->whereDate('date', '>=', \Carbon\Carbon::now()->weekday(1))
                ->whereDate('date', '<=', new Carbon("Sunday"));
        }

        $managers = $table->paginate(20);

        $processedManagers = [];
        $exchange_rate = (float) round($this->kurs->USDUAH, 2);
        foreach ($managers as $key => $manager) {
            $salary_uah = $manager->salary * round($exchange_rate, 2);
            $processedManagers[] = [
                'manager' => $manager->manager_bio,
                'salary_usd' => (int) round($manager->salary),
                'salary_uah' => (int) $salary_uah
            ];
        }

        return response()->json([
            'status' => TRUE,
            'data' => $processedManagers
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function incomeDelete($id): JsonResponse
    {
        $table = ReportingIncome::where('id', $id);
        if (!$table->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Income history not found'
            ]);
        }
        $table->delete();
        return response()->json([
            'status' => TRUE,
            'message' => 'Income have been successfully deleted'
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function expenseDelete($id): JsonResponse
    {
        $table = Expense::where('id', $id);
        if (!$table->exists()) {
            return response()->json([
                'status' => FALSE,
                'message' => 'Expense history not found'
            ]);
        }
        $table->delete();
        return response()->json([
            'status' => TRUE,
            'message' => 'Expense have been successfully deleted'
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function payouts($request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'sum' => 'required',
            'currency' => 'required',
            'percent' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors()
            ]);
        }
        $sum = $request->sum;
        $exchanger_rate = $this->kurs->USDUAH;
        if (strtoupper($request->currency) == 'BTC') {
            $sum = round($request->sum * $this->kurs->BTCUSD);
            $exchanger_rate = $this->kurs->BTCUSD;
        }
        PayoutsHistory::create([
            'date' => $request->date,
            'sum' => $sum,
            'currency' => $request->currency,
            'percent' => $request->percent,
            'exchange_sum' => $request->sum,
            'exchange_rate' => $exchanger_rate
        ]);
        return response()->json([
            'status' => TRUE,
            'message' => 'Payment for withdrawal has been successfully sent'
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function payoutsHistory(): JsonResponse
    {
        $data = PayoutsHistory::paginate(15);
        return response()->json([
            'status' => TRUE,
            'data' => $data,
            'statistics' => [
                'total_usd' => PayoutsHistory::sum('sum'),
                'btc' => PayoutsHistory::where('currency', 'btc')->sum('sum'),
                'usdt' => PayoutsHistory::where('currency', 'usdt')->sum('sum')
            ]
        ]);
    }
}
