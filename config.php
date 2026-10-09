<?php
/**
 * Default (public) configuration — safe to keep in Git.
 *
 * Do NOT put passwords or secrets here. On the server, create `config.local.php`
 * (see config.local.example.php); its values override the ones below and it is
 * ignored by Git, so deployments never overwrite it.
 */
return [
    'site_name'   => 'Psychiatry Academy',
    'timezone'    => 'Africa/Cairo',

    // Leave empty to auto-detect (works in a sub-folder or sub-domain). Example: '/academy'
    'base_url'    => '',

    'db_driver'   => 'sqlite',            // 'sqlite' | 'mysql'
    'sqlite_path' => __DIR__ . '/data/platform.sqlite',
    'mysql'       => [
        'host'    => 'localhost',
        'name'    => '',
        'user'    => '',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // Optional: pin the private staff login keys here instead of the database
    // (useful as a recovery option if a link is lost). Leave unset normally.
    // 'owner_login_key'   => '',
    // 'manager_login_key' => '',
];
