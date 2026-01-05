<?php
// firebase_init.php

// 1. Load Composer's autoloader
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Dotenv\Dotenv;

// 2. Load Environment Variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 3. Initialize the Firebase Admin SDK using Environment Variables
try {
    // Map individual env vars back to the array structure the SDK expects
    $serviceAccount = [
        'type'                        => $_ENV['FIREBASE_TYPE'],
        'project_id'                  => $_ENV['FIREBASE_PROJECT_ID'],
        'private_key_id'              => $_ENV['FIREBASE_PRIVATE_KEY_ID'],
        'private_key'                 => str_replace('\n', "\n", $_ENV['FIREBASE_PRIVATE_KEY']),
        'client_email'                => $_ENV['FIREBASE_CLIENT_EMAIL'],
        'client_id'                   => $_ENV['FIREBASE_CLIENT_ID'],
        'auth_uri'                    => $_ENV['FIREBASE_AUTH_URI'],
        'token_uri'                   => $_ENV['FIREBASE_TOKEN_URI'],
        'auth_provider_x509_cert_url' => $_ENV['FIREBASE_AUTH_PROVIDER_CERT_URL'],
        'client_x509_cert_url'        => $_ENV['FIREBASE_CLIENT_CERT_URL'],
        'universe_domain'             => $_ENV['FIREBASE_UNIVERSE_DOMAIN'],
    ];

    $factory = (new Factory())->withServiceAccount($serviceAccount);
    $firebase = $factory->create();
    
    // Get the Authentication client
    $auth = $firebase->getAuth(); 
    
} catch (\Exception $e) {
    die("Firebase Initialization Error: " . $e->getMessage());
}
?>