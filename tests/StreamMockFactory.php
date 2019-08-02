<?php

namespace Anfly0\Middleware\GitHub\tests;

use \Nyholm\Psr7\Factory\Psr17Factory;

class StreamMockFactory
{
    private $psr17Factory;

    public function __construct()
    {
        $this->psr17Factory = new Psr17Factory();
    }

    public function createSeekableStream()
    {
        $mock = $this->psr17Factory->createStream(GithubRequestMockFactory::BODY_DATA);
        return $mock;
    }

    public function createNotSeekableStream()
    {
        $mock = $this->psr17Factory->createStream(GithubRequestMockFactory::BODY_DATA);
        $ref = new \ReflectionClass('Nyholm\Psr7\Stream');
        $setSeekable = $ref->getProperty('seekable');
        $setSeekable->setAccessible(true);

        $setSeekable->setValue($mock, false);
        
        return $mock;
    }
}
