<?php

declare(strict_types=1);

namespace Pollen\Cookie;

use DateTimeInterface;
use InvalidArgumentException;
use Pollen\Support\Concerns\ConfigBagAwareTrait;
use Pollen\Support\Exception\ManagerRuntimeException;
use Pollen\Support\Proxy\ContainerProxy;
use Psr\Container\ContainerInterface as Container;
use RuntimeException;

class CookieJar implements CookieJarInterface
{
    use ConfigBagAwareTrait;
    use ContainerProxy;

    /**
     * Cookie Jar main instance.
     * @var CookieJarInterface|null
     */
    private static ?CookieJarInterface $instance = null;

    /**
     * List of registered cookie instances.
     * @var array<string, CookieInterface>|array
     */
    protected $cookies = [];

    /**
     * Lifetime for default cookies.
     * @var int|string|DateTimeInterface
     */
    protected $lifetime = 0;

    /**
     * Prefix of value for default cookie.
     * @var string|null
     */
    protected ?string $prefix = null;

    /**
     * Salt suffix of name for default cookies.
     * @var string|null
     */
    protected ?string $salt = null;

    /**
     * Domain name for default cookies.
     * @var string|null
     */
    public ?string $domain = null;

    /**
     * Flag HTTP Only for default cookies.
     * @var bool
     */
    public bool $httpOnly = true;

    /**
     * Path for default cookies.
     * @var string|null
     */
    public ?string $path = null;

    /**
     * Flag of raw url encode for default cookies.
     * @var bool
     */
    public bool $raw = false;

    /**
     * Directive of availability for cross-site requests.
     * @see https://developer.mozilla.org/fr/docs/Web/HTTP/Headers/Set-Cookie
     * @var string|null strict|lax|null
     */
    public ?string $sameSite = null;

    /**
     * Flag of sending cookie only over HTTPS for default cookies.
     * {@internal auto if null.}
     * @var bool|null
     */
    public ?bool $secure = null;

    /**
     * @param array $config
     * @param Container|null $container
     *
     * @return void
     */
    public function __construct(array $config = [], ?Container $container = null)
    {
        $this->setConfig($config);

        if ($container !== null) {
            $this->setContainer($container);
        }

        if (!self::$instance instanceof static) {
            self::$instance = $this;
        }
    }

    /**
     * Retrieve cookie jar main instance.
     *
     * @return static
     */
    public static function getInstance(): CookieJarInterface
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        throw new ManagerRuntimeException(sprintf('Unavailable [%s] instance', __CLASS__));
    }

    /**
     * @inheritDoc
     */
    public function add(CookieInterface $cookie): CookieJarInterface
    {
        $this->cookies[$cookie->getAlias()] = $cookie;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->cookies;
    }

    /**
     * @inheritDoc
     */
    public function fetchQueued(): array
    {
        $queued = [];
        foreach ($this->cookies as $cookie) {
            if ($cookie->isQueued()) {
                $queued[] = $cookie;
                $cookie->unqueue();
            }
        }
        return $queued;
    }

    /**
     * @inheritDoc
     */
    public function get(string $alias): ?CookieInterface
    {
        return $this->cookies[$alias] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getAvailability($lifetime = null): int
    {
        if ($lifetime === null) {
            $lifetime = $this->lifetime;
        }

        if (!is_int($lifetime) && !is_string($lifetime) && !$lifetime instanceof DateTimeInterface) {
            throw new RuntimeException(
                'Unable to determine cookie availability, must require an int type or type string or DateTimeInterface instance for expiration value'
            );
        }

        if (!is_numeric($lifetime)) {
            $lifetime = strtotime($lifetime);

            if (false === $lifetime) {
                throw new InvalidArgumentException(
                    'Unable to determine cookie availability, textual datetime could not parsed into a Unix timestamp'
                );
            }
        }

        if ($lifetime === 0) {
            return 0;
        }

        return $lifetime instanceof DateTimeInterface ? $lifetime->getTimestamp() : time() + $lifetime;
    }

    /**
     * @inheritDoc
     */
    public function getDefaults(
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?bool $raw = null,
        ?string $sameSite = null
    ): array {
        return [
            $path ?: $this->path,
            $domain ?: $this->domain,
            $secure ?: $this->secure,
            filter_var($httpOnly ?? $this->httpOnly, FILTER_VALIDATE_BOOLEAN),
            filter_var($raw ?? $this->raw, FILTER_VALIDATE_BOOLEAN),
            $sameSite ?: $this->sameSite,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @inheritDoc
     */
    public function make(string $alias, array $params = []): CookieInterface
    {
        $this->add($cookie = new Cookie($alias, $params, $this));

        return $cookie;
    }

    /**
     * @inheritDoc
     */
    public function setDefaults(
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?bool $raw = null,
        ?string $sameSite = null
    ): CookieJarInterface {
        [$this->path, $this->domain, $this->secure, $this->httpOnly, $this->raw, $this->sameSite] = [
            $path,
            $domain,
            $secure,
            filter_var($httpOnly ?? true, FILTER_VALIDATE_BOOLEAN),
            filter_var($raw ?? false, FILTER_VALIDATE_BOOLEAN),
            $sameSite,
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setLifetime($lifetime): CookieJarInterface
    {
        if (!is_int($lifetime) && !is_string($lifetime) && !$lifetime instanceof DateTimeInterface) {
            $lifetime = 0;
        }

        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setSalt(string $salt): CookieJarInterface
    {
        $this->salt = $salt;

        return $this;
    }
}