<?php

namespace Codexoft\MobilesasaSDK\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Codexoft\MobilesasaSDK\Mobilesasa;

class ApiIntegrationTest extends TestCase
{
    protected $mobilesasa;

    protected function setUp(): void
    {
        $config = [
            'senderId' => $_ENV['SENDER_ID'],
            'apiKey' => $_ENV['API_KEY'],
            'mobileServeyKey' => $_ENV['SURVEY_KEY'],
            'showBalance' => true,
            'shortCode' => 'TEST_CODE'
        ];
        
        $this->mobilesasa = new Mobilesasa($config);
    }

    public function testAccountBalance()
    {
        $balance = $this->mobilesasa->accountBalance();
        $this->assertIsNumeric($balance);
    }

    public function testPhoneNumberDetails()
    {
        $details = $this->mobilesasa->phoneNumberDetails('0712345678');
        $this->assertArrayHasKey('network', $details);
        $this->assertArrayHasKey('formattedPhone', $details);
    }
}