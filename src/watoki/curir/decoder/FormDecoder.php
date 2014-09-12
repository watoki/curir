<?php
namespace watoki\curir\decoder;

use watoki\collections\Map;
use watoki\curir\ParameterDecoder;

class FormDecoder implements ParameterDecoder {

    /**
     * @param string $body
     * @return Map
     */
    public function decode($body) {
        $params = array();
        parse_str($body, $params);
        return new Map($params);
    }
}