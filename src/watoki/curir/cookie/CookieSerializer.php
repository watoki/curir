<?php
namespace watoki\curir\cookie;

use watoki\stores\Serializer;

class CookieSerializer implements Serializer {

    public function inflate($serialized) {
        $serialized = json_decode($serialized, true);
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
     * @param Cookie $inflated
     * @return string
     */
    public function serialize($inflated) {
        return json_encode(array(
            'payload' => $inflated->payload,
            'expire' => $inflated->expire ? $inflated->expire->format('c') : null,
            'path' => $inflated->path,
            'domain' => $inflated->domain,
            'secure' => $inflated->secure,
            'httpOnly' => $inflated->httpOnly
        ));
    }
}