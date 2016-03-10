<?php
namespace watoki\curir\protocol\decoder;

use watoki\collections\Map;
use watoki\curir\protocol\ParameterDecoder;

class ImageDecoder implements ParameterDecoder {

    const CONTENT_TYPE = 'image/jpeg';

    private $targetParameter;

    public function __construct($targetParameter = "bodyAsImage") {
        $this->targetParameter = $targetParameter;
    }

    /**
     * @param string $body
     * @return Map
     */
    public function decode($body) {
        $params = array();

        $image = @imagecreatefromstring($body);
        if ($image !== false) {
            $params[$this->targetParameter] = $image;
        }

        return new Map($params);
    }

}