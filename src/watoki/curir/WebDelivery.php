<?php
namespace watoki\curir;

use watoki\curir\cookie\CookieStore;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebRequestBuilder;
use watoki\curir\delivery\WebResponse;
use watoki\curir\delivery\WebResponseDeliverer;
use watoki\curir\delivery\WebRouter;
use watoki\curir\error\ErrorResponse;
use watoki\curir\error\HttpError;
use watoki\curir\protocol\decoder\FormDecoder;
use watoki\curir\protocol\decoder\ImageDecoder;
use watoki\curir\protocol\decoder\JsonDecoder;
use watoki\curir\rendering\VariableRenderer;
use watoki\curir\rendering\Renderer;
use watoki\deli\Delivery;
use watoki\deli\filter\DefaultFilterRegistry;
use watoki\deli\filter\FilterRegistry;
use watoki\deli\Path;
use watoki\deli\Request;
use watoki\deli\Router;
use watoki\deli\router\NoneRouter;
use watoki\deli\target\CallbackTarget;
use watoki\deli\target\RespondingTarget;
use watoki\factory\Factory;

class WebDelivery extends Delivery {

    public static function init(Factory $factory = null) {
        $factory = $factory ? : new Factory();

        $factory->setSingleton(Renderer::RENDERER, new VariableRenderer());
        $factory->setSingleton(FilterRegistry::$CLASS, new DefaultFilterRegistry());
        $factory->getSingleton(CookieStore::$CLASS, array('source' => $_COOKIE));

        return $factory;
    }

    public static function quickStart($rootResourceClass, Factory $factory = null) {
        $factory = $factory ? : self::init();

        $root = $factory->getInstance($rootResourceClass);
        $router = new NoneRouter(RespondingTarget::factory($factory, $root));
        self::quickRoute($router, $factory);
    }

    public static function quickRoot($rootDirectory, $defaultPath = 'index', $namespace = '', Factory $factory = null) {
        $factory = $factory ? : self::init();

        $router = new WebRouter($factory, $rootDirectory, $namespace);
        $router->setDefaultTarget(CallbackTarget::factory(function (WebRequest $request) use ($router, $defaultPath) {
            $request->setTarget(Path::fromString($defaultPath));
            return $router->route($request)->respond();
        }));
        self::quickRoute($router, $factory);
    }

    public static function quickRoute(Router $router, Factory $factory = null) {
        $factory = $factory ? : self::init();

        $builder = new WebRequestBuilder(new WebEnvironment($_SERVER, $_REQUEST));
        $deliverer = new WebResponseDeliverer($factory->getSingleton(CookieStore::$CLASS));
        $delivery = new WebDelivery($router, $builder, $deliverer);
        $delivery->run();
    }

    /**
     * @param Request|WebRequest $request
     * @throws HttpError
     * @throws \Exception
     * @return mixed
     */
    protected function getResponse(Request $request) {
        if ($request->getTarget()->count() > 0 && $request->getTarget()->last() == '') {
            $request->getTarget()->pop();
            $response = new WebResponse();
            $response->getHeaders()->set(WebResponse::HEADER_LOCATION, $request->toString());
            $response->setStatus(WebResponse::STATUS_MOVED_PERMANENTLY);
            return $response;
        }

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