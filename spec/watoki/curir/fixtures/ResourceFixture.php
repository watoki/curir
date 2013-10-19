<?php
namespace spec\watoki\curir\fixtures;

use watoki\curir\http\Path;
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
            $this->request->setFormat('json');
        }
        return $this->request;
    }

    public function givenTheDynamicResource($resource) {
        $this->givenTheDynamicResource_WithTheBody($resource, '');
    }

    public function givenTheDynamicResource_WithTheBody($resource, $body) {
        $this->givenTheDynamicResource_In_WithTheBody($resource, '', $body);
    }

    public function givenTheDynamicResource_In_WithTheBody($name, $dir, $body) {
        $this->givenThe__In_WithTheBody('DynamicResource', $name, $dir, $body);
    }

    public function givenTheStaticResourceFor($file) {
        $this->resource = new StaticResource($this->file->tmp, $file);
    }

    public function givenTheContainer($containerName) {
        $this->givenTheContainer_WithTheBody($containerName, '');
    }

    public function givenTheContainer_WithTheBody($name, $body) {
        $this->givenThe__In_WithTheBody('Container', $name, '', $body);
    }

    public function givenTheContainer_In($name, $dir) {
        $this->givenThe__In_WithTheBody('Container', $name, $dir, '');
    }

    private function givenThe__In_WithTheBody($baseClass, $name, $dir, $body) {
        $file = $dir . DIRECTORY_SEPARATOR . $name . '.php';
        $namespace = str_replace(array('/', '\\'), '\\', $dir);
        $namespaceStatement = $dir ? 'namespace ' . $namespace . ';' : '';

        $this->file->givenTheFile_WithTheContent($file, "<?php
            $namespaceStatement
            class $name extends \\watoki\\curir\\resource\\$baseClass {
                $body
            }");

        /** @noinspection PhpIncludeInspection */
        require_once($this->file->getFullPathOf($file));

        $this->resource = $this->spec->factory->getInstance($namespace . '\\' . $name, array(
            'directory' => rtrim($this->file->tmp . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR) . $dir,
            'name' => $name
        ));
    }

    public function givenThePresenter($presenterName) {
        eval('class ' . $presenterName . ' extends \watoki\curir\responder\Presenter {
            public function renderTest($template) {
                foreach ($this->getModel() as $key => $value) {
                    $template = str_replace("%{$key}%", $value, $template);
                }
                return $template;
            }
        }');
    }

    public function givenTheRequestParameter_Is($key, $value) {
        $this->getRequest()->getParameters()->set($key, $value);
    }

    public function givenTheRequestHasTheTarget($target) {
        $this->getRequest()->setTarget(Path::parse($target));
    }

    public function whenISendTheRequestToThatResource() {
        $this->response = $this->resource->respond($this->getRequest());
    }

    public function whenITryToSendTheRequestToThatResource() {
        try {
            $this->whenISendTheRequestToThatResource();
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