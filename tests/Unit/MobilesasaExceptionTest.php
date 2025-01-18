<?php

namespace Codexoft\MobilesasaSDK\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Codexoft\MobilesasaSDK\MobilesasaException;

class MobilesasaExceptionTest extends TestCase
{
    public function testExceptionCreation()
    {
        $message = 'Test error message';
        $statusCode = 400;
        $response = ['error' => 'Invalid request'];
        
        $exception = new MobilesasaException($message, $statusCode, $response);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($statusCode, $exception->getStatusCode());
        $this->assertEquals($response, $exception->getResponse());
    }
}