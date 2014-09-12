<?php
namespace watoki\curir;

use watoki\curir\error\ErrorResponse;
use watoki\deli\Delivery;
use watoki\deli\Path;
use watoki\deli\Request;
use watoki\deli\RequestBuilder;
use watoki\deli\ResponseDeliverer;
use watoki\deli\Router;

class WebDelivery extends Delivery {

    public function __construct(Router $router, Path $context, RequestBuilder $builder = null, ResponseDeliverer $deliverer = null) {
        $builder = $builder ? : new WebRequestBuilder($_SERVER, $_REQUEST, function () {
            return file_get_contents('php://input');
        }, $context);
        $deliverer = $deliverer ? : new WebResponseDeliverer();
        parent::__construct($router, $builder, $deliverer);
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