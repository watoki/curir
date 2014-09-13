<?php
namespace watoki\curir\protocol\decoder;

use watoki\collections\Map;
use watoki\curir\protocol\ParameterDecoder;

class FormDecoder implements ParameterDecoder {

    const CONTENT_TYPE = 'multipart/form-data';
    const CONTENT_TYPE_X = 'application/x-www-form-urlencoded';

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