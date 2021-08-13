<?php

declare(strict_types=1);

namespace Pollen\Cookie;

use Pollen\Container\ServiceProvider;
use Pollen\Cookie\Middleware\QueuedCookiesMiddleware;

class CookieServiceProvider extends ServiceProvider
{
    /**
     * @var string[]
     */
    protected $provides = [
        CookieJarInterface::class,
        'routing.middleware.queued-cookies'
    ];

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->getContainer()->share(
            CookieJarInterface::class,
            function () {
                return (new CookieJar(
                    [
                        'value'    => null,
                        'expire'   => 3600,
                        'path'     => null,
                        'domain'   => null,
                        'secure'   => null,
                        'httpOnly' => true,
                        'raw'      => false,
                        'sameSite' => null,
                    ], $this->getContainer()
                ));
            }
        );

        $this->registerMiddlewares();
    }

    /**
     * Register middleware services.
     *
     * @return void
     */
    public function registerMiddlewares(): void
    {
        $this->getContainer()->add('routing.middleware.queued-cookies', function () {
            return new QueuedCookiesMiddleware($this->getContainer()->get(CookieJarInterface::class));
        });
    }
}
