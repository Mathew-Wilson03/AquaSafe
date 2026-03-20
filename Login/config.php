<?php
// CRITICAL: TEMPORARY ERROR REPORTING FOR DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure session starts without warning
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// 2. Robust environment variable fetcher
function get_env_var($key, $defaultValue = '') {
    // 1. Railway Internal Mapping (Highest Priority for DB performance on Railway)
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

    // 2. Check direct key (e.g. DB_HOST from .env)
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (getenv($key) !== false && getenv($key) !== '') return getenv($key);
    
    // 3. Azure Prefix Mapping
    $azure_key = 'APPSETTING_' . $key;
    if (isset($_SERVER[$azure_key]) && $_SERVER[$azure_key] !== '') return $_SERVER[$azure_key];
    if (getenv($azure_key) !== false && getenv($azure_key) !== '') return getenv($azure_key);
    
    return $defaultValue;
}

// 3. Load Environment Variables (Lazy Loading Dotenv if available)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // We only load Dotenv for .env files if not already in environment
    if (!isset($_SERVER['DB_HOST']) && !getenv('DB_HOST')) {
        require_once __DIR__ . '/vendor/autoload.php';
        if (class_exists('Dotenv\Dotenv')) {
            try {
                $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
                $dotenv->safeLoad();
            } catch (\Exception $e) {}
        }
    }
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

    // Timeout: 2s (EMERGENCY REDUCTION) to release workers faster during DB latency
    mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 2);
    // Return integers/floats as native PHP types
    mysqli_options($link, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

    // Robust environment detection
    $is_azure   = (getenv('WEBSITE_SITE_NAME') !== false);
    $is_railway = (getenv('RAILWAY_ENVIRONMENT') !== false || getenv('RAILWAY_PROJECT_ID') !== false);

    // DISABLE PERSISTENT CONNECTIONS ('p:') 
    // This resolves the "still loading" hang issue caused by connection pool exhaustion/locks
    $host_prefix = ''; 
    $connect_host = $host_prefix . DB_SERVER;

    if ($is_azure || $is_railway || (int)DB_PORT === 37624) {
        mysqli_options($link, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        mysqli_real_connect($link, $connect_host, DB_USERNAME, DB_PASSWORD, DB_NAME, (int)DB_PORT, null, MYSQLI_CLIENT_SSL);
    } else {
        mysqli_real_connect($link, $connect_host, DB_USERNAME, DB_PASSWORD, DB_NAME, (int)DB_PORT);
    }

    // Force MySQL session to UTC to match PHP
    mysqli_query($link, "SET time_zone = '+00:00'");

} catch (mysqli_sql_exception $e) {
    die("<div style='font-family:sans-serif; padding: 30px; background: #ffebee; border: 1px solid #ef5350; border-radius: 8px; margin: 20px;'>
             <h3 style='color: #c62828;'>Database Connection Error</h3>
             <p>The application could not connect to the database. Configure your MySQL connection strings in Railway environment variables.</p>
             <p><strong>Required Variables:</strong> <code>DB_HOST</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>, <code>DB_NAME</code>, <code>DB_PORT</code></p>
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

// SMTP configuration (Uses get_env_var)
if (!defined('SMTP_HOST'))       define('SMTP_HOST',       get_env_var('SMTP_HOST',       'smtp.gmail.com'));
if (!defined('SMTP_PORT'))       define('SMTP_PORT',       get_env_var('SMTP_PORT',       '587'));
if (!defined('SMTP_USER'))       define('SMTP_USER',       get_env_var('SMTP_USER',       ''));
if (!defined('SMTP_PASS'))       define('SMTP_PASS',       get_env_var('SMTP_PASS',       ''));
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', get_env_var('SMTP_FROM_EMAIL', ''));
if (!defined('SMTP_FROM_NAME'))  define('SMTP_FROM_NAME',  get_env_var('SMTP_FROM_NAME',  'AquaSafe Alerts'));

// New Mail Parameters
if (!defined('MAIL_DRIVER'))     define('MAIL_DRIVER',     get_env_var('MAIL_DRIVER',     'smtp'));
if (!defined('MAIL_TIMEOUT'))    define('MAIL_TIMEOUT',    (int)get_env_var('MAIL_TIMEOUT', 5));
if (!defined('MAIL_API_KEY'))    define('MAIL_API_KEY',    get_env_var('MAIL_API_KEY',    ''));
if (!defined('MAIL_API_URL'))    define('MAIL_API_URL',    get_env_var('MAIL_API_URL',    ''));

// Guard: only attempt email if explicitly enabled and configured.
$mail_enabled_env = get_env_var('MAIL_ENABLED', '');
if (!defined('ENABLE_EMAIL')) {
    if ($mail_enabled_env === 'true' || $mail_enabled_env === '1') {
        define('ENABLE_EMAIL', true);
    } elseif ($mail_enabled_env === 'false' || $mail_enabled_env === '0') {
        define('ENABLE_EMAIL', false);
    } else {
        // Default logic: only enable if SMTP_USER or MAIL_API_KEY is set
        define('ENABLE_EMAIL', (SMTP_USER !== '' || MAIL_API_KEY !== ''));
    }
}
