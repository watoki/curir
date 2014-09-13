<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebRequestBuilder;
use watoki\curir\delivery\WebResponse;
use watoki\curir\delivery\WebResponseDeliverer;
use watoki\curir\error\ErrorResponse;
use watoki\curir\error\HttpError;
use watoki\curir\protocol\decoder\FormDecoder;
use watoki\curir\protocol\decoder\ImageDecoder;
use watoki\curir\protocol\decoder\JsonDecoder;
use watoki\curir\protocol\Url;
use watoki\deli\Delivery;
use watoki\deli\Request;
use watoki\deli\RequestBuilder;
use watoki\deli\ResponseDeliverer;
use watoki\deli\Router;
use watoki\deli\router\NoneRouter;
use watoki\deli\target\RespondingTarget;
use watoki\factory\Factory;

class WebDelivery extends Delivery {

    /**
     * @param Router $router
     * @param Url $context
     * @param RequestBuilder $builder
     * @param ResponseDeliverer $deliverer
     */
    public function __construct(Router $router, Url $context, RequestBuilder $builder = null, ResponseDeliverer $deliverer = null) {
        if (!$builder) {
            $bodyReader = function () {
                return file_get_contents('php://input');
            };
            $builder = new WebRequestBuilder($_SERVER, $_REQUEST, $bodyReader, $context);
            $this->registerDecoders($builder);
        }
        $deliverer = $deliverer ? : new WebResponseDeliverer();
        parent::__construct($router, $builder, $deliverer);
    }

    public static function quickStart($rootResourceClass, Factory $factory = null) {
        $factory = $factory ? : new Factory();
        self::quickRoute(new NoneRouter(RespondingTarget::factory($factory, $factory->getInstance($rootResourceClass))));
    }

    public static function quickRoute(Router $router) {
        $scheme = "http" . (!empty($_SERVER['HTTPS']) ? "s" : "");
        $port = $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '';
        $path = dirname($_SERVER['SCRIPT_NAME']);
        $url = $scheme . "://" . $_SERVER['SERVER_NAME'] . $port . $path;

        $delivery = new WebDelivery($router, Url::fromString($url));
        $delivery->run();
    }

    /**
     * @param Request|WebRequest $request
     * @throws HttpError
     * @throws \Exception
     * @return mixed
     */
    protected function getResponse(Request $request) {
        try {
            return parent::getResponse($request);
        } catch (\BadMethodCallException $e) {
            throw new HttpError(WebResponse::STATUS_METHOD_NOT_ALLOWED,
                "Method [{$request->getMethod()}] is not allowed here.", $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Cannot inject parameter') !== false) {
                throw new HttpError(WebResponse::STATUS_BAD_REQUEST,
                    "A request parameter is invalid or missing.", $e->getMessage(), 0, $e);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param Request|WebRequest $request
     * @param \Exception $exception
     * @return WebResponse
     */
    protected function error(Request $request, \Exception $exception) {
        return new ErrorResponse($request, $exception);
    }

    /**
     * @param WebRequestBuilder $builder
     */
    protected function registerDecoders(WebRequestBuilder $builder) {
        $builder->registerDecoder(FormDecoder::CONTENT_TYPE, new FormDecoder());
        $builder->registerDecoder(FormDecoder::CONTENT_TYPE_X, new FormDecoder());
        $builder->registerDecoder(ImageDecoder::CONTENT_TYPE, new ImageDecoder());
        $builder->registerDecoder(JsonDecoder::CONTENT_TYPE, new JsonDecoder());
    }
}