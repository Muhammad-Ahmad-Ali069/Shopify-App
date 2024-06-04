<?php

use App\Http\Controllers\Controller;
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

Route::get('/', function () {
    return "Hello API";
});
Route::post('/save-custom-attributes', [Controller::class, 'updateOrderLineItemCustomAttributes']);
Route::post('/get-order-line-item', [Controller::class, 'fetchOrderById']);
Route::get('/get-all-orders', [Controller::class, 'fetchAllOrders']);
