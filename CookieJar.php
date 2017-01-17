<?php
declare(strict_types=1);
namespace Viserio\Component\Cookie;

use Cake\Chronos\Chronos;
use Narrowspark\Arr\Arr;
use Viserio\Component\Contracts\Cookie\Cookie as CookieContract;
use Viserio\Component\Contracts\Cookie\QueueingFactory as JarContract;

class CookieJar implements JarContract
{
    /**
     * The default path (if specified).
     *
     * @var string
     */
    protected $path = '/';

    /**
     * The default domain (if specified).
     *
     * @var string
     */
    protected $domain = null;

    /**
     * The default secure setting.
     *
     * @var bool
     */
    protected $secure = false;

    /**
     * All of the cookies queued for sending.
     *
     * @var array
     */
    protected $queued = [];

    /**
     * {@inheritdoc}
     */
    public function create(
        string $name,
        string $value = null,
        int $minutes = 0,
        string $path = null,
        string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        $sameSite = false
    ): CookieContract {
        list($path, $domain, $secure) = $this->getPathAndDomain($path, $domain, $secure);

        $time = ($minutes === 0) ? 0 : Chronos::now()->getTimestamp() + ($minutes * 60);

        return new SetCookie($name, $value, $time, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * {@inheritdoc}
     */
    public function forever(
        string $name,
        string $value = null,
        string $path = null,
        string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        $sameSite = false
    ): CookieContract {
        return $this->create($name, $value, 2628000, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        string $name,
        string $path = null,
        string $domain = null
    ): CookieContract {
        return $this->create($name, null, -2628000, $path, $domain);
    }

    /**
     * {@inheritdoc}
     */
    public function hasQueued(string $key): bool
    {
        return $this->queued($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function queued(string $key, $default = null)
    {
        return Arr::get($this->queued, $key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function queue(...$arguments)
    {
        if (reset($arguments) instanceof CookieContract) {
            $cookie = reset($arguments);
        } else {
            $cookie = call_user_func_array([$this, 'create'], $arguments);
        }

        $this->queued[$cookie->getName()] = $cookie;
    }

    /**
     * {@inheritdoc}
     */
    public function unqueue(string $name)
    {
        unset($this->queued[$name]);
    }

    /**
     * Set the default path and domain for the jar.
     *
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     *
     * @return $this
     */
    public function setDefaultPathAndDomain(string $path, string $domain, bool $secure = false): JarContract
    {
        list($this->path, $this->domain, $this->secure) = [$path, $domain, $secure];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueuedCookies(): array
    {
        return $this->queued;
    }

    /**
     * Get the path and domain, or the default values.
     *
     * @param string|null $path
     * @param string|null $domain
     * @param bool        $secure
     *
     * @return string[]
     */
    protected function getPathAndDomain($path, $domain, bool $secure = false): array
    {
        return [$path ?? $this->path, $domain ?? $this->domain, $secure ?? $this->secure];
    }
}
