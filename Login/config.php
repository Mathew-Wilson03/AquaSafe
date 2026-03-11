<?php
session_start();
// Robust environment variable fetcher for Azure
function get_env_var($key, $default) {
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    if (getenv($key) !== false && getenv($key) !== '') return getenv($key);
    
    $azure_key = 'APPSETTING_' . $key;
    if (isset($_SERVER[$azure_key]) && $_SERVER[$azure_key] !== '') return $_SERVER[$azure_key];
    if (getenv($azure_key) !== false && getenv($azure_key) !== '') return getenv($azure_key);
    
    return $default;
}

define('DB_SERVER',   get_env_var('DB_HOST',     'aquasafe-db.mysql.database.azure.com'));
define('DB_USERNAME', get_env_var('DB_USERNAME', 'aquasafeadmin'));
define('DB_PASSWORD', get_env_var('DB_PASSWORD', 'Aquasafe@123'));
define('DB_NAME',     get_env_var('DB_NAME',     'aquasafe'));

/* Attempt to connect to MySQL database */
try {
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
    
    // Azure requires SSL connections by default. We must use mysqli_real_connect to pass the SSL flag.
    $link = mysqli_init();
    
    // Disable SSL verification for simplicity (Azure certificates usually work out of the box, but this prevents local dev errors)
    mysqli_options($link, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false); 
    
    // Connect with the MYSQLI_CLIENT_SSL flag
    mysqli_real_connect(
        $link, 
        DB_SERVER, 
        DB_USERNAME, 
        DB_PASSWORD, 
        DB_NAME, 
        3306, 
        null, 
        MYSQLI_CLIENT_SSL
    );
    
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

// Google OAuth client ID for your web app (set this to your OAuth 2.0 Client ID)
// Example: '12345-abcde.apps.googleusercontent.com'
define('GOOGLE_CLIENT_ID', '420461254572-1s58305detpq2n08ukpgf5sl4c44jb1f.apps.googleusercontent.com');
// Database table name for users. Change to 'user' if your table is named that.
define('DB_TABLE', 'user');

// Super Admin Configuration
define('SUPER_ADMIN_EMAIL', 'mathewwilson2028@mca.ajce.in');
