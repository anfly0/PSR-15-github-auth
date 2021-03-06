<?php


namespace Anfly0\Middleware\GitHub\tests;

use Anfly0\Middleware\GitHub\Auth;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use \Nyholm\Psr7\Factory\Psr17Factory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class AuthTest extends TestCase
{
    const SECRET = 'THIS_IS_A_SECRET';
    const LOGGER_CHANNEL_NAME = 'Unit-test-logger';

    /**
     * @var Auth
     */
    protected $authenticator;

    /**
     * @var GithubRequestMockFactory
     */
    protected $mockRequestFactory;

    /**
     * @var RequestHandlerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHandler;

    /**
     * @var \Psr\Http\Message\ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    protected $streamFactory;

    /**
     * @var Resource
     */
    protected $logFile;

    /**
     * @var Logger
     */
    protected $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->responseFactory = new Psr17Factory();
        $this->streamFactory = $this->responseFactory;
        $this->authenticator = new Auth(self::SECRET, $this->responseFactory, $this->streamFactory);
        $this->logFile = fopen('php://memory', 'rw');
        $this->logger = new Logger(self::LOGGER_CHANNEL_NAME);

        $this->mockHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockRequestFactory = new GithubRequestMockFactory(self::SECRET);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        fclose($this->logFile);
    }

    /**
     * @return ServerRequestInterface[][]
     */
    public static function serverRequestProvider(): array
    {
        $mockRequestFactory = new GithubRequestMockFactory(self::SECRET);
        return [
            [$mockRequestFactory->createAuthenticRequest()],
            [$mockRequestFactory->createAuthenticRequestNotSeekableBody()]
        ];
    }

    public function testExtendsPsr15Interface(): void
    {
        $this->assertInstanceOf(
            MiddlewareInterface::class,
            new Auth(self::SECRET, $this->responseFactory, $this->streamFactory)
        );
    }
    public function testResponseMissingSignature(): void
    {
        $request = $this->mockRequestFactory->createMissingHeaderRequest();
        $result = $this->authenticator->process($request, $this->mockHandler);

        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals('Bad Request', $result->getReasonPhrase());
    }

    public function testResponseIncorrectSignature(): void
    {
        $request = $this->mockRequestFactory->createUnauthenticRequest();
        $result = $this->authenticator->process($request, $this->mockHandler);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertEquals('Unauthorized', $result->getReasonPhrase());
    }

    public function test202ResponseFromHandlerCorrectSignature(): void
    {
        $request = $this->mockRequestFactory->createAuthenticRequest();

        $this->mockHandler->method('handle')
            ->willReturn($this->responseFactory->createResponse(202, 'Accepted'));

        $result = $this->authenticator->process($request, $this->mockHandler);

        $this->assertEquals(202, $result->getStatusCode());
        $this->assertEquals('Accepted', $result->getReasonPhrase());
    }

    public function test201ResponseFromHandlerCorrectSignature(): void
    {
        $request = $this->mockRequestFactory->createAuthenticRequest();

        $this->mockHandler->method('handle')
            ->willReturn($this->responseFactory->createResponse(201, 'Created'));

        $result = $this->authenticator->process($request, $this->mockHandler);

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('Created', $result->getReasonPhrase());
    }

    public function testLoggingOfMissingHeader(): void
    {
        $this->logger->pushHandler(new StreamHandler($this->logFile, Logger::DEBUG));
        $this->authenticator->setLogger($this->logger);

        $request = $this->mockRequestFactory->createMissingHeaderRequest();
        $this->authenticator->process($request, $this->mockHandler);

        rewind($this->logFile);
        $logLine = stream_get_line($this->logFile, 4096);
        $expected = self::LOGGER_CHANNEL_NAME . '.WARNING: ' . Auth::LOG_MSG_MISSING_HEADER;
        $this->assertStringContainsString($expected, $logLine);
    }

    public function testLoggingOfFailedAuthentication(): void
    {
        $this->logger->pushHandler(new StreamHandler($this->logFile, Logger::DEBUG));
        $this->authenticator->setLogger($this->logger);

        $request = $this->mockRequestFactory->createUnauthenticRequest();
        $this->authenticator->process($request, $this->mockHandler);

        rewind($this->logFile);
        $logLine = stream_get_line($this->logFile, 4096);
        $expected = self::LOGGER_CHANNEL_NAME . '.WARNING: ' . Auth::LOG_MSG_SIGNATURE_NOT_MATCHING;
        $this->assertStringContainsString($expected, $logLine);
    }

    public function testLoggingOfSuccessfulAuthentication(): void
    {
        $this->logger->pushHandler(new StreamHandler($this->logFile, Logger::DEBUG));
        $this->authenticator->setLogger($this->logger);

        $request = $this->mockRequestFactory->createAuthenticRequest();
        $this->authenticator->process($request, $this->mockHandler);

        rewind($this->logFile);
        $logLine = stream_get_line($this->logFile, 4096);
        $expected = self::LOGGER_CHANNEL_NAME . '.INFO: ' . Auth::LOG_MSG_SUCCESS;
        $this->assertStringContainsString($expected, $logLine);
    }

    /**
     * @dataProvider serverRequestProvider
     */
    public function testRequestBodyIsPassedToHandlerIsSeekable(ServerRequestInterface $request): void
    {
        $this->mockHandler->expects($this->atLeastOnce())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $handlerRequest) {
                return $handlerRequest->getBody()->isSeekable();
            }));

        $this->authenticator->process($request, $this->mockHandler);
    }

    /**
     * @dataProvider serverRequestProvider
     */
    public function testRequestBodyIsNotAltered(ServerRequestInterface $request): void
    {
        $this->mockHandler->expects($this->atLeastOnce())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $handlerRequest) use ($request) {
                return (string) $handlerRequest->getBody() == (string) $request->getBody();
            }));

        $this->authenticator->process($request, $this->mockHandler);
    }

    /**
     * @dataProvider serverRequestProvider
     */
    public function testRequestBodyIsRewound(ServerRequestInterface $request): void
    {
        $this->mockHandler->expects($this->atLeastOnce())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $handlerRequest) {
                return $handlerRequest->getBody()->tell() === 0;
            }));

        $this->authenticator->process($request, $this->mockHandler);
    }
}
