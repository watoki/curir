<?php
namespace watoki\curir\protocol\decoder;

use watoki\collections\Map;
use watoki\curir\protocol\ParameterDecoder;

class JsonDecoder implements ParameterDecoder {

    const CONTENT_TYPE = 'application/json';

    /**
     * @param string $body
     * @return Map
     */
    public function decode($body) {
        $decoded = json_decode($body, true);
        return is_array($decoded) ? new Map($decoded) : new Map();
    }
}