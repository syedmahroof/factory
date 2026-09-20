<?php

use Illuminate\Support\Facades\Route;
use Integrations\Crm\Http\Controllers\CrmController;

Route::post('push-merchant', [CrmController::class, 'pushMerchant'])->name('push-merchant');
Route::get('get-merchant-details', [CrmController::class, 'getMerchant'])->name('get-merchant-details');
Route::post('update-crm-id', [CrmController::class, 'updateCrmId'])->name('update-crm-id');
Route::post('get-merchant-payments', [CrmController::class, 'getMerchantPayments'])->name('get-merchant-payments');
Route::post('get-processing-ach', [CrmController::class, 'getProcessingAch'])->name('get-processing-ach');
Route::post('add-merchant-notes', [CrmController::class, 'addMerchantNotes'])->name('add-merchant-notes');
Route::post('get-merchant-notes', [CrmController::class, 'getMerchantNotes'])->name('get-merchant-notes');
