<?php
/**
 * @package   Anfly0\Middleware\GitHub
 * @author    Viktor Hellström <anfly0@gmail.com>
 * @copyright 2019 Viktor Hellström
 * @license   MIT - http://www.opensource.org/licenses/mit-license.php
 */
namespace Anfly0\Middleware\GitHub;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Auth is a PSR-15 middleware that is intended to authenticate incoming webhook from github.
 *
 * Auth is a PSR-15 middleware that is used to authenticate incoming webhook from github.
 * Being an implementation of the PSR-15 MiddlewareInterface interface this middleware should
 * usable with any framework that have PSR-15 support.
 * In addition to PSR-15, auth also implements the PSR-3 LoggerAwareInterface to facilitate logging.
 * If no logger is set through the setLogger method a PSR-3 NullLogger will be used and no logs will be
 *  persisted.
 */
class Auth implements MiddlewareInterface, LoggerAwareInterface
{
    /**
     * Trait that implements the methods required to implement the LoggerAwareInterface
     */
    use LoggerAwareTrait;

    private const SIGNATURE_NAME = 'X-Hub-Signature';

    /**
     * @var ResponseFactoryInterface Factory object that is used to create response objects when needed.
     */
    private $responseFactory;

    /**
     * @var string The secret used to calculate the expected signature.
     */
    private $secret;
    
    public function __construct(string $secret, ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->secret = $secret;
        $this->logger = null;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }

        if (!$request->hasHeader(self::SIGNATURE_NAME)) {
            $this->logger->warning("Tried to authenticate github webhook, but the signature header was missing.");
            return $this->responseFactory->createResponse(400);
        }

        $signature = $this->getSignature($this->secret, $request->getBody());
        
        if (!hash_equals($request->getHeader(self::SIGNATURE_NAME)[0], $signature)) {
            $this->logger->warning("Tried to authenticate github webhook, but the signature did not match.");
            return $this->responseFactory->createResponse(401);
        }

        $this->logger->info("github webhook authenticated OK");
        return $handler->handle($request);
    }

    private function getSignature(string $secret, string $body): string
    {
        return 'sha1=' . hash_hmac('sha1', $body, $secret);
    }
}
