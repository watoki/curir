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
use watoki\curir\protocol\Url;
use watoki\deli\Delivery;
use watoki\deli\filter\DefaultFilterRegistry;
use watoki\deli\Request;
use watoki\deli\RequestBuilder;
use watoki\deli\ResponseDeliverer;
use watoki\deli\Router;
use watoki\deli\router\NoneRouter;
use watoki\deli\target\RespondingTarget;
use watoki\factory\Factory;
use watoki\deli\filter\FilterRegistry;

class WebDelivery extends Delivery {

    /**
     * @param Factory $factory
     * @param Router $router
     * @param Url $context
     * @param RequestBuilder $builder
     * @param ResponseDeliverer $deliverer
     */
    public function __construct(Factory $factory, Router $router, Url $context,
                                RequestBuilder $builder = null, ResponseDeliverer $deliverer = null) {
        parent::__construct($router,
            $builder ? : $this->createBuilder($context),
            $deliverer ? : new WebResponseDeliverer());

        $factory->setSingleton(FilterRegistry::$CLASS, new DefaultFilterRegistry());
        $factory->setSingleton(CookieStore::$CLASS, $factory->getInstance(CookieStore::$CLASS, array('source' => $_COOKIE)));
    }

    public static function quickStart($rootResourceClass, Factory $factory = null) {
        $factory = $factory ? : new Factory();
        $router = new NoneRouter(RespondingTarget::factory($factory, $factory->getInstance($rootResourceClass)));
        self::quickRoute($router, $factory);
    }

    public static function quickRoute(Router $router, Factory $factory = null) {
        $factory = $factory ? : new Factory();

        $scheme = "http" . (!empty($_SERVER['HTTPS']) ? "s" : "");
        $port = $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '';
        $path = dirname($_SERVER['SCRIPT_NAME']);
        $url = $scheme . "://" . $_SERVER['SERVER_NAME'] . $port . $path;

        $delivery = new WebDelivery($factory, $router, Url::fromString($url));
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
            if (strpos($e->getMessage(), 'Cannot fill parameter') !== false) {
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

    private function createBuilder(Url $context) {
        $bodyReader = function () {
            return file_get_contents('php://input');
        };
        $builder = new WebRequestBuilder($_SERVER, $_REQUEST, $bodyReader, $context);
        $this->registerDecoders($builder);
        return $builder;
    }

    protected function registerDecoders(WebRequestBuilder $builder) {
        $builder->registerDecoder(FormDecoder::CONTENT_TYPE, new FormDecoder());
        $builder->registerDecoder(FormDecoder::CONTENT_TYPE_X, new FormDecoder());
        $builder->registerDecoder(ImageDecoder::CONTENT_TYPE, new ImageDecoder());
        $builder->registerDecoder(JsonDecoder::CONTENT_TYPE, new JsonDecoder());
    }
}