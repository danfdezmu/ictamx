<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shared login between the church site and ChordSystem
    |--------------------------------------------------------------------------
    |
    | The church site is the provider. ChordSystem is the client. A short
    | lived encrypted token hands the verified person from one site to the
    | other. Each app keeps its own users table and its own session.
    |
    */

    'secret' => env('SHARED_AUTH_SECRET'),

    'role' => env('SHARED_AUTH_ROLE'),

    'peer' => env('SHARED_AUTH_PEER_URL'),

];
