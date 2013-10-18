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

    /** @var Request|null */
    private $request;

    private function getRequest() {
        if (!$this->request) {
            $this->request = new Request();
            $this->request->setFormat('none');
        }
        return $this->request;
    }

    public function givenTheDynamicResource($resource) {
        $this->givenTheDynamicResource_WithTheBody($resource, '');
    }

    public function givenTheDynamicResource_WithTheBody($resource, $body) {
        $file = $resource . '.php';
        $this->file->givenTheFile_WithTheContent($file, "<?php
            class $resource extends \\watoki\\curir\\resource\\DynamicResource {
                $body
            }");

        /** @noinspection PhpIncludeInspection */
        require_once($this->file->getFullPathOf($file));

        $this->resource = $this->spec->factory->getInstance($resource, array(
            'directory' => substr($this->file->tmp, 0, -1),
            'name' => $resource
        ));
    }

    public function givenTheStaticResourceFor($file) {
        $this->resource = new StaticResource($this->file->tmp, $file);
    }

    public function givenATestRenderer($rendererName) {
        eval('class ' . $rendererName . ' implements \\watoki\\curir\\Renderer {
            public function needsTemplate() {
                return true;
            }
            public function render($template, $model) {
                foreach ($model as $key => $value) {
                    $template = str_replace("%{$key}%", $value, $template);
                }
                return $template;
            }
        }');
    }

    public function whenIRequestAResponseFromThatResource() {
        $this->response = $this->resource->respond($this->getRequest());
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

    public function givenIRequestTheFormat($format) {
        $this->getRequest()->setFormat($format);
    }

    public function thenIShouldBeRedirectedTo($target) {
        $this->spec->assertTrue($this->response->getHeaders()->has(Response::HEADER_LOCATION), 'No Location header set');
        $this->spec->assertEquals($target, $this->response->getHeaders()->get(Response::HEADER_LOCATION));
    }
}