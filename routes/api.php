<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublicAppointmentController;
use App\Http\Controllers\Api\PublicPrescriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify', [AuthController::class, 'verify']);
    Route::post('resend-verification', [AuthController::class, 'resend']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('throttle:30,1')->group(function () {
    Route::get('public/appointment/{slug}', [PublicAppointmentController::class, 'show']);
    Route::post('public/appointment/{slug}', [PublicAppointmentController::class, 'store']);
    Route::get('public/prescriptions/{token}', [PublicPrescriptionController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::post('profile/logo', [ProfileController::class, 'uploadLogo']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::get('availability/rules', [AvailabilityController::class, 'rules']);
    Route::post('availability/rules', [AvailabilityController::class, 'storeRule']);
    Route::put('availability/rules/{rule}', [AvailabilityController::class, 'updateRule']);
    Route::delete('availability/rules/{rule}', [AvailabilityController::class, 'destroyRule']);
    Route::get('availability/exceptions', [AvailabilityController::class, 'exceptions']);
    Route::post('availability/exceptions', [AvailabilityController::class, 'storeException']);
    Route::delete('availability/exceptions/{exception}', [AvailabilityController::class, 'destroyException']);

    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::patch('appointments/{appointment}', [AppointmentController::class, 'update']);

    Route::get('patients', [PatientController::class, 'index']);
    Route::post('patients', [PatientController::class, 'store']);
    Route::get('patients/{patient}', [PatientController::class, 'show']);

    Route::get('medicines', [MedicineController::class, 'search']);

    Route::get('prescriptions', [PrescriptionController::class, 'index']);
    Route::post('prescriptions', [PrescriptionController::class, 'store']);
    Route::get('prescriptions/{prescription}', [PrescriptionController::class, 'show']);
    Route::put('prescriptions/{prescription}', [PrescriptionController::class, 'update']);
    Route::post('prescriptions/{prescription}/share-email', [PrescriptionController::class, 'shareEmail']);
});
