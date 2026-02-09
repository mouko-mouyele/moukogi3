<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AlertController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard (lecture seule pour tous)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Catégories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    
    // Produits
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Prédictions
    Route::get('/products/{product}/predict', [PredictionController::class, 'predict']);
    Route::get('/products/{product}/chart-data', [PredictionController::class, 'chartData']);
    Route::get('/predictions', [PredictionController::class, 'allPredictions']);

    // Alertes
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::get('/alerts/{alert}', [AlertController::class, 'show']);

    // Routes pour gestionnaires et admins
    Route::middleware('role:gestionnaire,admin')->group(function () {
        // Catégories
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Produits
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Mouvements de stock
        Route::get('/stock-movements', [StockMovementController::class, 'index']);
        Route::post('/stock-movements', [StockMovementController::class, 'store']);
        Route::get('/stock-movements/{stockMovement}', [StockMovementController::class, 'show']);
        Route::put('/stock-movements/{stockMovement}', [StockMovementController::class, 'update']);
        Route::delete('/stock-movements/{stockMovement}', [StockMovementController::class, 'destroy']);

        // Inventaires
        Route::get('/inventories', [InventoryController::class, 'index']);
        Route::post('/inventories', [InventoryController::class, 'store']);
        Route::get('/inventories/{inventory}', [InventoryController::class, 'show']);
        Route::put('/inventories/{inventory}', [InventoryController::class, 'update']);
        Route::delete('/inventories/{inventory}', [InventoryController::class, 'destroy']);

        // Alertes
        Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);
        Route::post('/alerts/{alert}/dismiss', [AlertController::class, 'dismiss']);
        Route::post('/alerts/check-all', [AlertController::class, 'checkAll']);
    });

    // Routes réservées aux admins
    Route::middleware('role:admin')->group(function () {
        // Routes admin spécifiques si nécessaire
    });

    // Exports
    Route::get('/export/products/excel', [\App\Http\Controllers\ExportController::class, 'exportProductsExcel']);
    Route::get('/export/movements/excel', [\App\Http\Controllers\ExportController::class, 'exportMovementsExcel']);
    Route::get('/export/inventories/{inventory}/pdf', [\App\Http\Controllers\ExportController::class, 'exportInventoryPdf']);
    Route::get('/export/products/{product}/pdf', [\App\Http\Controllers\ExportController::class, 'exportProductPdf']);
});
