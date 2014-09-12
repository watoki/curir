<?php
namespace watoki\curir;

use watoki\curir\error\ErrorResponse;
use watoki\curir\error\HttpError;
use watoki\deli\Delivery;
use watoki\deli\Request;
use watoki\deli\RequestBuilder;
use watoki\deli\ResponseDeliverer;
use watoki\deli\Router;

class WebDelivery extends Delivery {

    public function __construct(Router $router, Url $context, RequestBuilder $builder = null, ResponseDeliverer $deliverer = null) {
        $builder = $builder ? : new WebRequestBuilder($_SERVER, $_REQUEST, function () {
            return file_get_contents('php://input');
        }, $context);
        $deliverer = $deliverer ? : new WebResponseDeliverer();
        parent::__construct($router, $builder, $deliverer);
    }

    /**
     * @param Request|WebRequest $request
     * @throws HttpError if the method does not exist in the target
     * @return mixed
     */
    protected function getResponse(Request $request) {
        try {
            return parent::getResponse($request);
        } catch (\BadMethodCallException $e) {
            throw new HttpError(WebResponse::STATUS_METHOD_NOT_ALLOWED,
                "Method [{$request->getMethod()}] is not allowed here.", $e->getMessage(), 0, $e);
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
}