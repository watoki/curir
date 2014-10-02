<?php
namespace watoki\curir\delivery;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\curir\protocol\Url;
use watoki\deli\Path;
use watoki\deli\Request;

class WebRequest extends Request {

    const METHOD_GET = 'get';

    const METHOD_POST = 'post';

    const METHOD_PUT = 'put';

    const METHOD_PATCH = 'patch';

    const METHOD_DELETE = 'delete';

    const METHOD_HEAD = 'head';

    const METHOD_OPTIONS = 'options';

    const METHOD_TRACE = 'trace';

    const HEADER_ACCEPT = 'Accept';

    const HEADER_ACCEPT_CHARSET = 'Accept-Charset';

    const HEADER_ACCEPT_ENCODING = 'Accept-Encoding';

    const HEADER_ACCEPT_LANGUAGE = 'Accept-Language';

    const HEADER_CACHE_CONTROL = 'Cache-Control';

    const HEADER_CONNECTION = 'Connection';

    const HEADER_PRAGMA = 'Pragma';

    const HEADER_USER_AGENT = 'User-Agent';

    const HEADER_CONTENT_TYPE = 'Content-Type';

    public static $METHOD_KEY = 'do';

    /** @var Liste */
    private $formats;

    /** @var Map */
    private $headers;

    function __construct(Url $context, Path $target, $method = null, Map $arguments = null, Liste $formats = null,
                         Map $headers = null) {
        parent::__construct($context, $target, $method, $arguments);
        $this->formats = $formats ? : new Liste();
        $this->headers = $headers ? : new Map();
    }

    public function getFormats() {
        return $this->formats;
    }

    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @return Url
     */
    public function getContext() {
        return parent::getContext();
    }

    /**
     * @param Path|Url $context
     */
    public function setContext(Path $context) {
        if (!($context instanceof Url)) {
            $newContext = $this->getContext()->copy();
            $newContext->setPath($context);
            $context = $newContext;
        }
        parent::setContext($context);
    }

    public function copy() {
        return new WebRequest(
                $this->getContext()->copy(),
                $this->getTarget()->copy(),
                $this->getMethod(),
                $this->getArguments()->copy(),
                $this->formats->copy(),
                $this->headers->copy()
        );
    }

    public function toString() {
        $queryString = $this->getArguments()->isEmpty() ? '' : '?' . urldecode(http_build_query($this->getArguments()->toArray()));
        $targetString = $this->getTarget()->isEmpty() ? '' : '/' . $this->getTarget()->toString();
        return $this->getContext()->toString() . $targetString . $queryString;
    }

}