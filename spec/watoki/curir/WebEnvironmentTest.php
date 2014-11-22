<?php
namespace spec\watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\WebEnvironment;
use watoki\scrut\Specification;

/**
 * The job of the `WebEnvironment` is to determine context, target, arguments, etc. from the environment (aka. $_SERVER and $_REQUEST).
 *
 * The only thing I can't test here is getBody() since that one reads directly from stdin.
 *
 * Determining the context and the target are somewhat tricky since different server configurations lead to different
 * environment variables. The combinations I've discovered so far are listed in `environmentVariableCombinations`.
 */
class WebEnvironmentTest extends Specification {

    protected function background() {
        $this->givenTheServerEntry_WithValue('SERVER_PORT', 80);
        $this->givenTheServerEntry_WithValue('SERVER_NAME', 'example.com');
        $this->givenTheServerEntry_WithValue('SCRIPT_FILENAME', '/home/some/place/some/index.php');
        $this->givenTheServerEntry_WithValue('SCRIPT_NAME', '');
        $this->givenTheServerEntry_WithValue('REQUEST_URI', '');
    }

    function testTranslateHeaders() {
        $this->givenTheServerEntry_WithValue('HTTP_ACCEPT', 'everything');
        $this->givenTheServerEntry_WithValue('HTTP_ACCEPT_CHARSET', 'nothing');

        $this->whenICreateTheEnvironment();
        $this->thenHeader_ShouldBe(WebRequest::HEADER_ACCEPT, 'everything');
        $this->thenHeader_ShouldBe(WebRequest::HEADER_ACCEPT_CHARSET, 'nothing');
        $this->thenThereShouldBeNoHeader(WebRequest::HEADER_PRAGMA);
    }

    function testDetermineRequestMethod() {
        $this->givenTheServerEntry_WithValue('REQUEST_METHOD', 'GET');

        $this->whenICreateTheEnvironment();
        $this->thenTheRequestMethodShouldBe('get');
    }

    function testReadQueryArguments() {
        $this->givenTheQueryArgument_WithValue('foo', 'bar');
        $this->givenTheQueryArgument_WithValue('a', array('b',
            'c' => array('d')));

        $this->whenICreateTheEnvironment();
        $this->thenTheArgument_ShouldBe('foo', 'bar');
        $this->thenTheArgument_ShouldBe('a', array('b',
            'c' => array('d')));
    }

    public function testDetermineTarget() {
        /** @noinspection PhpUnusedParameterInspection */
        $this->runEnvironmentVariableCombinations(function ($env, $c, $target) {
            $this->givenTheServerEntriesAccordingTo($env);
            $this->whenICreateTheEnvironment();
            $this->thenTheTargetShouldBe($target);
        });
    }

    public function testDetermineContext() {
        $this->runEnvironmentVariableCombinations(function ($env, $context) {
            $this->givenTheServerEntriesAccordingTo($env);
            $this->whenICreateTheEnvironment();
            $this->thenTheContextShouldBe($context);
        });
    }

    public function testSortUploadedFiles() {
        $this->givenTheFile_WithValue('foo', array(
            'name' => array('foo.txt', 'bar.html'),
            'type' => array('text/plain', 'text/html'),
            'tmp_name' => array('/tmp/phpYzdqkD', '/tmp/phpeEwEWG'),
            'error' => array(0, 0),
            'size' => array(123, 456)
        ));

        $this->whenICreateTheEnvironment();
        $this->thenTheFilesShouldBe(array('foo' => array(
            array(
                'name' => 'foo.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/phpYzdqkD',
                'error' => 0,
                'size' => 123
            ),
            array(
                'name' => 'bar.html',
                'type' => 'text/html',
                'tmp_name' => '/tmp/phpeEwEWG',
                'error' => 0,
                'size' => 456
            ),
        )));
    }

    public function testSortNamedUploadedFiles() {
        $this->givenTheFile_WithValue('foo', array(
            'name' => array('one' => 'foo.txt', 'two' => 'bar.html'),
            'type' => array('one' => 'text/plain', 'two' => 'text/html'),
            'tmp_name' => array('one' => '/tmp/phpYzdqkD', 'two' => '/tmp/phpeEwEWG'),
            'error' => array('one' => 0, 'two' => 0),
            'size' => array('one' => 123, 'two' => 456)
        ));

        $this->whenICreateTheEnvironment();
        $this->thenTheFilesShouldBe(array('foo' => array(
            'one' => array(
                'name' => 'foo.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/phpYzdqkD',
                'error' => 0,
                'size' => 123
            ),
            'two' => array(
                'name' => 'bar.html',
                'type' => 'text/html',
                'tmp_name' => '/tmp/phpeEwEWG',
                'error' => 0,
                'size' => 456
            ),
        )));
    }

