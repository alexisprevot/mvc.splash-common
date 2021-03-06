<?php

namespace Mouf\Mvc\Splash\Routers;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mouf\Utils\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
use Mouf\Utils\Common\ConditionInterface\ConditionInterface;

class CacheRouter implements MiddlewareInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var ConditionInterface
     */
    private $cacheCondition;

    /**
     * @var LoggerInterface
     */
    private $log;

    public function __construct(CacheInterface $cache, LoggerInterface $log, ConditionInterface $cacheCondition)
    {
        $this->cache = $cache;
        $this->cacheCondition = $cacheCondition;
        $this->log = $log;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $requestMethod = $request->getMethod();
        $key = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '_', $request->getUri()->getPath().'?'.$request->getUri()->getQuery());

        if ($this->cacheCondition->isOk() && $requestMethod == 'GET') {
            $cacheResponse = $this->cache->get($key);
            if ($cacheResponse) {
                $this->log->debug("Cache HIT on $key");

                return $cacheResponse;
            } else {
                $this->log->debug("Cache MISS on key $key");
                $response = $delegate->process($request);

                $noCache = false;
                if ($response->hasHeader('Mouf-Cache-Control') && $response->getHeader('Mouf-Cache-Control')[0] == 'no-cache') {
                    $noCache = true;
                }

                if ($noCache) {
                    $this->log->debug("Mouf NO CACHE header found, not storing '$key'");
                } else {
                    $ttl = null;

                    // TODO: continue here!
                    // Use PSR-7 response to analyze maxage and expires...
                    // ...or... use a completely different HTTP cache implementation!!!
                    // There must be one around for PSR-7!

                    $maxAge = $response->getMaxAge();
                    $expires = $response->getExpires();
                    if ($maxAge) {
                        $this->log->debug("MaxAge specified : $maxAge");
                        $ttl = $maxAge;
                    } elseif ($expires) {
                        $this->log->debug("Expires specified : $expires");
                        $ttl = date_diff($expires, new \DateTime())->s;
                    }

                    if ($ttl) {
                        $this->log->debug("TTL is  : $ttl");
                    }

                    // Make sure the response is serializable
                    $serializableResponse = new Response();
                    $serializableResponse->headers = $response->headers;

                    ob_start();
                    $response->sendContent();
                    $content = ob_get_clean();

                    $serializableResponse->setContent($content);

                    $this->cache->set($key, $serializableResponse, $ttl);
                    $this->log->debug("Cache STORED on key $key");
                    $response = $serializableResponse;
                }

                return $response;
            }
        } else {
            $this->log->debug("No cache for $key");

            return $this->fallBackRouter->handle($request, $type, $catch);
        }
    }
}
