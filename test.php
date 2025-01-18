<?php

require_once __DIR__ . '/vendor/autoload.php';

use  codexoft\MobilesasaSDK\Mobilesasa;
use  codexoft\MobilesasaSDK\MobilesasaException;
try {
    $config = [
        'apiKey' => 'VH7t2xcurvP2DIgD4uPzY7oSuGn9nuohiKYwqEE1ZFT4izTMxDGyLhn6pfEj',
        'mobileServeyKey' => 'VH7t2xcurvP2DIgD4uPzY7oSuGn9nuohiKYwqEE1ZFT4izTMxDGyLhn6pfEj',
        'senderId' => 'MOBILESASA',
        'shortCode' => 'YOUR_SHORT_CODE',
        'showBalance' => true
    ];

    $mobilesasa = new Mobilesasa($config);

    // Send a single SMS
    $result = $mobilesasa->sendSMS('254795375735', 'Hello World!');
    echo json_encode($result);

} catch (MobilesasaException $e) {
    // Handle API-specific errors
    echo "API Error: " . $e->getMessage();
    if ($e->getResponse()) {
        print_r($e->getResponse());
    }
} catch (InvalidArgumentException $e) {
    // Handle validation errors
    echo "Validation Error: " . $e->getMessage();
} catch (Exception $e) {
    // Handle other errors
    echo "Error: " . $e->getMessage();
}