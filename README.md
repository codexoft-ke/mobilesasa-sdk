# Mobilesasa SDK Documentation

The Mobilesasa SDK is a PHP library that provides seamless integration with the Mobilesasa API for sending SMS messages, managing contact groups, and conducting mobile surveys. This SDK supports various messaging features including single SMS, bulk SMS, personalized bulk messaging, and delivery status tracking.

## Table of Contents
- [Installation](#installation)
- [Configuration](#configuration)
- [Basic Usage](#basic-usage)
- [Features](#features)
  - [Message Management](#message-management)
  - [Contact Groups](#contact-groups)
  - [Anniversary Groups](#anniversary-groups)
  - [Mobile Surveys](#mobile-surveys)
- [API Reference](#api-reference)
- [Error Handling](#error-handling)

## Installation

To install the Mobilesasa SDK, use Composer:

```bash
composer require codexoft/mobilesasa-sdk
```

## Configuration

Initialize the SDK with your credentials:

```php
use codexoft\MobilesasaSDK\Mobilesasa;

$config = [
    'senderId' => 'YOUR_SENDER_ID',
    'apiKey' => 'YOUR_API_KEY',
    'mobileServeyKey' => 'YOUR_SURVEY_KEY',
    'showBalance' => true, // Optional: Show balance in responses
    'shortCode' => 'YOUR_SHORT_CODE' // Optional: For short code messaging
];

$mobilesasa = new Mobilesasa($config);
```

## Basic Usage

### Sending a Single SMS

```php
$response = $mobilesasa->sendSMS('0712345678', 'Hello World!');
```

### Sending Bulk SMS

```php
$phoneNumbers = ['0712345678', '0723456789'];
$response = $mobilesasa->sendBulkSms($phoneNumbers, 'Hello everyone!');
```

## Features

### Message Management

#### Phone Number Details
Get information about a phone number, including the network provider:

```php
$details = $mobilesasa->phoneNumberDetails('0712345678');
```

#### Calculate Message Length
Analyze message length and SMS parts:

```php
$messageInfo = $mobilesasa->calculateMessageLength('Your message here');
```

#### Check Delivery Status
Track the delivery status of sent messages:

```php
$status = $mobilesasa->smsDeliveryStatus('message_id');
```

#### Personalized Bulk SMS
Send different messages to different recipients:

```php
$messageBody = [
    [
        'phone' => '0712345678',
        'message' => 'Hello John!'
    ],
    [
        'phone' => '0723456789',
        'message' => 'Hello Jane!'
    ]
];
$response = $mobilesasa->sendPersonalizedBulkSms($messageBody);
```

### Contact Groups

#### List Groups
```php
$groups = $mobilesasa->smsGroups();
```

#### Add Contact to Group
```php
$contactDetails = [
    'name' => 'John Doe',
    'phone' => '0712345678',
    'email' => 'john@example.com' // Optional
];
$response = $mobilesasa->addToGroup('GROUP_CODE', $contactDetails);
```

#### Remove Contact from Group
```php
$response = $mobilesasa->deleteFromGroup('GROUP_CODE', '0712345678');
```

### Anniversary Groups

#### List Anniversary Groups
```php
$groups = $mobilesasa->anniversaryGroups();
```

#### Add Contact to Anniversary Group
```php
$contactDetails = [
    'name' => 'John Doe',
    'phone' => '0712345678',
    'date' => '2024-01-01'
];
$response = $mobilesasa->addToAnniversaryGroup('GROUP_CODE', $contactDetails);
```

#### Remove Contact from Anniversary Group
```php
$response = $mobilesasa->deleteFromAnniversaryGroup('GROUP_CODE', '0712345678');
```

### Mobile Surveys

Send mobile surveys to contacts:

```php
$contactDetails = [
    'name' => 'John Doe',
    'phone' => '0712345678'
];

// Send immediately
$response = $mobilesasa->mobileServey('SURVEY_ID', $contactDetails);

// Schedule for later
$response = $mobilesasa->mobileServey(
    'SURVEY_ID',
    $contactDetails,
    false,
    '2024-01-20 10:00:00',
    '2024-01-20 18:00:00'
);
```

## API Reference

### Phone Number Format Support
The SDK automatically handles various phone number formats:
- 9 digits (e.g., '712345678')
- 10 digits with leading zero (e.g., '0712345678')
- 12 digits with country code (e.g., '254712345678')

### Response Format
Most methods return an array containing:
- `status`: Boolean indicating success
- `responseCode`: API response code
- `message`: Response message
- Additional data specific to the operation
- `balance`: Account balance (if showBalance is true in config)

## Error Handling

The SDK uses the `MobilesasaException` class for error handling:

```php
try {
    $response = $mobilesasa->sendSMS('0712345678', 'Hello World!');
} catch (MobilesasaException $e) {
    echo 'Error: ' . $e->getMessage();
    echo 'Status Code: ' . $e->getStatusCode();
    echo 'Response: ' . print_r($e->getResponse(), true);
}
```

Common exceptions include:
- Invalid phone number format
- Missing required parameters
- API authentication errors
- Network connectivity issues

For detailed API documentation and error codes, please visit the [Mobilesasa API Documentation](https://api.mobilesasa.com/docs).
