<?php
namespace watoki\curir\cookie;

use watoki\stores\exception\NotFoundException;
use watoki\stores\GeneralStore;

class CookieStore extends GeneralStore {

    public static $CLASS = __CLASS__;

    /** @var array With cookie data indexed by key */
    private $source;

    /** @var array|array[] setcookie arguments without name indexed by name */
    private $serialized = array();

    /**
     * @param array $source
     */
    public function __construct(array $source) {
        parent::__construct(new CookieSerializer());
        $this->source = $source;
    }

    /**
     * @param string $key
     * @return Cookie Entity belonging given key
     * @throws \watoki\stores\exception\NotFoundException If no entity with given key exists
     */
    protected function _read($key) {
        if (!array_key_exists($key, $this->source)) {
            throw new NotFoundException("Cookie with name [$key] does not exist");
        }
        $value = $this->source[$key];
        if (!json_decode($value)) {
            return $this->inflate(json_encode(array("payload" => $value)), $key);
        }
        return $this->inflate($value, $key);
    }

    /**
     * @param Cookie $entity
     * @param string $key If omitted, a key will be generated
     * @throws \InvalidArgumentException If no key is provided
     * @return null
     */
    protected function _create($entity, $key) {
        $this->serialized[$key] = $this->serialize($entity, $key);
        $this->source[$key] = $this->serialized[$key][0];
    }

    /**
     * @param Cookie $entity
     * @throws \BadMethodCallException always
     * @return null
     */
    protected function _update($entity) {
        $this->create($entity, $this->getKey($entity));
    }

    /**
     * @param string $key
     * @return null
     */
    protected function _delete($key) {
        $this->serialized[$key] = array(null, time() - 3600, '/');
    }

    /**
     * @param Cookie $cookie
     * @return array
     */
    protected function serialize($cookie) {
        return array(
                parent::serialize($cookie),
                $cookie->expire ? $cookie->expire->getTimestamp() : null,
                $cookie->path,
                $cookie->domain,
                $cookie->secure,
                $cookie->httpOnly);
    }

    /**
     * @return array|mixed[] All stored keys
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