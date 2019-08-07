<?php

namespace Anfly0\Middleware\GitHub\tests;

use \Psr\Http\Message\ServerRequestInterface;

class GithubRequestMockFactory
{
    const BODY_DATA = '{"array":[1,2,3],"boolean":true,"color":"#82b92c","null":null,"number":123,"object":{"a":"b","c":"d","e":"f"},"string":"Hello World"}';
    const ALG = 'sha1';
    const HEADER_NAME = 'X-Hub-Signature';

    /**
     * @var string
     */
    protected $correctHash;

    /**
     * @var string
     */
    protected $wrongHash = '90502e8291a3e67eb88d722f3590aee599f73a27';
    
    /**
     * @var ServerRequestInterface
     */
    protected $baseMock;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var StreamMockFactory
     */
    protected $streamFactory;

    public function __construct(string $secret)
    {
        $this->correctHash = hash_hmac(self::ALG, self::BODY_DATA, $secret);
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $this->baseMock = $psr17Factory->createServerRequest('POST', 'localhost/');
        $this->secret = $secret;
        $this->streamFactory = new StreamMockFactory();
    }

    public function createBaseRequestMock(): ServerRequestInterface
    {
        return clone $this->baseMock;
    }

    public function createAuthenticRequest(): ServerRequestInterface
    {
        $mock = clone $this->baseMock;
        $mock = $mock->withHeader(self::HEADER_NAME, 'sha1=' . $this->correctHash);
        $mock = $mock->withBody($this->streamFactory->createSeekableStream());
        return $mock;
    }

    public function createAuthenticRequestNotSeekableBody(): ServerRequestInterface
    {
        $mock = clone $this->baseMock;
        $mock = $mock->withHeader(self::HEADER_NAME, 'sha1=' . $this->correctHash);
        $mock = $mock->withBody($this->streamFactory->createNotSeekableStream());
        return $mock;
    }

    public function createUnauthenticRequest(): ServerRequestInterface
    {
        $mock = clone $this->baseMock;
        $mock = $mock->withHeader(self::HEADER_NAME, 'sha1=' . $this->wrongHash);
        $mock = $mock->withBody($this->streamFactory->createSeekableStream());
        return $mock;
    }

    public function createMissingHeaderRequest(): ServerRequestInterface
    {
        $mock = clone $this->baseMock;
        $mock = $mock->withBody($this->streamFactory->createSeekableStream());
        return $mock;
    }
}
