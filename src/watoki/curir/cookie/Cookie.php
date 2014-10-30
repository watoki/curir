<?php
namespace watoki\curir\cookie;

class Cookie {

    public static $CLASS = __CLASS__;

    /** @var string|array */
    public $payload;

    /** @var null|\DateTime */
    public $expire;

    /** @var null|string */
    public $path;

    /** @var null|string */
    public $domain;

    /** @var null|bool */
    public $secure;

    /** @var null|bool */
    public $httpOnly;

    function __construct($payload, \DateTime $expire = null, $path = '/', $domain = null, $secure = null, $httpOnly = null) {
        $this->domain = $domain;
        $this->expire = $expire;
        $this->httpOnly = $httpOnly;
        $this->path = $path;
        $this->payload = $payload;
        $this->secure = $secure;
    }

}