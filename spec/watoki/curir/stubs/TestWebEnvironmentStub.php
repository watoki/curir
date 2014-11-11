<?php
namespace spec\watoki\curir\stubs;

use watoki\collections\Map;
use watoki\curir\protocol\Url;
use watoki\curir\WebEnvironment;
use watoki\deli\Path;

class TestWebEnvironmentStub extends WebEnvironment {

    /** @var Url */
    public $context;

    /** @var null|string */
    public $method;

    /** @var Map */
    public $headers;

    /** @var Map */
    public $arguments;

    /** @var Path */
    public $target;

    /** @var string */
    public $body;

    /** @var Map */
    public $files;

    function __construct() {
        $this->headers = new Map();
        $this->arguments = new Map();
        $this->files = new Map();
        $this->target = new Path();
        $this->context = Url::fromString('http://example.com');
    }

    public function getArguments() {
        return $this->arguments;
    }

    public function getContext() {
        return $this->context;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function getTarget() {
        return $this->target;
    }

    public function getBody() {
        return $this->body;
    }

    public function getRequestMethod() {
        return $this->method;
    }

    public function getFiles() {
        return $this->files;
    }

}