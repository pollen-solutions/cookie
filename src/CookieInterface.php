<?php

declare(strict_types=1);

namespace Pollen\Cookie;

use Pollen\Http\RequestInterface;
use Pollen\Support\Proxy\CookieProxyInterface;
use Pollen\Support\Proxy\HttpRequestProxyInterface;

/**
 * @mixin \Symfony\Component\HttpFoundation\Cookie
 */
interface CookieInterface extends HttpRequestProxyInterface, CookieProxyInterface
{
    /**
     * Check the cookie validity from his value HTTP request.
     *
     * @param RequestInterface|null $request
     * @param mixed|null $value
     *
     * @return bool
     */
    public function checkRequestValue(?RequestInterface $request = null, $value = null): bool;

    /**
     * Clear cookie.
     *
     * @return static
     */
    public function clear(): CookieInterface;

    /**
     * Gets the cookie alias identifier.
     *
     * @return string
     */
    public function getAlias(): string;

    /**
     * Gets the HTTP Request cookie value.
     *
     * @param RequestInterface|null $request
     *
     * @return mixed
     */
    public function httpValue(?RequestInterface $request = null);

    /**
     * Check if value is encrypted.
     *
     * @return bool
     */
    public function isEncrypted(): bool;

    /**
     * Check if cookie is in queue.
     *
     * @return bool
     */
    public function isQueued(): bool;

    /**
     * Sets expiration so that it never expires.
     *
     * @return static
     */
    public function neverExpires(): CookieInterface;

    /**
     * Add cookie in queue.
     *
     * @return static
     */
    public function queue(): CookieInterface;

    /**
     * Remove cookie from queue.
     *
     * @return static
     */
    public function unqueue(): CookieInterface;

    /**
     * Creates a cookie copy with a real new value.
     *
     * @param string|array|null $value
     *
     * @return static
     */
    public function withRealValue($value): CookieInterface;
}