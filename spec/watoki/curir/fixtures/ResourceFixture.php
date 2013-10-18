<?php
namespace spec\watoki\curir\fixtures;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\resource\StaticResource;
use watoki\curir\Resource;
use watoki\scrut\Fixture;

/**
 * @property FileFixture file <-
 */
class ResourceFixture extends Fixture {

    /** @var Response */
    private $response;

    /** @var null|\Exception */
    private $caught;

    /** @var \watoki\curir\Resource */
    private $resource;

    public function givenTheDynamicResource($resource) {
        eval("class $resource extends \\watoki\\curir\\resource\\DynamicResource {}");
        $this->resource = new $resource($this->file->tmp, $resource);
    }

    public function givenTheStaticResourceFor($file) {
        $this->resource = new StaticResource($this->file->tmp, $file);
    }

    public function whenIRequestAResponseFromThatResource() {
        $this->response = $this->resource->respond(new Request());
    }

    public function whenITryToRequestAResponseFromThatResource() {
        try {
            $this->whenIRequestAResponseFromThatResource();
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    public function thenTheRequestShouldFailWith($string) {
        $this->spec->assertNotNull($this->caught, 'No Exception caught.');
        $this->spec->assertContains($string, $this->caught->getMessage());
    }

    public function thenTheResponseShouldHaveTheContentType($mime) {
        $this->spec->assertEquals($mime, $this->response->getHeaders()->get(Response::HEADER_CONTENT_TYPE));
    }

    public function thenTheResponseShouldHaveTheBody($body) {
        $this->spec->assertEquals($body, $this->response->getBody());
    }
}