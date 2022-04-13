<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['api']], function ($route) {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', '\App\Http\Controllers\AuthController@login');
        Route::post('logout', '\App\Http\Controllers\AuthController@logout');
        Route::post('refresh', '\App\Http\Controllers\AuthController@refresh');
        Route::post('me', '\App\Http\Controllers\AuthController@me');
    });

    Route::group(['prefix' => 'account'], function () {
        Route::post('change-password', '\App\Http\Controllers\AccountController@changePassword');
    });

    Route::group(['prefix' => 'managers'], function () {
        Route::get('all', '\App\Http\Controllers\ManagerController@allManagers');
        Route::get('{id}/get', '\App\Http\Controllers\ManagerController@showManager');
        Route::post('create', '\App\Http\Controllers\ManagerController@addManager');
        Route::patch('{id}/update', '\App\Http\Controllers\ManagerController@updateManager');
        Route::delete('{id}/delete', '\App\Http\Controllers\ManagerController@deleteManager');
        Route::get('{id}/refresh-online', '\App\Http\Controllers\ManagerController@refreshOnline');
        Route::get('{id}/passed-tests', '\App\Http\Controllers\ManagerController@getPassedTests');
        Route::get('{id}/passed-test/{test_id}', '\App\Http\Controllers\ManagerController@getPassedTest');
        Route::post('{id}/passing-test/{test_id}', '\App\Http\Controllers\ManagerController@passingTest');
    });

    Route::group(['prefix' => 'databases'], function () {
        Route::post('upload', '\App\Http\Controllers\FileController@uploadFile');
        Route::patch('{id}/rename', '\App\Http\Controllers\FileController@renameDatabase');
        Route::delete('{id}/delete', '\App\Http\Controllers\FileController@deleteDatabase');
        Route::get('all', '\App\Http\Controllers\FileController@all');
        Route::get('{id}/get', '\App\Http\Controllers\FileController@show');
    });

    Route::group(['prefix' => 'clients'], function () {
        Route::get('duplicates', '\App\Http\Controllers\ClientController@duplicates');
        Route::delete('duplicates/delete', '\App\Http\Controllers\ClientController@deleteAllDuplicates');
        Route::delete('duplicates/statuses/delete', '\App\Http\Controllers\ClientController@deleteDuplicates');
        Route::get('search', '\App\Http\Controllers\ClientController@searchClient');
        Route::delete('{id}/delete', '\App\Http\Controllers\ClientController@deleteClient');
        Route::get('{id}/get', '\App\Http\Controllers\ClientController@show');
        Route::patch('{id}/update', '\App\Http\Controllers\ClientController@updateClient');
        Route::patch('{id}/set-status', '\App\Http\Controllers\ClientController@setStatus');
        Route::patch('{id}/transfer', '\App\Http\Controllers\ClientController@transferClient');
        Route::get('active', '\App\Http\Controllers\ClientController@activeClients');
    });

    Route::group(['prefix' => 'statuses'], function () {
        Route::get('all', '\App\Http\Controllers\StatusController@all');
        Route::patch('update', '\App\Http\Controllers\StatusController@updateStatuses');
    });

    Route::group(['prefix' => 'settings'], function () {
        Route::get('get', '\App\Http\Controllers\SettingController@getSettings');
        Route::patch('preinstall-text', '\App\Http\Controllers\SettingController@setPreinstallText');
        Route::patch('jivo', '\App\Http\Controllers\SettingController@setJivoUrl');
    });

    Route::group(['prefix' => 'statistics'], function () {
        Route::get('managers', '\App\Http\Controllers\ManagerController@statistic');
    });

    Route::group(['prefix' => 'tests'], function () {
        Route::get('all', '\App\Http\Controllers\TestController@all');
        Route::get('{id}/get', '\App\Http\Controllers\TestController@show');
        Route::post('create', '\App\Http\Controllers\TestController@create');
        Route::delete('{id}/delete', '\App\Http\Controllers\TestController@delete');
    });

    Route::group(['prefix' => 'dialogue-templates'], function () {
        Route::get('all', '\App\Http\Controllers\SettingController@dialogueTemplates');
        Route::get('{id}/get', '\App\Http\Controllers\SettingController@showDialogueTemplate');
        Route::post('create', '\App\Http\Controllers\SettingController@createDialogueTemplate');
        Route::patch('{id}/update', '\App\Http\Controllers\SettingController@updateDialogueTemplate');
        Route::delete('{id}/delete', '\App\Http\Controllers\SettingController@deleteDialogueTemplate');
    });

    Route::group(['prefix' => 'reporting'], function () {
        Route::post('income', '\App\Http\Controllers\ReportingController@income');
        Route::get('income/history', '\App\Http\Controllers\ReportingController@incomeHistory');
        Route::delete('income/{id}/delete', '\App\Http\Controllers\ReportingController@incomeDelete');
        Route::post('expense', '\App\Http\Controllers\ReportingController@expense');
        Route::get('expense/history', '\App\Http\Controllers\ReportingController@expenseHistory');
        Route::delete('expense/{id}/delete', '\App\Http\Controllers\ReportingController@expenseDelete');
        Route::get('kurs', '\App\Http\Controllers\ReportingController@kurs');
        Route::get('salaries', '\App\Http\Controllers\ReportingController@salaries');

        Route::post('payouts', '\App\Http\Controllers\ReportingController@payouts');
        Route::get('payouts/history', '\App\Http\Controllers\ReportingController@payoutsHistory');
    });
});
