<?php

declare(strict_types=1);

namespace Pollen\Cookie;

use DateTimeInterface;
use Pollen\Support\Concerns\ConfigBagAwareTraitInterface;
use Pollen\Support\Proxy\ContainerProxyInterface;
use Symfony\Component\HttpFoundation\Cookie as BaseCookie;

interface CookieJarInterface extends ContainerProxyInterface, ConfigBagAwareTraitInterface
{
    /**
     * Add a cookie instance provides by cookie jar.
     *
     * @param CookieInterface $cookie
     *
     * @return static
     */
    public function add(CookieInterface $cookie): CookieJarInterface;

    /**
     * Returns list of all cookie instances.
     *
     * @return array<string, CookieInterface|BaseCookie>|array
     */
    public function all(): array;

    /**
     * Returns list of all cookie instances in queue.
     *
     * @return  array<string, CookieInterface|BaseCookie>|array
     */
    public function fetchQueued(): array;

    /**
     * Returns a registered cookie instance by its alias identifier.
     *
     * @param string $alias
     *
     * @return CookieInterface|BaseCookie|null
     */
    public function get(string $alias): ?CookieInterface;

    /**
     * Gets an availability based on lifetime.
     *
     * @param int|string|DateTimeInterface|null
     *
     * @return int
     */
    public function getAvailability($lifetime = null): int;

    /**
     * Gets the default cookie parameters.
     *
     * @param string|null $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool|null $httpOnly
     * @param bool|null $raw
     * @param string|null $sameSite
     *
     * @return array
     */
    public function getDefaults(
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?bool $raw = null,
        ?string $sameSite = null
    ): array;

    /**
     * Gets the salt suffix of cookie name.
     *
     * @return string|null
     */
    public function getSalt(): ?string;

    /**
     * Makes a cookie instance from its alias identifier and parameters.
     *
     * @param string $alias
     * @param array $params
     *
     * @return CookieInterface|BaseCookie
     */
    public function make(string $alias, array $params = []): CookieInterface;

    /**
     * Sets the default cookie parameters.
     *
     * @param string|null $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool|null $httpOnly
     * @param bool|null $raw
     * @param string|null $sameSite
     *
     * @return static
     */
    public function setDefaults(
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?bool $raw = null,
        ?string $sameSite = null
    ): CookieJarInterface;

    /**
     * Set the default cookie lifetime.
     *
     * @param int|string|DateTimeInterface
     *
     * @return static
     */
    public function setLifetime($lifetime): CookieJarInterface;

    /**
     * Set the default cookie salt suffix of name.
     *
     * @param string $salt
     *
     * @return static
     */
    public function setSalt(string $salt): CookieJarInterface;
}
