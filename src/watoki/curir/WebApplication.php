<?php
namespace watoki\curir;

use watoki\collections\Map;
use watoki\curir\http\decoder\FormDecoder;
use watoki\curir\http\decoder\ImageDecoder;
use watoki\curir\http\decoder\JsonDecoder;
use watoki\curir\http\error\ErrorResponder;
use watoki\curir\http\error\HttpError;
use watoki\curir\http\MimeTypes;
use watoki\curir\http\ParameterDecoder;
use watoki\curir\http\Path;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
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
        Request::HEADER_USER_AGENT => 'HTTP_USER_AGENT',
        Request::HEADER_CONTENT_TYPE => 'CONTENT_TYPE'
    );

    /** @var Resource */
    private $root;

    /** @var array|ParameterDecoder[] */
    private $decoders = array();

    public static function quickStart($rootResourceClass, Factory $factory = null) {
        $factory = $factory ? : new Factory();

        $scheme = "http" . (!empty($_SERVER['HTTPS']) ? "s" : "");
        $port = $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '';
        $path = dirname($_SERVER['SCRIPT_NAME']);
        $url = $scheme . "://" . $_SERVER['SERVER_NAME'] . $port . $path;

        $app = new WebApplication($factory->getInstance($rootResourceClass, array(Url::parse($url))));
        $app->run();
    }

    public function __construct(Resource $root) {
        $this->root = $root;

        $formDecoder = new FormDecoder();
        $this->registerDecoder('application/x-www-form-urlencoded', $formDecoder);
        $this->registerDecoder('multipart/form-data', $formDecoder);
        $this->registerDecoder('application/json', new JsonDecoder());
        $this->registerDecoder('image/jpeg', new ImageDecoder());
    }

    public function onFatalError() {

    }

    public function run() {
        $this->getResponse($this->buildRequest())->flush();
    }

    protected function getTargetKey() {
        return '-';
    }

    protected function getMethodKey() {
        return 'method';
    }

    protected function getResponse(Request $request) {
        ini_set('display_errors', 0);
        $that = $this;
        register_shutdown_function(function () use ($request, $that) {
            $error = error_get_last();
            if (in_array($error['type'], array(E_ERROR, E_PARSE, E_USER_ERROR, E_RECOVERABLE_ERROR))) {
                $message = "Fatal Error: {$error['message']} in {$error['file']}:{$error['line']};";
                $that->getErrorResponder(new \Exception($message))->createResponse($request)->flush();
            }
        });

        try {
            return $this->root->respond($request);
        } catch (\Exception $e) {
            return $this->getErrorResponder($e)->createResponse($request);
        }
    }

    protected function buildRequest() {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if (array_key_exists($this->getMethodKey(), $_REQUEST)) {
            $method = $_REQUEST[$this->getMethodKey()];
            unset($_REQUEST[$this->getMethodKey()]);
        }

        if (!array_key_exists($this->getTargetKey(), $_REQUEST)) {
            throw new HttpError(Response::STATUS_BAD_REQUEST, "The target resource is missing.",
                    'Request parameter $_REQUEST["' . $this->getTargetKey() . '"] not set in ' . json_encode($_REQUEST, true));
        }

        $target = Path::parse($_REQUEST[$this->getTargetKey()]);
        unset($_REQUEST[$this->getTargetKey()]);

        $formats = array();
        if (!$target->isEmpty() && strpos($target->last(), '.')) {
            $parts = explode('.', $target->pop());
            $formats[] = array_pop($parts);
            $target->append(implode('.', $parts));
        }
        foreach (explode(',', $_SERVER['HTTP_ACCEPT']) as $accepted) {
            $accepted = trim($accepted);
            if (strpos($accepted, ';') !== false) {
                list($accepted,) = explode(';', $accepted);
            }
            $formats = array_unique(array_merge($formats, MimeTypes::getExtensions($accepted)));
        }

        $params = Map::toCollections($_REQUEST);

        $body = $this->readBody();

        if ($method != Request::METHOD_GET && $method != Request::METHOD_HEAD) {
            $params = $this->decodeParamsFromBody($params, $body);
        }

        $headers = new Map();
        foreach (self::$headerKeys as $name => $key) {
            if (isset($_SERVER[$key])) {
                $headers->set($name, $_SERVER[$key]);
            }
        }

        return new Request($target, $formats, $method, $params, $headers, $body);
    }

    private function decodeParamsFromBody(Map $params, $body) {
        $key = self::$headerKeys[Request::HEADER_CONTENT_TYPE];
        $contentType = isset($_SERVER[$key]) ? $_SERVER[$key] : null;

        if (!array_key_exists($contentType, $this->decoders)) {
            return $params;
        }

        foreach ($this->decoders[$contentType]->decode($body) as $key => $value) {
            $params->set($key, $value);
        }
        return $params;
    }

    public function registerDecoder($contentType, ParameterDecoder $decoder) {
        $this->decoders[$contentType] = $decoder;
    }

    protected function readBody() {
        return file_get_contents('php://input');
    }

    /**
     * @param \Exception $e
     * @return ErrorResponder
     */
    public function getErrorResponder(\Exception $e) {
        return new ErrorResponder($this->root, $e);
    }
}
