<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DentistController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Pages
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/about', [PublicController::class, 'about'])->name('about');
Route::get('/features', [PublicController::class, 'features'])->name('features');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');

// Authentication Routes
Route::middleware(['guest', 'bruteforce'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/settings', [AuthController::class, 'showSettings'])->name('settings');
    Route::post('/settings', [AuthController::class, 'updateSettings'])->middleware('throttle:5,1')->name('settings.update');

    // Patient Routes
    Route::prefix('patient')->name('patient.')->middleware('role:patient')->group(function () {
        Route::get('/dashboard', [PatientController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [PatientController::class, 'profile'])->name('profile');
        Route::post('/profile', [PatientController::class, 'updateProfile'])->name('profile.update');
        
        Route::get('/request-appointment', [PatientController::class, 'requestForm'])->name('appointments.request');
        Route::post('/request-appointment', [PatientController::class, 'storeAppointment'])->middleware('throttle:10,1')->name('appointments.store');
        Route::get('/available-slots', [PatientController::class, 'getAvailableSlots'])->name('appointments.slots');
        
        Route::get('/appointments', [PatientController::class, 'myAppointments'])->name('appointments.index');
        Route::get('/appointments/{appointment}', [PatientController::class, 'showAppointment'])->name('appointments.show');
        Route::get('/appointments/{appointment}/reschedule', [PatientController::class, 'rescheduleForm'])->name('appointments.reschedule');
        Route::post('/appointments/{appointment}/reschedule', [PatientController::class, 'storeReschedule'])->middleware('throttle:10,1')->name('appointments.reschedule.store');
        Route::post('/appointments/{appointment}/cancel', [PatientController::class, 'cancelAppointment'])->middleware('throttle:10,1')->name('appointments.cancel');
        
        Route::get('/notifications', [PatientController::class, 'notifications'])->name('notifications');
    });

    // Staff and Administrator Routes
    Route::prefix('staff')->name('staff.')->middleware('role:staff,admin')->group(function () {
        Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
        Route::get('/appointments', [StaffController::class, 'appointments'])->name('appointments.index');
        Route::get('/appointments/{appointment}', [StaffController::class, 'showAppointment'])->name('appointments.show');
        Route::post('/appointments/{appointment}/confirm', [StaffController::class, 'confirm'])->name('appointments.confirm');
        Route::post('/appointments/{appointment}/reject', [StaffController::class, 'reject'])->name('appointments.reject');
        Route::post('/appointments/{appointment}/modify', [StaffController::class, 'modify'])->name('appointments.modify');
        Route::post('/appointments/{appointment}/complete', [StaffController::class, 'complete'])->name('appointments.complete');
        Route::post('/appointments/{appointment}/cancel', [StaffController::class, 'cancel'])->name('appointments.cancel');
        
        Route::get('/calendar', [StaffController::class, 'calendar'])->name('calendar');
        Route::get('/patients', [StaffController::class, 'patients'])->name('patients');
        
        Route::get('/dentists', [StaffController::class, 'dentists'])->name('dentists.index');
        Route::post('/dentists', [StaffController::class, 'storeDentist'])->name('dentists.store');
        Route::put('/dentists/{dentist}', [StaffController::class, 'updateDentist'])->name('dentists.update');
        Route::post('/dentists/{dentist}/availability', [StaffController::class, 'updateAvailability'])->name('dentists.availability');
        
        Route::get('/notifications', [StaffController::class, 'notificationLogs'])->name('notifications.index');
        Route::post('/notifications/{notification}/retry', [StaffController::class, 'retryNotification'])->name('notifications.retry');
        
        Route::get('/settings', [StaffController::class, 'settings'])->name('settings');
        Route::post('/test-sms', [StaffController::class, 'testSms'])->name('test-sms');

        // Shelex Free OTP & Inbound Test SMS Endpoints
        Route::prefix('otp-service')->name('otp.')->group(function () {
            Route::get('/countries', [StaffController::class, 'getFreeOtpCountries'])->name('countries');
            Route::get('/list/{country}', [StaffController::class, 'getFreeOtpNumbers'])->name('numbers');
            Route::get('/verify/{country}/{phone}', [StaffController::class, 'getFreeOtpVerification'])->name('verify');
        });
    });

    // Dentist Routes
    Route::prefix('dentist')->name('dentist.')->middleware('role:dentist')->group(function () {
        Route::get('/dashboard', [DentistController::class, 'dashboard'])->name('dashboard');
        Route::get('/schedule', [DentistController::class, 'mySchedule'])->name('schedule');
        Route::get('/appointments/{appointment}', [DentistController::class, 'showAppointment'])->name('appointments.show');
        Route::post('/appointments/{appointment}/complete', [DentistController::class, 'markCompleted'])->name('appointments.complete');
    });
});
