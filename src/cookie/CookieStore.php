<?php
namespace watoki\curir\cookie;

use watoki\stores\exceptions\NotFoundException;
use watoki\stores\Store;

class CookieStore implements Store {

    /** @var array With cookie data indexed by key */
    private $source;

    /** @var array|array[] setcookie arguments without name indexed by name */
    private $serialized = array();

    /**
     * @param array $source
     */
    public function __construct(array $source = []) {
        $this->source = $source;
    }

    /**
     * @param Cookie $cookie Data to be stored
     * @param string $name Name of the cookie
     * @return string The cookie name
     */
    public function write($cookie, $name = null) {
        $serialized = json_encode(array(
            'payload' => $cookie->payload,
            'expire' => $cookie->expire ? $cookie->expire->format('c') : null,
            'path' => $cookie->path,
            'domain' => $cookie->domain,
            'secure' => $cookie->secure,
            'httpOnly' => $cookie->httpOnly
        ));

        $this->serialized[$name] = array(
            $serialized,
            $cookie->expire ? $cookie->expire->getTimestamp() : null,
            $cookie->path,
            $cookie->domain,
            $cookie->secure,
            $cookie->httpOnly);;

        $this->source[$name] = $serialized;
    }

    /**
     * @param string $key
     * @return Cookie
     * @throws NotFoundException If no data is stored under this key
     */
    public function read($key) {
        if (!array_key_exists($key, $this->source)) {
            throw new NotFoundException($key);
        }

        $value = $this->source[$key];
        $serialized = json_decode($value, true);

        if (!$serialized) {
            return new Cookie($value);
        }

        return new Cookie(
            $serialized['payload'],
            isset($serialized['expire']) && $serialized['expire'] ? new \DateTime($serialized['expire']) : null,
            isset($serialized['path']) ? $serialized['path'] : null,
            isset($serialized['domain']) ? $serialized['domain'] : null,
            isset($serialized['secure']) ? $serialized['secure'] : null,
            isset($serialized['httpOnly']) ? $serialized['httpOnly'] : null
        );
    }

    /**
     * @param mixed $key The key which to remove from the store
     * @return void
     * @throws NotFoundException If no data is stored under this key
     */
    public function remove($key) {
        $this->serialized[$key] = array(null, time() - 3600, '/');
    }

    /**
     * @param mixed $key
     * @return boolean True if the key exists, false otherwise
     */
    public function has($key) {
        return in_array($key, $this->keys());
    }

    /**
     * @return mixed[] All keys that are currently stored
     */
    public function keys() {
        return array_keys(array_merge($this->source, $this->serialized));
    }

    /**
     * @param callable $callable Called with (key, payload, expire, path, domain, secure, httpOnly) for each cookie
     */
    public function applyCookies($callable) {
        foreach ($this->serialized as $key => $args) {
            call_user_func_array($callable, array_merge(array($key), $args));
        }
    }
}