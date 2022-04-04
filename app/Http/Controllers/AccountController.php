<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ServiceAccount;

class AccountController extends Controller
{
    private $account;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->account = new ServiceAccount();
    }

    /**
     * Change user password
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->account->changePassword($request);
    }

}
