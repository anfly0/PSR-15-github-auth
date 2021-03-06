<?php

namespace Anfly0\Middleware\GitHub\tests;

use \Nyholm\Psr7\Factory\Psr17Factory;
use \Psr\Http\Message\StreamInterface;

class StreamMockFactory
{
    /**
     * @var Psr17Factory
     */
    private $psr17Factory;

    public function __construct()
    {
        $this->psr17Factory = new Psr17Factory();
    }

    public function createSeekableStream(): StreamInterface
    {
        $mock = $this->psr17Factory->createStream(GithubRequestMockFactory::BODY_DATA);
        return $mock;
    }

    public function createNotSeekableStream(): StreamInterface
    {
        $mock = new class () implements StreamInterface {
            
            /**
             * @var StreamInterface
             */
            private $stream;
            
            public function __construct()
            {
                $factory = new Psr17Factory();
                $this->stream = $factory->createStream(GithubRequestMockFactory::BODY_DATA);
                $this->stream->rewind();
            }
            public function __toString()
            {
                return $this->stream->__toString();
            }

            public function close()
            {
                return $this->stream->close();
            }

            public function detach()
            {
                return $this->stream->detach();
            }

            public function getSize()
            {
                return $this->stream->getSize();
            }

            public function tell()
            {
                return $this->stream->tell();
            }

            public function eof()
            {
                return $this->stream->eof();
            }

            public function isSeekable(): bool
            {
                return false;
            }

            public function rewind(): void
            {
                $this->stream->rewind();
            }

            public function isWritable()
            {
                return $this->stream->isWritable();
            }

            public function isReadable()
            {
                return $this->stream->isReadable();
            }

            public function getContents()
            {
                return $this->getContents();
            }

            public function seek($offset, $whence = SEEK_SET): void
            {
                $this->stream->seek($offset, $whence);
            }

            public function write($string)
            {
                return $this->stream->write($string);
            }

            public function read($length)
            {
                return $this->stream->read($length);
            }

            public function getMetadata($key = null)
            {
                return $this->stream->getMetadata($key);
            }
        };
        return $mock;
    }
}
