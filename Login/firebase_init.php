<?php
// firebase_init.php

// 1. Load Composer's autoloader (required for the SDK)
require 'vendor/autoload.php';

use Kreait\Firebase\Factory;

// 2. Define the path to your credentials file
// This assumes 'firebase_credentials.json' is in the same directory.
$serviceAccountPath = __DIR__ . '/firebase_credentials.json';

// 3. Check if the credentials file exists (important for troubleshooting)
if (!file_exists($serviceAccountPath)) {
    die("FATAL ERROR: Firebase credentials file not found at " . $serviceAccountPath);
}

// 4. Initialize the Firebase Admin SDK
try {
    $factory = (new Factory())->withServiceAccount($serviceAccountPath);
    $firebase = $factory->create();
    
    // Get the Authentication client, which we will use in the next file
    $auth = $firebase->getAuth(); 
    
} catch (\Exception $e) {
    die("Firebase Initialization Error: " . $e->getMessage());
}
?>