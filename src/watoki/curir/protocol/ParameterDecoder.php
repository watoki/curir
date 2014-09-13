<?php
namespace watoki\curir\protocol;

use watoki\collections\Map;

interface ParameterDecoder {

    /**
     * @param string $body
     * @return Map
     */
    public function decode($body);

} 