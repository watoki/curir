<?php
namespace watoki\curir;

use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\curir\protocol\Url;
use watoki\deli\Path;

class WebEnvironment {

    private static $headerKeys = array(
            WebRequest::HEADER_ACCEPT => 'HTTP_ACCEPT',
            WebRequest::HEADER_ACCEPT_CHARSET => 'HTTP_ACCEPT_CHARSET',
            WebRequest::HEADER_ACCEPT_ENCODING => 'HTTP_ACCEPT_ENCODING',
            WebRequest::HEADER_ACCEPT_LANGUAGE => 'HTTP_ACCEPT_LANGUAGE',
            WebRequest::HEADER_CACHE_CONTROL => 'HTTP_CACHE_CONTROL',
            WebRequest::HEADER_CONNECTION => 'HTTP_CONNECTION',
            WebRequest::HEADER_PRAGMA => 'HTTP_PRAGMA',
            WebRequest::HEADER_USER_AGENT => 'HTTP_USER_AGENT',
            WebRequest::HEADER_CONTENT_TYPE => 'CONTENT_TYPE'
    );

    private $headers;

    private $method;

    private $arguments;

    private $context;

    private $target;

    private $files;

    function __construct($server, $request, $files) {
        $this->headers = $this->determineHeaders($server);
        $this->arguments = $this->determineArguments($request);
        $this->method = $this->determineMethod($server);
        $this->target = $this->determineTarget($server);
        $this->context = $this->determineContext($server);
        $this->files = $this->determineFiles($files);
    }

    /**
     * @return Url
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * @return Path
     */
    public function getTarget() {
        return $this->target;
    }

    /**
     * @return string
     */
    public function getRequestMethod() {
        return $this->method;
    }

    /**
     * @return Map
     */
    public function getArguments() {
        return $this->arguments;
    }

    /**
     * @return Map
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @return Map
     */
    public function getFiles() {
        return $this->files;
    }

    /**
     * @return string
     */
    public function getBody() {
        return file_get_contents('php://input');
    }

    protected function determineHeaders($server) {
        $headers = new Map();
        foreach (self::$headerKeys as $headerKey => $serverKey) {
            if (array_key_exists($serverKey, $server)) {
                $headers->set($headerKey, $server[$serverKey]);
            }
        }
        return $headers;
    }

    protected function determineArguments($request) {
        $arguments = new Map();
        foreach ($request as $key => $value) {
            $arguments->set($key, $value);
        }
        return $arguments;
    }

    protected function determineFiles($fileArrays) {
        $files = new Map();

        foreach ($fileArrays as $name => $array) {
            if (!is_array($array['name'])) {
                $sorted = $array;
            } else {
                $sorted = array();
                $count = count($array['name']);
                $keys = array_keys($array);

                for ($i = 0; $i < $count; $i++) {
                    foreach ($keys as $key) {
                        $sorted[$i][$key] = $array[$key][$i];
                    }
                }
            }

            $files->set($name, $sorted);
        }

        return $files;
    }

    protected function determineMethod($server) {
        if (array_key_exists('REQUEST_METHOD', $server)) {
            return strtolower($server['REQUEST_METHOD']);
        }
        return null;
    }

    protected function determineTarget($server) {
        list(, $target) = $this->splitContextAndTarget($server);
        return Path::fromString(ltrim($target, '/'));
    }

    protected function determineContext($server) {
        $scheme = "http" . (!empty($server['HTTPS']) ? "s" : "");

        if (isset($server['HTTP_HOST'])) {
            $host = $server['HTTP_HOST'];
        } else {
            $host = $server['SERVER_NAME'] . ($server['SERVER_PORT'] != 80 ? ':' . $server['SERVER_PORT'] : '');
        }

        list($context,) = $this->splitContextAndTarget($server);

        return Url::fromString($scheme . "://" . $host . rtrim($context,  '/'));
    }

    protected function splitContextAndTarget($server) {
        $uri = $server['REQUEST_URI'];
        if (strpos($uri, '?') !== false) {
            list($uri,) = explode('?', $uri);
        }

        $scriptName = $server['SCRIPT_NAME'];

        $names = explode('/', $scriptName);
        if (count($names) >= 2 && $names[count($names) - 1] == $names[count($names) - 2]) {
            $scriptName = substr($scriptName, 0, -strlen($names[count($names) - 1]) - 1);
        }

        $context = '';
        $target = $uri;

        if ($this->endsWith($scriptName, '.php')) {
            if ($this->startsWith($uri, $scriptName)) {
                $context = substr($uri, 0, strlen($scriptName));
            } else {
                $context = substr($scriptName, 0, strrpos($scriptName, '/'));
            }
            $target = substr($uri, strlen($context));
        }

        return array($context, $target);
    }

    private function startsWith($abc, $a) {
        return substr($abc, 0, strlen($a)) == $a;
    }

    private function endsWith($abc, $c) {
        return substr($abc, -strlen($c)) == $c;
    }

} 