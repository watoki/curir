<?php
namespace watoki\curir\cookie;

use watoki\stores\Serializer;

class CookieSerializer implements Serializer {

    /**
     * @param Cookie $cookie
     * @return array With the arguments of setcookie excluding key
     */
    public function serialize($cookie) {
        $expire = $cookie->expire ? $cookie->expire->getTimestamp() : null;
        $encoded = is_string($cookie->payload) ? $cookie->payload : json_encode($cookie->payload);

        return array($encoded, $expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
    }

    /**
     * @param string $serialized Either json decoded or a literal string
     * @return Cookie
     */
    public function inflate($serialized) {
        $decoded = json_decode($serialized, true);
        return new Cookie($decoded ? : $serialized);
    }
}