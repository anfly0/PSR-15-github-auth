<?php

namespace Anfly0\Middleware\GitHub\tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class StreamMockFactory extends TestCase
{
    public function createSeekableStream()
    {
        $mock = $this->getMockBuilder(StreamInterface::class)
                     ->setMethods(['__toString', 'isSeekable'])
                     ->getMockForAbstractClass();
        
        $mock->method('__toString')->willReturn(GithubRequestMockFactory::BODY_DATA);
        $mock->method('isSeekable')->willReturn(true);

        return $mock;
    }

    public function createNotSeekableStream()
    {
        $mock = $this->getMockBuilder(StreamInterface::class)
                     ->setMethods(['__toString', 'isSeekable'])
                     ->getMockForAbstractClass();
        
        $mock->method('__toString')->willReturn(GithubRequestMockFactory::BODY_DATA);
        $mock->method('isSeekable')->willReturn(false);

        return $mock;
    }
}