    public function testUploadedFile() {
        $this->givenTheFile_WithValue('foo', array(
            'name' => 'foo.txt',
            'type' => 'text/plain',
            'tmp_name' => '/tmp/phpYzdqkD',
            'error' => 0,
            'size' => 123
        ));

        $this->whenICreateTheEnvironment();
        $this->thenTheFilesShouldBe(array('foo' =>
            array(
                'name' => 'foo.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/phpYzdqkD',
                'error' => 0,
                'size' => 123
            )
        ));
    }

    ######################################################################################################

    public function givenTheServerEntriesAccordingTo($env) {
        foreach ($env as $key => $value) {
            $this->givenTheServerEntry_WithValue($key, $value);
        }
    }

    function environmentVariableCombinations() {
        return array(

            'dev server with route script' => array(
                'localhost/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php',
                    ),
                    'out' => array(
                        'context' => '',
                        'target' => ''
                    )
                ),
                'localhost/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/hello/world?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php/hello/world',
                    ),
                    'out' => array(
                        'context' => '',
                        'target' => 'hello/world'
                    )
                ),
                'localhost/hello/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/hello/?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php/hello/',
                    ),
                    'out' => array(
                        'context' => '',
                        'target' => 'hello/'
                    )
                ),
                'localhost/hello/world.html?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/hello/world.html?foo',
                        'SCRIPT_NAME' => '/hello/world.html',
                        'PHP_SELF' => '/hello/world.html',
                    ),
                    'out' => array(
                        'context' => '',
                        'target' => 'hello/world.html'
                    )
                ),
            ),

            'dev server without route script' => array(
                'localhost/index.php/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/index.php/?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php/',
                    ),
                    'out' => array(
                        'context' => '/index.php',
                        'target' => ''
                    )
                ),
                'localhost/index.php/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/index.php/hello/world?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php/hello/world',
                    ),
                    'out' => array(
                        'context' => '/index.php',
                        'target' => 'hello/world'
                    )
                ),
                'localhost/index.php/hello/world.html?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/index.php/hello/world.html?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php/hello/world.html',
                    ),
                    'out' => array(
                        'context' => '/index.php',
                        'target' => 'hello/world.html'
                    )
                ),
                'localhost/bar/index.php/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/bar/index.php/?foo',
                        'SCRIPT_NAME' => '/bar/index.php',
                        'PHP_SELF' => '/bar/index.php/',
                    ),
                    'out' => array(
                        'context' => '/bar/index.php',
                        'target' => ''
                    )
                ),
                'localhost/bar/index.php/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/bar/index.php/hello/world?foo',
                        'SCRIPT_NAME' => '/bar/index.php',
                        'PHP_SELF' => '/bar/index.php/hello/world',
                    ),
                    'out' => array(
                        'context' => '/bar/index.php',
                        'target' => 'hello/world'
                    )
                ),
            ),

            'apache alias with rewrite' => array(
                'localhost/xkdl/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/xkdl/?foo',
                        'SCRIPT_NAME' => '/xkdl/index.php',
                        'PHP_SELF' => '/xkdl/index.php',
                    ),
                    'out' => array(
                        'context' => '/xkdl',
                        'target' => ''
                    )
                ),
                'localhost/xkdl/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/xkdl/hello/world?foo',
                        'SCRIPT_NAME' => '/xkdl/index.php',
                        'PHP_SELF' => '/xkdl/index.php/hello/world',
                    ),
                    'out' => array(
                        'context' => '/xkdl',
                        'target' => 'hello/world'
                    )
                ),
            ),

            'apache alias without rewrite' => array(
                'localhost/xkdl/index.php/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/xkdl/index.php/?foo',
                        'SCRIPT_NAME' => '/xkdl/index.php',
                        'PHP_SELF' => '/xkdl/index.php/',
                    ),
                    'out' => array(
                        'context' => '/xkdl/index.php',
                        'target' => ''
                    )
                ),
                'localhost/xkdl/index.php/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/xkdl/index.php/hello/world?foo',
                        'SCRIPT_NAME' => '/xkdl/index.php',
                        'PHP_SELF' => '/xkdl/index.php/hello/world',
                    ),
                    'out' => array(
                        'context' => '/xkdl/index.php',
                        'target' => 'hello/world'
                    )
                ),
            ),

            'apache virtual host with rewrite' => array(
                'test.localhost' => array(
                    'in' => array(
                        'REQUEST_URI' => '/',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php',
                    ),
                    'out' => array(
                        'context' => '',
                        'target' => ''
                    )
                ),
                'test.localhost/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php',
                    ),
                    'out' => array(
                        'context' => '',
                        'target' => ''
                    )
                ),
                'test.localhost/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/hello/world?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php/hello/world',
                    ),
                    'out' => array(
                        'context' => '',
                        'target' => 'hello/world'
                    )
                ),
            ),

            'apache virtual host without rewrite' => array(
                'localhost/index.php/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/index.php/?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php/',
                    ),
                    'out' => array(
                        'context' => '/index.php',
                        'target' => ''
                    )
                ),
                'localhost/index.php/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/index.php/hello/world?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php/hello/world',
                    ),
                    'out' => array(
                        'context' => '/index.php',
                        'target' => 'hello/world'
                    )
                ),
                'localhost/bar/index.php/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/bar/index.php/?foo',
                        'SCRIPT_NAME' => '/bar/index.php',
                        'PHP_SELF' => '/bar/index.php/',
                    ),
                    'out' => array(
                        'context' => '/bar/index.php',
                        'target' => ''
                    )
                ),
                'localhost/bar/index.php/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/bar/index.php/hello/world?foo',
                        'SCRIPT_NAME' => '/bar/index.php',
                        'PHP_SELF' => '/bar/index.php/hello/world',
                    ),
                    'out' => array(
                        'context' => '/bar/index.php',
                        'target' => 'hello/world'
                    )
                ),
            ),

            'nginx with rewrite' => array(
                'localhost' => array(
                    'in' => array(
                        'REQUEST_URI' => '/',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php',
                    ),
                    'out' => array(
                        'context' => '',
                        'target' => ''
                    )
                ),
                'localhost/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php',
                    ),
                    'out' => array(
                        'context' => '',
                        'target' => ''
                    )
                ),
                'localhost/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/hello/world?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php',
                    ),
                    'out' => array(
                        'context' => '',
                        'target' => 'hello/world'
                    )
                ),
            ),

            'nginx without rewrite' => array(
                'localhost/index.php/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/index.php/?foo',
                        'SCRIPT_NAME' => '/index.php/index.php',
                        'PHP_SELF' => '/index.php/index.php',
                    ),
                    'out' => array(
                        'context' => '/index.php',
                        'target' => ''
                    )
                ),
                'localhost/index.php/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/index.php/hello/world?foo',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php',
                    ),
                    'out' => array(
                        'context' => '/index.php',
                        'target' => 'hello/world'
                    )
                ),
                'localhost/bar/index.php/?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/bar/index.php/?foo',
                        'SCRIPT_NAME' => '/bar/index.php/index.php',
                        'PHP_SELF' => '/bar/index.php/index.php',
                    ),
                    'out' => array(
                        'context' => '/bar/index.php',
                        'target' => ''
                    )
                ),
                'localhost/bar/index.php/hello/world?foo' => array(
                    'in' => array(
                        'REQUEST_URI' => '/bar/index.php/hello/world?foo',
                        'SCRIPT_NAME' => '/bar/index.php',
                        'PHP_SELF' => '/bar/index.php',
                    ),
                    'out' => array(
                        'context' => '/bar/index.php',
                        'target' => 'hello/world'
                    )
                ),
            ),
        );
    }

    public function runEnvironmentVariableCombinations($callable) {
        $failures = array();
        $total = 0;
        foreach ($this->environmentVariableCombinations() as $name => $configuration) {
            foreach ($configuration as $num => $fixture) {
                $total++;
                try {
                    $this->setUp();
                    $this->background();
                    call_user_func($callable, $fixture['in'], $fixture['out']['context'], $fixture['out']['target']);
                } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
                    $cf = $e->getComparisonFailure();
                    $failures[] = $name . ':' . $num . ' - Expected [' . $cf->getExpectedAsString() . '], got [' . $cf->getActualAsString() . ']';
                }
            }
        }
        if ($failures) {
            $this->fail(count($failures) . "/" . $total . " failed: [\n\t" . implode(",\n\t", $failures));
        }
    }

    #############################################################################################

    /** @var WebEnvironment */
    private $env;

    private $_SERVER;

    private $_REQUEST;

    private $_FILES;

    protected function setUp() {
        parent::setUp();
        $this->_SERVER = array();
        $this->_REQUEST = array();
        $this->_FILES = array();
    }

    private function givenTheServerEntry_WithValue($key, $value) {
        $this->_SERVER[$key] = $value;
    }

    private function whenICreateTheEnvironment() {
        $this->env = new WebEnvironment($this->_SERVER, $this->_REQUEST, $this->_FILES);
    }

    private function thenHeader_ShouldBe($key, $value) {
        $this->assertEquals($value, $this->env->getHeaders()->get($key));
    }

    private function thenThereShouldBeNoHeader($key) {
        $this->assertFalse($this->env->getHeaders()->has($key));
    }

    private function thenTheRequestMethodShouldBe($string) {
        $this->assertEquals($string, $this->env->getRequestMethod());
    }

    private function givenTheQueryArgument_WithValue($key, $value) {
        $this->_REQUEST[$key] = $value;
    }

    private function givenTheFile_WithValue($key, $value) {
        $this->_FILES[$key] = $value;
    }

    private function thenTheArgument_ShouldBe($key, $value) {
        $this->assertEquals($value, $this->env->getArguments()->get($key));
    }

    private function thenTheContextShouldBe($context) {
        $this->assertEquals('http://example.com' . $context, $this->env->getContext()->toString());
    }

    private function thenTheTargetShouldBe($target) {
        $this->assertEquals($target, $this->env->getTarget()->toString());
    }

    private function thenTheFilesShouldBe($array) {
        $this->assertEquals($array, $this->env->getFiles()->toArray());
    }

} 