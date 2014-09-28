<?php
namespace watoki\curir\cookie;

use watoki\stores\ObjectSerializer;

class CookieSerializer extends ObjectSerializer {

    public function inflate($serialized) {
        return parent::inflate(json_decode($serialized, true));
    }

    public function serialize($inflated) {
        return json_encode(parent::serialize($inflated));
    }
}