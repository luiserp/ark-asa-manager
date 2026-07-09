<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Paths
    |--------------------------------------------------------------------------
    */

    'server_dir' => env('ARK_SERVER_DIR', '/ark-server/server'),

    'backups_dir' => env('ARK_BACKUPS_DIR', '/ark-server/backups'),

    'logs_dir' => env('ARK_LOGS_DIR', '/ark-server/logs'),

    /*
    |--------------------------------------------------------------------------
    | Server Binary
    |--------------------------------------------------------------------------
    */

    'binary_path' => env('ARK_BINARY_PATH', 'ShooterGame/Binaries/Win64/ArkAscendedServer.exe'),

    'proton_path' => env('ARK_PROTON_PATH', '/opt/glorious_eggroll/proton/bin/wine64'),

    /*
    |--------------------------------------------------------------------------
    | SteamCMD
    |--------------------------------------------------------------------------
    */

    'steam_app_id' => env('ARK_STEAM_APP_ID', '2430930'),

    /*
    |--------------------------------------------------------------------------
    | Default Profile Settings
    |--------------------------------------------------------------------------
    */

    'profile_defaults' => [
        'map' => 'TheIsland_WP',
        'game_port' => 7777,
        'query_port' => 27015,
        'rcon_port' => 27020,
        'session_name' => env('ARK_SESSION_NAME', 'ARK Server'),
        'admin_password' => env('ARK_ADMIN_PASSWORD'),
        'server_password' => env('ARK_SERVER_PASSWORD'),
        'max_players' => 70,
        'battleye_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Manager Daemon Settings
    |--------------------------------------------------------------------------
    */

    'manager' => [
        'status_interval' => (int) env('ARK_MANAGER_STATUS_INTERVAL', 5),
        'subscription_check_interval' => (int) env('ARK_MANAGER_SUBSCRIPTION_CHECK_INTERVAL', 30),
    ],

    'curseforge_url' => env('ARK_CURSEFORGE_URL', 'https://www.curseforge.com/ark-survival-ascended/search?page=1&pageSize=20&sortBy=relevancy'),

    'maps' => [
        'TheIsland_WP' => 'The Island',
        'TheCenter_WP' => 'The Center',
        'ScorchedEarth_WP' => 'Scorched Earth',
        'Ragnarok_WP' => 'Ragnarok',
        'Aberration_WP' => 'Aberration',
        'Extinction_WP' => 'Extinction',
        'Valguero_WP' => 'Valguero',
        'Genesis_WP' => 'Genesis: Part 1',
        'Astraeos_WP' => 'Astraeos',
        'LostColony_WP' => 'Lost Colony',
    ],
];
