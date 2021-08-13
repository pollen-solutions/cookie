<?php

declare(strict_types=1);

namespace Pollen\Cookie;

use Exception;
use Pollen\Encryption\Encrypter;
use Pollen\Http\RequestInterface;
use Pollen\Support\Proxy\CookieProxy;
use Pollen\Validation\Validator as v;
use Pollen\Support\Proxy\HttpRequestProxy;
use Symfony\Component\HttpFoundation\Cookie as BaseCookie;
use RuntimeException;

class Cookie extends BaseCookie implements CookieInterface
{
    use CookieProxy;
    use HttpRequestProxy;

    /**
     * Cookie alias identifier.
     * @var string
     */
    protected string $alias = '';

    /**
     * Encryption enabled indicator.
     * @var bool
     */
    protected bool $encrypted = false;

    /**
     * Queue processing flag enabled.
     * @var bool
     */
    protected bool $queued = false;

    /**
     * Cookie value prefix.
     * @var string|null
     */
    protected ?string $prefix = null;

    /**
     * @param string $alias
     * @param array $args
     * @param CookieJarInterface|null $cookieJar
     */
    public function __construct(string $alias, array $args = [], ?CookieJarInterface $cookieJar = null)
    {
        $this->alias = $alias;

        if ($cookieJar !== null) {
            $this->setCookieJar($cookieJar);
        }

        $name = $args['name'] ?? $alias;
        $salt = $args['salt'] ?? $this->cookie()->getSalt();
        if (is_string($salt)) {
            $name .= $salt;
        }
        $name = str_replace('.', '_', $name);

        $value = $args['value'] ?? null;
        $this->encrypted = filter_var($args['encrypted'] ?? $this->encrypted, FILTER_VALIDATE_BOOLEAN);
        $this->prefix = $args['prefix'] ?? null;
        if ($this->prefix && !is_string($this->prefix)) {
            throw new RuntimeException('Cookie could not prefix cookie value.');
        }

        $value = $this->formatValue($value);

        $expire = $cookieJar->getAvailability($args['lifetime'] ?? null);

        [$path, $domain, $secure, $httpOnly, $raw, $sameSite] = $this->cookie()->getDefaults(
            $args['path'] ?? null,
            $args['domain'] ?? null,
            $args['secure'] ?? null,
            $args['httpOnly'] ?? null,
            $args['raw'] ?? null,
            $args['sameSite'] ?? null
        );

        parent::__construct($name, $value, $expire, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }

    /**
     * @inheritDoc
     */
    public function checkRequestValue(?RequestInterface $request = null, $value = null): bool
    {
        $httpValue = $this->httpValue($request);

        if ($value === null) {
            $value = $this->resolveValue($this->getValue());
        }

        return $httpValue !== null && $value === $httpValue;
    }

    /**
     * @inheritDoc
     */
    public function clear(): CookieInterface
    {
        return $this->withValue(null)->withExpires(time() - (60 * 60 * 24 * 365 * 5));
    }

    /**
     * Decrypts cookie value.
     *
     * @param string $hashedValue
     *
     * @return string
     */
    protected function decrypt(string $hashedValue): string
    {
        return (new Encrypter(substr(hash('sha256', $this->alias), 0, 16)))->decrypt($hashedValue);
    }

    /**
     * Encrypts cookie value.
     *
     * @param string $plainValue
     *
     * @return string
     */
    protected function encrypt(string $plainValue): string
    {
        return (new Encrypter(substr(hash('sha256', $this->alias), 0, 16)))->encrypt($plainValue);
    }

    /**
     * Formats the real value passed in plain string with formatting parameters of cookie.
     *
     * @param string|array|null $value
     *
     * @return string|null
     */
    protected function formatValue($value): ?string
    {
        if ($value !== null && !is_string($value)) {
            try {
                $value = json_encode($value, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                throw new RuntimeException('Cookie could not encode the value in JSON');
            }
        }

        if ($value !== null && $this->encrypted) {
            $value = $this->encrypt($value);
        }

        if ($value !== null && $this->prefix) {
            $value = $this->prefix . $value;
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @inheritDoc
     */
    public function httpValue(?RequestInterface $request = null)
    {
        if ($request === null) {
            $request = $this->httpRequest();
        }

        if (!$value = $request->cookies->get($this->getName())) {
            return null;
        }

        if (!$this->isRaw()) {
            $value = rawurldecode($value);
        }

        return $this->resolveValue($value);
    }
    
    /**
     * @inheritDoc
     */
    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    /**
     * @inheritDoc
     */
    public function isQueued(): bool
    {
        return $this->queued;
    }

    /**
     * @inheritDoc
     */
    public function neverExpires(): CookieInterface
    {
        return $this->withExpires(time() + (60 * 60 * 24 * 365 * 5));
    }

    /**
     * @inheritDoc
     */
    public function queue(): CookieInterface
    {
        $this->queued = true;

        return $this;
    }

    /**
     * Resolves the real value from a plain string with formatting parameters of cookie.
     * {@internal Remove prefix, decrypts, json_decode if necessary}
     *
     * @param string $value
     *
     * @return string|array
     */
    protected function resolveValue(string $value)
    {
        if ($this->prefix) {
            $withoutPrefix = substr($value, strlen($this->prefix));
            if ($withoutPrefix !== false) {
                $value = $withoutPrefix;
            }
        }

        if ($this->isEncrypted()) {
            $value = $this->decrypt($value);
        }

        if (!is_numeric($value) && v::json()->validate($value)) {
            try {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                throw new RuntimeException('Cookie could not decode the value from JSON.');
            }
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function unqueue(): CookieInterface
    {
        $this->queued = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withRealValue($value = null): self
    {
        $value = $this->formatValue($value);

        return $this->withValue($value);
    }
}