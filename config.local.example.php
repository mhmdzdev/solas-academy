<?php
/**
 * Copy this file to `config.local.php` ON THE SERVER and fill in your real values.
 * `config.local.php` is git-ignored — never commit it.
 */
return [
    // --- MySQL (recommended for production). Remove these 2 lines to keep using SQLite. ---
    'db_driver' => 'mysql',
    'mysql'     => [
        'host'    => 'localhost',
        'name'    => 'cpuser_academy',     // cPanel prefixes DB + user names with your account name
        'user'    => 'cpuser_academy',
        'pass'    => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],

    // --- Only if you serve the platform from a sub-folder and auto-detection fails ---
    // 'base_url' => '/academy',

    // --- Recovery: fixed secret keys for the private owner/manager links (32+ random chars) ---
    // 'owner_login_key'   => 'put-a-long-random-string-here',
    // 'manager_login_key' => 'put-another-long-random-string-here',
];
