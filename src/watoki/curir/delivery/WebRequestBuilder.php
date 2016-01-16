<?php
namespace watoki\curir\delivery;

use watoki\collections\Collection;
use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\curir\error\HttpError;
use watoki\curir\protocol\MimeTypes;
use watoki\curir\protocol\ParameterDecoder;
use watoki\curir\WebEnvironment;
use watoki\deli\Path;
use watoki\deli\RequestBuilder;

class WebRequestBuilder implements RequestBuilder {

    /** @var array|ParameterDecoder[] */
    private $decoders = array();

    /** @var WebEnvironment */
    private $environment;

    private static $headers = array(
            WebRequest::HEADER_ACCEPT,
            WebRequest::HEADER_ACCEPT_CHARSET,
            WebRequest::HEADER_ACCEPT_ENCODING,
            WebRequest::HEADER_ACCEPT_LANGUAGE,
            WebRequest::HEADER_CACHE_CONTROL,
            WebRequest::HEADER_CONNECTION,
            WebRequest::HEADER_PRAGMA,
            WebRequest::HEADER_USER_AGENT,
            WebRequest::HEADER_CONTENT_TYPE
    );

    private static $readingMethods = array(
            WebRequest::METHOD_GET,
            WebRequest::METHOD_HEAD,
            WebRequest::METHOD_OPTIONS,
    );

    function __construct(WebEnvironment $environment) {
        $this->environment = $environment;
    }

    /**
     * @param string $contentType
     * @param ParameterDecoder $decoder
     */
    public function registerDecoder($contentType, ParameterDecoder $decoder) {
        $this->decoders[$contentType] = $decoder;
    }

    /**
     * @throws HttpError
     * @return WebRequest
     */
    public function build() {
        list($target, $extension) = $this->parseExtension($this->environment->getTarget());

        return new WebRequest(
                $this->environment->getContext(),
                $target,
                $this->getMethod(),
                $this->getArguments($this->getMethod()),
                $this->getFormats($extension),
                $this->getHeaders()
        );
    }

    private function getMethod() {
        $arguments = $this->environment->getArguments();
        if ($arguments->has(WebRequest::$METHOD_KEY)) {
            return $arguments->remove(WebRequest::$METHOD_KEY);
        }
        return $this->environment->getRequestMethod();
    }

    private function getArguments($method) {
        return Collection::toCollections(array_merge(
                $this->environment->getArguments()->toArray(),
                $this->decodeBody($method)->toArray()
        ));
    }

    private function getFormats($extension) {
        $formats = $this->getFormatsFromHeaders();

        if ($extension) {
            $formats = array_unique(array_merge(array($extension), $formats));
        }

        return new Liste($formats);
    }

    private function getHeaders() {
        $headers = new Map();
        foreach (self::$headers as $header) {
            if ($this->environment->getHeaders()->has($header)) {
                $headers->set($header, $this->environment->getHeaders()->get($header));
            }
        }
        return $headers;
    }

    private function decodeBody($method) {
        if (!in_array($method, self::$readingMethods)) {
            return $this->decodeParamsFromBody($this->environment->getBody());
        }
        return new Map();
    }

    private function decodeParamsFromBody($body) {
        $decoder = $this->getDecoder();
        if (!$decoder) {
            return new Map();
        } else {
            return $decoder->decode($body);
        }
    }

    private function parseExtension(Path $target) {
        $extension = null;
        $elements = $target->getElements();

        if (count($elements) && strpos($elements[count($elements) - 1], '.')) {
            $parts = explode('.', array_pop($elements));
            $extension = array_pop($parts);
            $elements[] = implode('.', $parts);
        }

        return array(new Path($elements), $extension);
    }

    private function getFormatsFromHeaders() {
        $formats = array();
        $headers = $this->environment->getHeaders();
        if ($headers->has(WebRequest::HEADER_ACCEPT)) {
            foreach (explode(',', $headers->get(WebRequest::HEADER_ACCEPT)) as $accepted) {
                $accepted = trim($accepted);
                if (strpos($accepted, ';') !== false) {
                    list($accepted,) = explode(';', $accepted);
                }
                $formats = array_merge($formats, MimeTypes::getExtensions($accepted));
            }
        }
        return $formats;
    }

    private function getDecoder() {
        if ($this->environment->getHeaders()->has(WebRequest::HEADER_CONTENT_TYPE)
                && array_key_exists($this->environment->getHeaders()->get(WebRequest::HEADER_CONTENT_TYPE), $this->decoders)
        ) {
            return $this->decoders[$this->environment->getHeaders()->get(WebRequest::HEADER_CONTENT_TYPE)];
        }
        return null;
    }
}