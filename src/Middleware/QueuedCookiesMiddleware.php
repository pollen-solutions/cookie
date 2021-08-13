<?php

declare(strict_types=1);

namespace Pollen\Cookie\Middleware;

use Pollen\Cookie\CookieJarInterface;
use Pollen\Routing\BaseMiddleware;
use Pollen\Routing\RouterInterface;
use Pollen\Support\Proxy\CookieProxy;
use Psr\Http\Message\ResponseInterface as PsrResponse;

class QueuedCookiesMiddleware extends BaseMiddleware
{
    use CookieProxy;

    /**
     * @param CookieJarInterface|null $cookieJar
     */
    public function __construct(?CookieJarInterface $cookieJar = null)
    {
        if ($cookieJar !== null) {
            $this->setCookieJar($cookieJar);
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeSend(PsrResponse $response, RouterInterface $router): PsrResponse
    {
        if (!headers_sent() && ($cookies = $this->cookie()->fetchQueued())) {
            foreach ($cookies as $cookie) {
                $response = $response->withAddedHeader('Set-Cookie', (string)$cookie);
            }
        }

        return $router->beforeSendResponse($response);
    }
}