<?php
namespace watoki\curir;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\curir\error\HttpError;
use watoki\deli\Path;
use watoki\deli\RequestBuilder;

class WebRequestBuilder implements RequestBuilder {

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

    /** @var array|ParameterDecoder[] */
    private $decoders = array();

    /** @var array */
    private $serverData;

    /** @var array */
    private $requestData;

    /** @var callable */
    private $bodyReader;

    /** @var Path */
    private $context;

    /**
     * @param array $serverData
     * @param array $requestData
     * @param callable $bodyReader
     * @param Url $context
     */
    function __construct($serverData, $requestData, $bodyReader, Url $context = null) {
        $this->serverData = $serverData;
        $this->requestData = $requestData;
        $this->bodyReader = $bodyReader;
        $this->context = $context ?  : Url::fromString('http://example.com');
    }

    /**
     * @throws HttpError
     * @return WebRequest
     */
    public function build() {
        $method = $this->getMethod($this->serverData, $this->requestData);

        if (!array_key_exists($this->targetKey, $this->requestData)) {
            throw new HttpError(WebResponse::STATUS_BAD_REQUEST, "No target given.",
                    'Request parameter $_REQUEST["' . $this->targetKey . '"] not set');
        }

        $target = Path::fromString($this->requestData[$this->targetKey]);

        $formats = $this->getFormats($target, $this->serverData);

        unset($this->requestData[$this->methodKey]);
        unset($this->requestData[$this->targetKey]);
        $arguments = Map::toCollections($this->requestData);

        $body = call_user_func($this->bodyReader);

        if ($method != WebRequest::METHOD_GET && $method != WebRequest::METHOD_HEAD) {
            $arguments = $this->decodeParamsFromBody($arguments, $body, $this->serverData);
        }

        $headers = new Map();
        foreach (self::$headerKeys as $name => $key) {
            if (isset($this->serverData[$key])) {
                $headers->set($name, $this->serverData[$key]);
            }
        }

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

    private function decodeParamsFromBody(Map $args, $body, $serverData) {
        $key = self::$headerKeys[WebRequest::HEADER_CONTENT_TYPE];
        $contentType = isset($serverData[$key]) ? $serverData[$key] : null;

        if (!array_key_exists($contentType, $this->decoders)) {
            return $args;
        }

        foreach ($this->decoders[$contentType]->decode($body) as $key => $value) {
            $args->set($key, $value);
        }
        return $args;
    }

    public function registerDecoder($contentType, ParameterDecoder $decoder) {
        $this->decoders[$contentType] = $decoder;
    }
}