<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Require email verification
    |--------------------------------------------------------------------------
    |
    | When true, new SaaS registrations must verify their email before accessing
    | the company panel. Set to false in local/staging when mail is unavailable.
    |
    */

    'require_email_verification' => env('CLIENTLOOP_REQUIRE_EMAIL_VERIFICATION', false),

    /*
    |--------------------------------------------------------------------------
    | Signup notification
    |--------------------------------------------------------------------------
    |
    | Extra addresses that receive an email when a company registers. Separate
    | multiple addresses with commas. Superadmins are notified as well.
    |
    */

    'signup_notification_email' => env('CLIENTLOOP_SIGNUP_NOTIFICATION_EMAIL'),

];
