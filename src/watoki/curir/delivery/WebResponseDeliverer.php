<?php
namespace watoki\curir\delivery;

use watoki\curir\cookie\CookieStore;
use watoki\deli\ResponseDeliverer;

class WebResponseDeliverer implements ResponseDeliverer {

    /** @var CookieStore */
    private $cookies;

    /**
     * @param CookieStore $cookies <-
     */
    function __construct(CookieStore $cookies) {
        $this->cookies = $cookies;
    }

    /**
     * @param WebResponse $response
     * @throws \Exception if $response is not a WebResponse
     * @return null
     */
    public function deliver($response) {
        if (!($response instanceof WebResponse)) {
            $type = is_object($response) ? get_class($response) : json_encode($response);
            throw new \Exception('The response needs to be an instance of \watoki\curir\delivery\WebResponse. Got [' . $type . ']');
        }
        if ($response->getStatus()) {
            header('HTTP/1.1 ' . $response->getStatus());
        }
        foreach ($response->getHeaders() as $header => $value) {
            if (!is_null($value)) {
                header($header . ': ' . $value);
            }
        }
        $this->cookies->applyCookies('setcookie');

        echo $response->getBody();
    }
}