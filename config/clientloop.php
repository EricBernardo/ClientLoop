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

    'require_email_verification' => env('CLIENTLOOP_REQUIRE_EMAIL_VERIFICATION', true),

];
