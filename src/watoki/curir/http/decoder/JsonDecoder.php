<?php
namespace watoki\curir\http\decoder;

use watoki\collections\Map;
use watoki\curir\http\ParameterDecoder;

class JsonDecoder implements ParameterDecoder {

    /**
     * @param string $body
     * @return Map
     */
    public function decode($body) {
        if (!$body) {
            return new Map();
        }
        return new Map(json_decode($body, true));
    }
}