<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\CategoryFileController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ColorThemeController;
use App\Http\Controllers\Api\ContractFileController;
use App\Http\Controllers\Api\DomiciliationFileTypeController;
use App\Http\Controllers\Api\ReceivedFileController;
use App\Http\Controllers\Api\SupportingFileController;
use App\Http\Controllers\Api\VirtualOfficeOfferController;
use App\Http\Controllers\Api\VirtualOfficeController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\SingleInvoiceController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\LatestInvoiceController;
use App\Http\Controllers\Api\InvoiceDetailsController;
use App\Http\Controllers\Api\CompanyDataController;
use App\Http\Controllers\Api\DocumentAnalysisController;
use App\Http\Controllers\Api\BasicUserController;
use App\Http\Controllers\Api\AdminUserController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/password-hash', [AuthController::class, 'getUserPasswordHash']);
// category_file
Route::get('/category-files', [CategoryFileController::class, 'index']);
Route::get('/category-files/{id}', [CategoryFileController::class, 'show']);
// company
Route::get('/companies', [CompanyController::class, 'index']);
Route::get('/companies/{id}', [CompanyController::class, 'show']);
// color_theme
Route::get('/color-themes', [ColorThemeController::class, 'index']);
Route::get('/color-themes/{id}', [ColorThemeController::class, 'show']);
Route::get('/color-themes/company/{companyId}', [ColorThemeController::class, 'getByCompany']);
// category_file
Route::get('/contract-files', [ContractFileController::class, 'index']);
Route::get('/contract-files/{id}', [ContractFileController::class, 'show']);
// domiciliation_file_type
Route::get('/domiciliation-file-types', [DomiciliationFileTypeController::class, 'index']);
Route::get('/domiciliation-file-types/{id}', [DomiciliationFileTypeController::class, 'show']);
// received-file
Route::get('/received-files', [ReceivedFileController::class, 'index']);
Route::get('/received-files/{id}', [ReceivedFileController::class, 'show']);
// supporting_file
Route::get('/supporting-files', [SupportingFileController::class, 'index']);
Route::get('/supporting-files/{id}',[SupportingFileController::class, 'show']);
// virtual-office-offer
Route::get('/virtual-office-offer', [VirtualOfficeOfferController::class, 'index']);
Route::get('/virtual-office-offer/{id}', [VirtualOfficeOfferController::class, 'show']);
// virtual-office
Route::get('/virtual-office', [VirtualOfficeController::class, 'index']);
Route::get('/virtual-office/{id}', [VirtualOfficeController::class, 'show']);
// invoice
Route::get('/invoice', [InvoiceController::class, 'index']);
Route::get('/invoice/single', [SingleInvoiceController::class, 'index']);
Route::get('/invoice/single/{id}', [SingleInvoiceController::class, 'show']);
Route::get('/invoice/latest/{companyId}', [LatestInvoiceController::class, 'show']);
Route::get('/invoice/details/{id}', [InvoiceDetailsController::class, 'show']);
// chat
Route::get('/company/{slug}', [CompanyDataController::class, 'show']);
Route::post('/chat', [ChatController::class, 'chat']);
// file analysis
Route::post('/analyze-file', [DocumentAnalysisController::class, 'analyze']);
// basic-user
Route::get('/basic-user', [BasicUserController::class, 'index']);
Route::get('/basic-user/{id}', [BasicUserController::class, 'show']);
// admin-user
Route::get('/admin-user', [AdminUserController::class, 'index']);
Route::get('/admin-user/{id}', [AdminUserController::class, 'show']);

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // category_file
    Route::post('/category-files', [CategoryFileController::class, 'store']);
    Route::put('/category-files/{id}', [CategoryFileController::class, 'update']);
    Route::delete('/category-files/{id}', [CategoryFileController::class, 'destroy']);
    // company
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::patch('/companies/{id}', [CompanyController::class, 'update']);
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);
    // color_theme
    Route::post('/color-themes/upsert', [ColorThemeController::class, 'upsert']);
    Route::post('/color-themes', [ColorThemeController::class, 'store']);
    Route::patch('/color-themes/{id}', [ColorThemeController::class, 'update']);
    Route::delete('/color-themes/{id}', [ColorThemeController::class, 'destroy']);
    // contract_file
    Route::post('/contract-files', [ContractFileController::class, 'store']);
    Route::patch('/contract-files/{id}', [ContractFileController::class, 'update']);
    Route::put('/contract-files/{id}', [ContractFileController::class, 'update']);
    // domiciliation_file_type
    Route::post('/domiciliation-file-types', [DomiciliationFileTypeController::class, 'store']);
    Route::patch('/domiciliation-file-types/{id}', [DomiciliationFileTypeController::class, 'update']);
    // received_file
    Route::post('/received-files', [ReceivedFileController::class, 'store']);
    Route::patch('/received-files/{id}', [ReceivedFileController::class, 'update']);
    // supporting_file
    Route::post('/supporting-files', [SupportingFileController::class, 'store']);
    Route::patch('/supporting-files/{id}', [SupportingFileController::class, 'update']);
    Route::delete('/supporting-files/{id}', [SupportingFileController::class, 'destroy']);
    // virtual-office-offer
    Route::post('/virtual-office-offer', [VirtualOfficeOfferController::class, 'store']);
    Route::patch('/virtual-office-offer/{id}', [VirtualOfficeOfferController::class, 'update']);
    Route::delete('/virtual-office-offer/{id}', [VirtualOfficeOfferController::class, 'delete']);
    // virtual-office
    Route::post('/virtual-office', [VirtualOfficeController::class, 'store']);
    Route::patch('/virtual-office', [VirtualOfficeController::class, 'update']);
    // invoice
    Route::post('/invoice', [InvoiceController::class, 'store']);
    Route::patch('/invoice/single/{id}', [SingleInvoiceController::class, 'update']);
    // basic-user
    Route::patch('/basic-user/{id}', [BasicUserController::class, 'update']);
    Route::put('/basic-user/{id}', [BasicUserController::class, 'update']);
    Route::delete('/basic-user/{id}', [BasicUserController::class, 'destroy']);
    // admin-user
    Route::patch('/admin-user/{id}', [AdminUserController::class, 'update']);
    Route::put('/admin-user/{id}', [AdminUserController::class, 'update']);
});