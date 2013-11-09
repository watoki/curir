<?php
namespace watoki\curir;

use watoki\collections\Map;
use watoki\curir\http\Path;
use watoki\curir\http\Request;
use watoki\curir\http\Url;
use watoki\factory\Factory;

class WebApplication {

    private static $headerKeys = array(
        Request::HEADER_ACCEPT => 'HTTP_ACCEPT',
        Request::HEADER_ACCEPT_CHARSET => 'HTTP_ACCEPT_CHARSET',
        Request::HEADER_ACCEPT_ENCODING => 'HTTP_ACCEPT_ENCODING',
        Request::HEADER_ACCEPT_LANGUAGE => 'HTTP_ACCEPT_LANGUAGE',
        Request::HEADER_CACHE_CONTROL => 'HTTP_CACHE_CONTROL',
        Request::HEADER_CONNECTION => 'HTTP_CONNECTION',
        Request::HEADER_PRAGMA => 'HTTP_PRAGMA',
        Request::HEADER_USER_AGENT => 'HTTP_USER_AGENT'
    );

    /** @var Resource */
    private $root;

    public function __construct($rootResourceClass, Url $rootUrl, Factory $factory = null) {
        $this->rootResourceClass = $rootResourceClass;
        $factory = $factory ? : new Factory();

        $this->root = $factory->getInstance($rootResourceClass, array(
            'url' => $rootUrl,
            'parent' => null
        ));
    }

    public function run() {
        $this->root->respond($this->buildRequest())->flush();
    }

    protected function getTargetKey() {
        return '-';
    }


    protected function getMethodKey() {
        return 'method';
    }

    protected function buildRequest() {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if (array_key_exists($this->getMethodKey(), $_REQUEST)) {
            $method = $_REQUEST[$this->getMethodKey()];
            unset($_REQUEST[$this->getMethodKey()]);
        }

        if (!array_key_exists($this->getTargetKey(), $_REQUEST)) {
            throw new \InvalidArgumentException('Request parameter $_REQUEST["' . $this->getTargetKey() . '"] not set in ' . json_encode($_REQUEST,
                    true));
        }

        $target = Path::parse($_REQUEST[$this->getTargetKey()]);
        unset($_REQUEST[$this->getTargetKey()]);

        $format = null;
        if (!$target->isEmpty() && strpos($target->last(), '.')) {
            list($name, $format) = explode('.', $target->pop());
            $target->append($name);
        }

        $params = Map::toCollections($_REQUEST);

        $body = file_get_contents('php://input');

        $headers = new Map();
        foreach (self::$headerKeys as $name => $key) {
            if (isset($_SERVER[$key])) {
                $headers->set($name, $_SERVER[$key]);
            }
        }

        return new Request($target, $format, $method, $params, $headers, $body);
    }
}