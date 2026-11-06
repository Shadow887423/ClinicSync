<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway Credentials & Drivers
    |--------------------------------------------------------------------------
    */

    'provider' => env('SMS_GATEWAY_PROVIDER', 'simulated'),
    'simulation_enabled' => filter_var(env('SMS_SIMULATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'reminder_hours_before' => env('SMS_REMINDER_HOURS_BEFORE', 24),

    'twilio' => [
        'sid' => env('SMS_TWILIO_SID'),
        'token' => env('SMS_TWILIO_AUTH_TOKEN'),
        'from' => env('SMS_TWILIO_FROM'),
    ],

    'semaphore' => [
        'api_key' => env('SMS_SEMAPHORE_API_KEY'),
        'sender_name' => env('SMS_SEMAPHORE_SENDER_NAME', 'CLINICSYNC'),
    ],

    'custom_http' => [
        'url' => env('SMS_CUSTOM_HTTP_URL'),
        'token' => env('SMS_CUSTOM_HTTP_TOKEN'),
    ],

];
