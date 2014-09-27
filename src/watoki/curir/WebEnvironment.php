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

    function __construct($server, $request) {
        $this->headers = $this->determineHeaders($server);
        $this->arguments = $this->determineArguments($request);
        $this->method = $this->determineMethod($server);
        $this->context = $this->determineContext($server);
        $this->target = $this->determineTarget($server);
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
     * @return string
     */
    public function getBody() {
        return file_get_contents('php://input');
    }

    private function determineHeaders($server) {
        $headers = new Map();
        foreach (self::$headerKeys as $headerKey => $serverKey) {
            if (array_key_exists($serverKey, $server)) {
                $headers->set($headerKey, $server[$serverKey]);
            }
        }
        return $headers;
    }

    private function determineArguments($request) {
        $arguments = new Map();
        foreach ($request as $key => $value) {
            $arguments->set($key, $value);
        }
        return $arguments;
    }

    private function determineMethod($server) {
        if (array_key_exists('REQUEST_METHOD', $server)) {
            return strtolower($server['REQUEST_METHOD']);
        }
        return null;
    }

    private function determineTarget($server) {
        if (isset($server['PATH_INFO'])) {
            $target = $server['PATH_INFO'];
        } else if ($this->isSpecialBuiltInWithRouteCase($server)) {
            $target = $server['SCRIPT_NAME'];
        } else {
            $target = '';
        }

        return Path::fromString(ltrim($target, '/'));
    }

    private function determineContext($server) {
        $scheme = "http" . (!empty($server['HTTPS']) ? "s" : "");
        $port = $server['SERVER_PORT'] != 80 ? ':' . $server['SERVER_PORT'] : '';
        $host = $server['SERVER_NAME'];
        $path = rtrim($this->determinePath($server), '/');

        return Url::fromString($scheme . "://" . $host . $port . $path);
    }

    private function determinePath($server) {
        $path = $server['SCRIPT_NAME'];

        if (!$this->startsWith($this->determineRequestUrl($server), $path)) {
            return substr($path, 0, -strlen(basename($server['SCRIPT_FILENAME'])));
        } else if ($this->isSpecialBuiltInWithRouteCase($server)) {
            return '';
        }
        return $path;
    }

    private function determineRequestUrl($server) {
        $requestUrl = $server['REQUEST_URI'];
        if (strpos($requestUrl, '?') !== false) {
            $requestUrl = substr($requestUrl, 0, strpos($requestUrl, '?'));
            return $requestUrl;
        }
        return $requestUrl;
    }

    private function startsWith($abc, $a) {
        return substr($abc, 0, strlen($a)) == $a;
    }

    private function contains($abc, $b) {
        return strpos($abc, $b) !== false;
    }

    private function isSpecialBuiltInWithRouteCase($server) {
        return !$this->contains($server['SCRIPT_NAME'], basename($server['SCRIPT_FILENAME']));
    }

} 