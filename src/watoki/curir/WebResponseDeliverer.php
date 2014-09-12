<?php
namespace watoki\curir;

use watoki\deli\ResponseDeliverer;

class WebResponseDeliverer implements ResponseDeliverer {

    /**
     * @param WebResponse $response
     * @return null
     */
    public function deliver($response) {
        echo $response->getBody();
    }
}