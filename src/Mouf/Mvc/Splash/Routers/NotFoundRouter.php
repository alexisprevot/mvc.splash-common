<?php

namespace Mouf\Mvc\Splash\Routers;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Mouf\Mvc\Splash\Controllers\Http404HandlerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Mouf\Mvc\Splash\Services\SplashUtils;

/**
 * This router always returns a 404 page, based on the configured page not found controller.
 *
 * @author Kevin Nguyen
 * @author David Négrier
 */
class NotFoundRouter implements MiddlewareInterface
{
    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var Http404HandlerInterface
     */
    private $pageNotFoundController;

    public function __construct(Http404HandlerInterface $pageNotFoundController, LoggerInterface $log = null)
    {
        $this->pageNotFoundController = $pageNotFoundController;
        $this->log = $log;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     * @param DelegateInterface $delegate
     *
     * @return Response
     */
    public function process(Request $request, DelegateInterface $delegate)
    {
        if ($this->log) {
            $this->log->info('404 - Page not found on URL: '.$request->getUri()->getPath());
        }

        $response = SplashUtils::buildControllerResponse(
            function () use ($request) {
                return $this->pageNotFoundController->pageNotFound($request);
            }
        );

        return $response;
    }
}
