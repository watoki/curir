<?php
namespace watoki\curir;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\deli\Path;

class RequestBuilder {

    const DEFAULT_TARGET_KEY = '-';

    const DEFAULT_METHOD_KEY = '!';
    
    private $targetKey = self::DEFAULT_TARGET_KEY;
    
    private $methodKey = self::DEFAULT_METHOD_KEY;

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

    /** @var Path */
    private $context;

    function __construct(Path $context = null) {
        $this->context = $context ? : new Path();
    }

    /**
     * @param array $serverData
     * @param array $requestData
     * @throws HttpError If target key is missing
     * @return WebRequest
     */
    public function build($serverData, $requestData) {
        $method = $this->getMethod($serverData, $requestData);

        if (!array_key_exists($this->targetKey, $requestData)) {
            throw new HttpError(WebResponse::STATUS_BAD_REQUEST, "No target given.",
                    'Request parameter $_REQUEST["' . $this->targetKey . '"] not set');
        }

        $target = Path::fromString($requestData[$this->targetKey]);

        $formats = $this->getFormats($target, $serverData);

        $arguments = Map::toCollections($requestData);

//        $body = $this->readBody();

//        if ($method != Request::METHOD_GET && $method != Request::METHOD_HEAD) {
//            $params = $this->decodeParamsFromBody($params, $body, $serverData);
//        }

        $headers = new Map();
        foreach (self::$headerKeys as $name => $key) {
            if (isset($serverData[$key])) {
                $headers->set($name, $serverData[$key]);
            }
        }

        unset($requestData[$this->methodKey]);
        unset($requestData[$this->targetKey]);

        return new WebRequest($this->context, $target, $method, $arguments, new Liste($formats), $headers);
    }

    private function getMethod($serverData, $requestData) {
        $method = null;

        if (array_key_exists('REQUEST_METHOD', $serverData)) {
            $method = strtolower($serverData['REQUEST_METHOD']);
        }

        if (array_key_exists($this->methodKey, $requestData)) {
            $method = $requestData[$this->methodKey];
            return $method;
        }

        return $method;
    }

    private function getFormats($target, $serverData) {
        $formats = array();

        $extension = $this->popExtensions($target);
        if ($extension) {
            $formats[] = $extension;
        }

        if (array_key_exists('HTTP_ACCEPT', $serverData)) {
            foreach (explode(',', $serverData['HTTP_ACCEPT']) as $accepted) {
                $accepted = trim($accepted);
                if (strpos($accepted, ';') !== false) {
                    list($accepted,) = explode(';', $accepted);
                }
                $formats = array_merge($formats, MimeTypes::getExtensions($accepted));
            }
        }

        return array_unique($formats);
    }

    private function popExtensions(Path $target) {
        $extension = null;
        if (!$target->isEmpty() && strpos($target->last(), '.')) {
            $parts = explode('.', $target->pop());
            $extension = array_pop($parts);
            $target->append(implode('.', $parts));
            return $extension;
        }
        return $extension;
    }
}