<?php
namespace spec\watoki\curir\fixtures;

use watoki\curir\http\error\HttpError;
use watoki\curir\http\Path;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\resource\StaticResource;
use watoki\curir\Resource;
use watoki\factory\Factory;
use watoki\scrut\Fixture;
use watoki\scrut\Specification;

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

    public function __construct(Specification $spec, Factory $factory) {
        parent::__construct($spec, $factory);
        if (!class_exists('TestPresenter')) {
            eval('class TestPresenter extends \watoki\curir\responder\Presenter {
                public function renderHtml($template) {
                    return $template;
                }

                public function renderJson() {
                    return json_encode($this->getModel());
                }

                public function renderTest($template) {
                    foreach ($this->getModel() as $key => $value) {
                        $template = str_replace("%{$key}%", $value, $template);
                    }
                    return $template;
                }
            }');
        }
    }

    private function getRequest() {
        if (!$this->request) {
            $this->request = new Request(new Path());
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
        $this->givenThe__In_WithTheBody('\\watoki\\curir\\resource\\DynamicResource', $name, $dir, $body);
    }

    public function givenTheStaticResourceFor($file) {
        $this->resource = new StaticResource(Url::parse($file), null, $this->file->tmp . DIRECTORY_SEPARATOR . $file);
    }

    public function givenTheContainer($containerName) {
        $this->givenTheContainer_WithTheBody($containerName, '');
    }

    public function givenTheContainer_WithTheBody($name, $body) {
        $this->givenTheContainer_In_WithTheBody($name, '', $body);
    }

    public function givenTheContainer_In($name, $dir) {
        $this->givenTheContainer_In_WithTheBody($name, $dir, '');
    }

    public function givenTheContainer_In_WithTheBody($name, $dir, $body) {
        $this->givenThe__In_WithTheBody('\\watoki\\curir\\resource\\Container', $name, $dir, $body);
    }

    public function givenTheContainer_In_Extending($name, $dir, $base) {
        $this->givenThe__In_WithTheBody($base, $name, $dir, '');
    }

    private function givenThe__In_WithTheBody($baseClass, $name, $dir, $body) {
        $class = $name . 'Resource';
        $file = $dir . DIRECTORY_SEPARATOR . $class . '.php';
        $namespace = str_replace(array('/', '\\'), '\\', $dir);
        $namespaceStatement = $dir ? 'namespace ' . $namespace . ';' : '';

        $this->file->givenTheFile_WithTheContent($file, "<?php
            $namespaceStatement
            class $class extends $baseClass {
                $body
            }");

        /** @noinspection PhpIncludeInspection */
        require_once($this->file->getFullPathOf($file));

        $this->resource = $this->spec->factory->getInstance($namespace . '\\' . $class, array(
            'directory' => $this->file->tmp . DIRECTORY_SEPARATOR . $dir,
            'url' => Url::parse('http://localhost'),
            'name' => $name,
            'parent' => null
        ));
    }

    public function givenThePresenter($presenterName) {
        eval('class ' . $presenterName . ' extends \watoki\curir\responder\Presenter {
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

    public function thenTheRequestShouldReturnTheStatus($string) {
        $this->spec->assertNotNull($this->caught, 'No Exception caught.');
        if (!$this->caught instanceof HttpError) {
            $this->spec->fail("Exception is not an HttpError");
        }
        $this->spec->assertContains($string, $this->caught->getStatus());
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