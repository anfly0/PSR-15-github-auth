<?php


namespace Anfly0\Middleware\GitHub\tests;

use Anfly0\Middleware\GitHub\Auth;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;


/**
 * Undocumented class
 */
class GithubWebHookAuthTest extends TestCase
{
    protected $authenticator;
    protected $mockRequest;
    protected $mockHandler;
    protected $secret;
    protected $messageBody;
    protected $responseFactory;
    protected $signature;

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->secret = 'THIS_IS_A_TEST';
        $this->messageBody = 'THIS_IS_A_TEST_BODY';
        $this->signature = 'sha1=90502e8291a3e67eb88d722f3590aee599f73a27';
        $this->responseFactory = new ResponseFactory();
        $this->authenticator = new Auth($this->secret, $this->responseFactory);
        $this->mockRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockHandler = $this->createMock(RequestHandlerInterface::class);
        $this->responseFactory = new ResponseFactory();
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->authenticator);
        unset($this->mockRequest);
    }
    

    /** @test */
    public function testExtendsPsr15Interface()
    {
        $this->assertInstanceOf(
            MiddlewareInterface::class,
            new Auth($this->secret, $this->responseFactory)
        );
    }
    
    /** @test */
    public function testResponseMissingSignature()
    {
        $this->mockRequest->method('hasHeader')
            ->with('X-Hub-Signature')
            ->willReturn(false);

        $this->mockRequest->method('getHeader')
            ->with('X-Hub-Signature')
            ->willReturn(array());
        
        $result = $this->authenticator->process($this->mockRequest, $this->mockHandler);

        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals('Bad Request', $result->getReasonPhrase());
    }

    public function testResponseIncorrectSignature()
    {
        $this->mockRequest->method('hasHeader')
            ->with('X-Hub-Signature')
            ->willReturn(true);

        $this->mockRequest->method('getHeader')
            ->with('X-Hub-Signature')
            ->willReturn(array('sha1=90502e8291a3e67eb88d722f3590aee599f73a28'));
        
        $this->mockRequest->method('getBody')
            ->willReturn($this->messageBody);
        
        $result = $this->authenticator->process($this->mockRequest, $this->mockHandler);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertEquals('Unauthorized', $result->getReasonPhrase());
    }

    public function test202ResponseFromHandlerCorrectSignature()
    {
        $this->mockRequest->method('hasHeader')
            ->with('X-Hub-Signature')
            ->willReturn(true);

        $this->mockRequest->method('getHeader')
            ->with('X-Hub-Signature')
            ->willReturn(array($this->signature));

        $this->mockRequest->method('getBody')
            ->willReturn($this->messageBody);

        $this->mockHandler->method('handle')
            ->willReturn($this->responseFactory->createResponse(202));
        
        $result = $this->authenticator->process($this->mockRequest, $this->mockHandler);

        $this->assertEquals(202, $result->getStatusCode());
        $this->assertEquals('Accepted', $result->getReasonPhrase());
    }

    public function test201ResponseFromHandlerCorrectSignature()
    {
        $this->mockRequest->method('hasHeader')
            ->with('X-Hub-Signature')
            ->willReturn(true);

        $this->mockRequest->method('getHeader')
            ->with('X-Hub-Signature')
            ->willReturn(array($this->signature));

        $this->mockRequest->method('getBody')
            ->willReturn($this->messageBody);

        $this->mockHandler->method('handle')
            ->willReturn($this->responseFactory->createResponse(201));
        
        $result = $this->authenticator->process($this->mockRequest, $this->mockHandler);

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('Created', $result->getReasonPhrase());
    }
}
