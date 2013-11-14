<?php
namespace watoki\curir\http;
 
interface Responding {

    /**
     * @param Request $request
     * @return Response
     */
    public function respond(Request $request);

}
 