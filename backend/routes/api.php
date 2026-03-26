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

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::post("/logout", [AuthController::class, "logout"]);


    Route::get("barangs", [BarangController::class, "index"]);


    Route::middleware(AdminMiddleware::class)->group(function () {

        Route::post("barang", [BarangController::class, "create"]);
        Route::delete("barang/{id}", [BarangController::class, "destroy"]);
        Route::post("barang/{id}/suppliers", [BarangController::class, "assignSuppliers"]);
 

 
        Route::post("supplier", [SupplierController::class, "create"]);
        Route::delete("supplier/{id}", [SupplierController::class, "destroy"]);
        Route::post("supplier/{id}", [SupplierController::class, "update"]);

    });



    Route::middleware(TwoRoleMiddleware::class)->group(function () {
        Route::get("suppliers", [SupplierController::class, "index"]);

        Route::post("/inventoryIn", [BarangMasukController::class, "in"]);
        Route::post("/inventoryOut", [BarangKeluarController::class, "out"]);
        Route::post("barang/{id}", [BarangController::class, "update"]);
        Route::post("barang/{id}/opName", [BarangController::class, "addOpName"]);
        Route::get("barang/opName", [BarangController::class, "opName"]);
    });
});
