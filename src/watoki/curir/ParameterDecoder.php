<?php
namespace watoki\curir;

use watoki\collections\Map;

interface ParameterDecoder {

    /**
     * @param string $body
     * @return Map
     */
    public function decode($body);

} 