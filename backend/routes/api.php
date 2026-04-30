<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BarangKeluarController;
use App\Http\Controllers\BarangMasukController;
use App\Http\Controllers\SupplierController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\TwoRoleMiddleware;
use Illuminate\Support\Facades\Route;

Route::post("/login", [AuthController::class, "login"]);
Route::post("/register", [AuthController::class, "register"]);

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::post("/logout", [AuthController::class, "logout"]);


    Route::get("products", [BarangController::class, "index"]);


    Route::middleware(AdminMiddleware::class)->group(function () {

        Route::post("products", [BarangController::class, "create"]);
        Route::delete("products/{id}", [BarangController::class, "destroy"]);
        Route::post("products/{id}/suppliers", [BarangController::class, "assignSuppliers"]);



        Route::post("suppliers", [SupplierController::class, "create"]);
        Route::delete("suppliers/{id}", [SupplierController::class, "destroy"]);
        Route::post("suppliers/{id}", [SupplierController::class, "update"]);

    });

    Route::middleware(TwoRoleMiddleware::class)->group(function () {
        Route::get("suppliers", [SupplierController::class, "index"]);

        Route::post("/inventory-in", [BarangMasukController::class, "in"]);
        Route::post("/inventory-in/{id}", [BarangMasukController::class, "update"]);
        Route::delete("/inventory-in/{id}", [BarangMasukController::class, "destroy"]);
        Route::get("/inventory-in", [BarangMasukController::class, "index"]);
        
        Route::post("/inventory-out", [BarangKeluarController::class, "out"]);
        Route::get("/inventory-out", [BarangKeluarController::class, "index"]);

        Route::post("products/{id}", [BarangController::class, "update"]);
        Route::post("products/{id}/op-name", [BarangController::class, "addOpName"]);
        Route::get("products/op-name", [BarangController::class, "opName"]);
    });
});
