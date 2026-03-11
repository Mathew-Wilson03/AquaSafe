<?php
// Ensure session starts without warning
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/**
 * Robust environment variable fetcher
 * Prioritizes Railway, then Azure, then defaults
 */
function get_env_var($key, $defaultValue = '') {
    // 1. Check direct key (e.g. DB_HOST) in all common sources
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (getenv($key) !== false && getenv($key) !== '') return getenv($key);
    
    // 2. Railway Mapping (If the user hasn't manually set DB_HOST, use MYSQLHOST)
    $railway_map = [
        'DB_HOST'     => 'MYSQLHOST',
        'DB_USERNAME' => 'MYSQLUSER',
        'DB_PASSWORD' => 'MYSQLPASSWORD',
        'DB_NAME'     => 'MYSQLDATABASE',
        'DB_PORT'     => 'MYSQLPORT'
    ];
    
    if (isset($railway_map[$key])) {
        $rk = $railway_map[$key];
        if (isset($_SERVER[$rk]) && $_SERVER[$rk] !== '') return $_SERVER[$rk];
        if (isset($_ENV[$rk]) && $_ENV[$rk] !== '') return $_ENV[$rk];
        if (getenv($rk) !== false && getenv($rk) !== '') return getenv($rk);
    }

    // 3. Azure Prefix Mapping
    $azure_key = 'APPSETTING_' . $key;
    if (isset($_SERVER[$azure_key]) && $_SERVER[$azure_key] !== '') return $_SERVER[$azure_key];
    if (getenv($azure_key) !== false && getenv($azure_key) !== '') return getenv($azure_key);
    
    return $defaultValue;
}

// Database configuration
define('DB_SERVER',   get_env_var('DB_HOST',     'localhost'));
define('DB_USERNAME', get_env_var('DB_USERNAME', 'root'));
define('DB_PASSWORD', get_env_var('DB_PASSWORD', ''));
define('DB_NAME',     get_env_var('DB_NAME',     'aquasafe'));
define('DB_PORT',     get_env_var('DB_PORT',     '3306'));

/* Attempt to connect to MySQL database */
try {
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
    
    $link = mysqli_init();
    
    // Set a short timeout (5 seconds)
    mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    
    // Connect to database
    // We only use SSL if we are clearly on Azure (host contains .azure.com)
    $is_azure = strpos(DB_SERVER, '.azure.com') !== false;
    
    if ($is_azure) {
        mysqli_options($link, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        mysqli_real_connect($link, DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT, null, MYSQLI_CLIENT_SSL);
    } else {
        mysqli_real_connect($link, DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
    }
    
} catch (mysqli_sql_exception $e) {
    die("<div style='font-family:sans-serif; padding: 30px; background: #ffebee; border: 1px solid #ef5350; border-radius: 8px; margin: 20px;'>
             <h3 style='color: #c62828;'>Database Connection Error</h3>
             <p>The application could not connect to the database. If you recently deployed to Azure, you must configure your MySQL Database connection strings.</p>
             <p><strong>Required Azure Application Settings:</strong> <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code></p>
             <br>
             <p style='color:#c62828; font-size: 14px;'><strong>Attempted Host:</strong> " . htmlspecialchars(DB_SERVER) . "</p>
             <p style='color:#c62828; font-size: 14px;'><strong>Technical Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
         </div>");
}

// Check connection (fallback for generic errors)
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Google OAuth client ID for your web app
define('GOOGLE_CLIENT_ID', get_env_var('GOOGLE_CLIENT_ID', '420461254572-1s58305detpq2n08ukpgf5sl4c44jb1f.apps.googleusercontent.com'));
// Database table name for users. Change to 'user' if your table is named that.
define('DB_TABLE', 'user');

// Super Admin Configuration
define('SUPER_ADMIN_EMAIL', 'mathewwilson2028@mca.ajce.in');
