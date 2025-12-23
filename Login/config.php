<?php
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' and no password) */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Your MySQL username
define('DB_PASSWORD', '');     // Your MySQL password
define('DB_NAME', 'aquasafe'); // Name of your database

/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Google OAuth client ID for your web app (set this to your OAuth 2.0 Client ID)
// Example: '12345-abcde.apps.googleusercontent.com'
define('GOOGLE_CLIENT_ID', '420461254572-1s58305detpq2n08ukpgf5sl4c44jb1f.apps.googleusercontent.com');
// Database table name for users. Change to 'user' if your table is named that.
define('DB_TABLE', 'user');
