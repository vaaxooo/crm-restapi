<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ServiceReporting;
use Illuminate\Validation\ValidationException;

class ReportingController extends Controller
{
    private $reporting;

    public function __construct()
    {
        $this->middleware('auth:api');

        $this->reporting = new ServiceReporting();
    }

    /**
     * @throws ValidationException
     */
    public function income(Request $request): JsonResponse
    {
        return $this->reporting->income($request);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function incomeHistory(Request $request): JsonResponse
    {
        return $this->reporting->incomeHistory($request);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function incomeDelete($id): JsonResponse
    {
        return $this->reporting->incomeDelete($id);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function expense(Request $request): JsonResponse
    {
        return $this->reporting->expense($request);
    }

    /**
     * @return JsonResponse
     */
    public function expenseHistory(Request $request): JsonResponse
    {
        return $this->reporting->expenseHistory($request);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function expenseDelete($id): JsonResponse
    {
        return $this->reporting->expenseDelete($id);
    }

    /**
     * @return mixed
     */
    public function kurs()
    {
        return $this->reporting->kurs();
    }

    /**
     * @return JsonResponse
     */
    public function salaries(Request $request): JsonResponse
    {
        return $this->reporting->salaries($request);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function payouts(Request $request): JsonResponse
    {
        return $this->reporting->payouts($request);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function payoutsHistory(Request $request): JsonResponse
    {
        return $this->reporting->payoutsHistory();
    }
}
