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

    /*
    |--------------------------------------------------------------------------
    | TextBee Free Outbound SMS Gateway (https://textbee.dev)
    | Offers 300 free SMS/month to real phone numbers worldwide
    |--------------------------------------------------------------------------
    */
    'textbee' => [
        'api_key' => env('SMS_TEXTBEE_API_KEY'),
        'device_id' => env('SMS_TEXTBEE_DEVICE_ID'),
        'base_url' => env('SMS_TEXTBEE_URL', 'https://api.textbee.dev/api/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Textbelt SMS Gateway (https://textbelt.com)
    | 1 free SMS/day using key 'textbelt' or paid key
    |--------------------------------------------------------------------------
    */
    'textbelt' => [
        'key' => env('SMS_TEXTBELT_KEY', 'textbelt'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shelex Free OTP & Inbound SMS Verification API
    | https://otp-api.shelex.dev/api/
    |--------------------------------------------------------------------------
    */
    'free_otp' => [
        'base_url' => env('SMS_FREE_OTP_API_URL', 'https://otp-api.shelex.dev/api'),
    ],

];
