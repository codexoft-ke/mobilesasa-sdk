<?php

namespace Codexoft\MobilesasaSDK\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Codexoft\MobilesasaSDK\Mobilesasa;
use Codexoft\MobilesasaSDK\MobilesasaException;

class MobilesasaTest extends TestCase
{
    protected $mobilesasa;
    protected $config;

    protected function setUp(): void
    {
        $this->config = [
            'senderId' => $_ENV['SENDER_ID'],
            'apiKey' => $_ENV['API_KEY'],
            'mobileServeyKey' => $_ENV['SURVEY_KEY'],
            'showBalance' => true,
            'shortCode' => 'TEST_CODE'
        ];
        
        $this->mobilesasa = new Mobilesasa($this->config);
    }

    public function testSendSMS()
    {
        $this->expectException(MobilesasaException::class);
        $this->mobilesasa->sendSMS('', 'Test message');
    }

    public function testPhoneNumberFormatting()
    {
        $reflection = new \ReflectionClass($this->mobilesasa);
        $method = $reflection->getMethod('formatPhoneNumber');
        $method->setAccessible(true);

        $this->assertEquals('254712345678', $method->invoke($this->mobilesasa, '0712345678'));
        $this->assertEquals('254712345678', $method->invoke($this->mobilesasa, '712345678'));
        $this->assertEquals('254712345678', $method->invoke($this->mobilesasa, '254712345678'));
    }

    public function testCalculateMessageLength()
    {
        $result = $this->mobilesasa->calculateMessageLength('Hello World');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('length', $result);
        $this->assertArrayHasKey('smsCount', $result);
        $this->assertArrayHasKey('encoding', $result);
        $this->assertEquals(11, $result['length']);
        $this->assertEquals(1, $result['smsCount']);
        $this->assertEquals('GSM-7', $result['encoding']);
    }
}
