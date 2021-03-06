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
     * @param WebResponse|mixed $response
     * @throws \Exception if $response is not a WebResponse
     * @return null
     */
    public function deliver($response) {
        if ($response instanceof WebResponse) {
            if ($response->getStatus()) {
                header('HTTP/1.1 ' . $response->getStatus());
            }
            foreach ($response->getHeaders() as $header => $value) {
                if (!is_null($value)) {
                    header($header . ': ' . $value);
                }
            }
        }
        $this->cookies->applyCookies('setcookie');

        echo $response;
    }
}