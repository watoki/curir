<?php
namespace watoki\curir\delivery;

use watoki\deli\ResponseDeliverer;

class WebResponseDeliverer implements ResponseDeliverer {

    /**
     * @param WebResponse $response
     * @return null
     */
    public function deliver($response) {
        if ($response->getStatus()) {
            header('HTTP/1.1 ' . $response->getStatus());
        }
        foreach ($response->getHeaders() as $header => $value) {
            if (!is_null($value)) {
                header($header . ': ' . $value);
            }
        }
        echo $response->getBody();
    }
}