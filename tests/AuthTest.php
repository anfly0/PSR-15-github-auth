<?php


namespace Anfly0\Middleware\GitHub\tests;

use Anfly0\Middleware\GitHub\Auth;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

class AuthTest extends TestCase
{
    const SECRET = 'THIS_IS_A_SECRET';

    protected $authenticator;
    protected $mockRequestFactory;
    protected $mockHandler;
    protected $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->responseFactory = new ResponseFactory();
        $this->authenticator = new Auth(self::SECRET, $this->responseFactory);
        $this->mockHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockRequestFactory = new GithubRequestMockFactory(self::SECRET);
                                      
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->authenticator);
    }
    
    public function testExtendsPsr15Interface()
    {
        $this->assertInstanceOf(
            MiddlewareInterface::class,
            new Auth(self::SECRET, $this->responseFactory)
        );
    }
    public function testResponseMissingSignature()
    {
        $request = $this->mockRequestFactory->createMissingHeaderRequest();
        $result = $this->authenticator->process($request, $this->mockHandler);

        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals('Bad Request', $result->getReasonPhrase());
    }

    public function testResponseIncorrectSignature()
    {
        $request = $this->mockRequestFactory->createUnauthenticRequest();
        $result = $this->authenticator->process($request, $this->mockHandler);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertEquals('Unauthorized', $result->getReasonPhrase());
    }

    public function test202ResponseFromHandlerCorrectSignature()
    {
        $request = $this->mockRequestFactory->createAuthenticRequest();
       
        $this->mockHandler->method('handle')
            ->willReturn($this->responseFactory->createResponse(202));
        
        $result = $this->authenticator->process($request, $this->mockHandler);

        $this->assertEquals(202, $result->getStatusCode());
        $this->assertEquals('Accepted', $result->getReasonPhrase());
    }

    public function test201ResponseFromHandlerCorrectSignature()
    {
        $request = $this->mockRequestFactory->createAuthenticRequest();

        $this->mockHandler->method('handle')
            ->willReturn($this->responseFactory->createResponse(201));
        
        $result = $this->authenticator->process($request, $this->mockHandler);

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('Created', $result->getReasonPhrase());
    }
}
