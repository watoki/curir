<?php
namespace watoki\curir;

use watoki\curir\cookie\CookieStore;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebRequestBuilder;
use watoki\curir\delivery\WebResponse;
use watoki\curir\delivery\WebResponseDeliverer;
use watoki\curir\error\ErrorResponse;
use watoki\curir\error\HttpError;
use watoki\curir\protocol\decoder\FormDecoder;
use watoki\curir\protocol\decoder\ImageDecoder;
use watoki\curir\protocol\decoder\JsonDecoder;
use watoki\deli\Delivery;
use watoki\deli\filter\DefaultFilterRegistry;
use watoki\deli\filter\FilterRegistry;
use watoki\deli\Request;
use watoki\deli\RequestBuilder;
use watoki\deli\ResponseDeliverer;
use watoki\deli\router\NoneRouter;
use watoki\deli\Router;
use watoki\deli\target\RespondingTarget;
use watoki\factory\Factory;

class WebDelivery extends Delivery {

    /**
     * @param Factory $factory
     * @param Router $router
     * @param RequestBuilder $builder
     * @param ResponseDeliverer $deliverer
     */
    public function __construct(Factory $factory, Router $router, RequestBuilder $builder, ResponseDeliverer $deliverer) {
        parent::__construct($router, $builder, $deliverer);

        $factory->setSingleton(FilterRegistry::$CLASS, new DefaultFilterRegistry());
        $factory->setSingleton(CookieStore::$CLASS, $factory->getInstance(CookieStore::$CLASS, array('source' => $_COOKIE)));
    }

    public static function quickStart($rootResourceClass, Factory $factory = null) {
        $factory = $factory ? : new Factory();

        $root = $factory->getInstance($rootResourceClass);
        $router = new NoneRouter(RespondingTarget::factory($factory, $root));
        self::quickRoute($router, $factory);
    }

    public static function quickRoute(Router $router, Factory $factory = null) {
        $factory = $factory ? : new Factory();

        $builder = new WebRequestBuilder(new WebEnvironment($_SERVER, $_REQUEST));
        $deliverer = new WebResponseDeliverer();
        $delivery = new WebDelivery($factory, $router, $builder, $deliverer);
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
        } catch (\InvalidArgumentException $e) {
            throw new HttpError(WebResponse::STATUS_BAD_REQUEST,
                "A request parameter is invalid or missing.", $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param \watoki\deli\Request|WebRequest $request
     * @param \Exception $exception
     * @return WebResponse
     */
    protected function error(Request $request, \Exception $exception) {
        return new ErrorResponse($request, $exception);
    }

    protected function registerDecoders(WebRequestBuilder $builder) {
        $builder->registerDecoder(FormDecoder::CONTENT_TYPE, new FormDecoder());
        $builder->registerDecoder(FormDecoder::CONTENT_TYPE_X, new FormDecoder());
        $builder->registerDecoder(ImageDecoder::CONTENT_TYPE, new ImageDecoder());
        $builder->registerDecoder(JsonDecoder::CONTENT_TYPE, new JsonDecoder());
    }
}