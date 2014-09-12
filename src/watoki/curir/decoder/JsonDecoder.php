<?php
namespace watoki\curir\decoder;

use watoki\collections\Map;
use watoki\curir\ParameterDecoder;

class JsonDecoder implements ParameterDecoder {

    /**
     * @param string $body
     * @return Map
     */
    public function decode($body) {
        $decoded = json_decode($body, true);
        return is_array($decoded) ? new Map($decoded) : new Map();
    }
}