<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Website Inspection Deterrence and Frontend Protection
    |--------------------------------------------------------------------------
    | Discourages casual inspection while maintaining normal navigation,
    | accessibility, form interactions, and legitimate user workflows.
    |
    | IMPORTANT NOTICE:
    | Browser developer tools cannot be completely disabled by any website
    | because clients have ultimate control over their browser runtime.
    | These controls serve as behavioral deterrents only; genuine security
    | is enforced strictly through server-side authentication, authorization,
    | rate limiting, input firewalls, and tamper-proof audit trails.
    |--------------------------------------------------------------------------
    */
    'inspection_deterrence' => [
        'enabled' => env('SECURITY_DETERRENCE_ENABLED', true),
        'disable_context_menu' => env('SECURITY_DISABLE_CONTEXT_MENU', true),
        'block_shortcuts' => env('SECURITY_BLOCK_DEV_SHORTCUTS', true),
        'detect_devtools' => env('SECURITY_DETECT_DEVTOOLS', true),
        'show_warning' => env('SECURITY_SHOW_DEVTOOLS_WARNING', true),
        'allow_admin_bypass' => env('SECURITY_ALLOW_ADMIN_BYPASS', true),
    ],
];
