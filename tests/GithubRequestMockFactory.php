<?php

namespace Anfly0\Middleware\GitHub\tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class GithubRequestMockFactory extends TestCase
{
    const BODY_DATA = '{"array":[1,2,3],"boolean":true,"color":"#82b92c","null":null,"number":123,"object":{"a":"b","c":"d","e":"f"},"string":"Hello World"}';
    const ALG = 'sha1';
    const HEADER_NAME = 'X-Hub-Signature';

    protected $correctHash;
    protected $wrongHash = '90502e8291a3e67eb88d722f3590aee599f73a27';
    protected $baseMock;
    protected $secret;

    public function __construct(string $secret)
    {
        $this->correctHash = hash_hmac(self::ALG, self::BODY_DATA, $secret);
        $this->baseMock = $this->getMockBuilder(ServerRequestInterface::class)
                               ->setMethods(['hasHeader', 'getHeader', 'getBody'])
                               ->getMockForAbstractClass();

        $this->secret = $secret;
    }

    public function createBaseRequestMock()
    {
        return clone $this->baseMock;
    }

    public function createAuthenticRequest()
    {
        $mock = clone $this->baseMock;

        $mock->method('hasHeader')
            ->with(self::HEADER_NAME)
            ->willReturn(true);

        $mock->method('getHeader')
            ->with(self::HEADER_NAME)
            ->willReturn(array('sha1=' . $this->correctHash));

        $mock->method('getBody')
            ->willReturn(self::BODY_DATA);

        return $mock;
    }

    public function createUnauthenticRequest()
    {
        $mock = clone $this->baseMock;

        $mock->method('hasHeader')
            ->with(self::HEADER_NAME)
            ->willReturn(true);

        $mock->method('getHeader')
            ->with(self::HEADER_NAME)
            ->willReturn(array('sha1=' . $this->wrongHash));

        $mock->method('getBody')
            ->willReturn(self::BODY_DATA);

        return $mock;
    }

    public function createMissingHeaderRequest()
    {
        $mock = clone $this->baseMock;

        $mock->method('hasHeader')
            ->with(self::HEADER_NAME)
            ->willReturn(false);

        $mock->method('getHeader')
            ->with(self::HEADER_NAME)
            ->willReturn(array());

        $mock->method('getBody')
            ->willReturn(self::BODY_DATA);

        return $mock;
    }
}
