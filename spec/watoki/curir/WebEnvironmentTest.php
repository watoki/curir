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

    public function givenTheServerEntriesAccordingTo($env) {
        foreach ($env as $key => $value) {
            $this->givenTheServerEntry_WithValue($key, $value);
        }
    }

    function environmentVariableCombinations() {
        return array(
                'built-in with route script' => array(
                        array(
                                'out' => array(
                                        'context' => '',
                                        'target' => ''),
                                'in' => array(
                                        'REQUEST_URI' => '/?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php',
                                        )),
                        array(
                                'out' => array(
                                        'context' => '',
                                        'target' => 'hello/there'),
                                'in' => array(
                                        'REQUEST_URI' => '/hello/there?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php/hello/there',
                                        'PATH_INFO' => '/hello/there')),
                        array(
                                'out' => array(
                                        'context' => '',
                                        'target' => 'hello/there.html'),
                                'in' => array(
                                        'REQUEST_URI' => '/hello/there.html?you',
                                        'SCRIPT_NAME' => '/hello/there.html',
                                        'PHP_SELF' => '/hello/there.html',
                                        )),
                ),
                'built-in without route script' => array(
                        array(
                                'out' => array(
                                        'context' => '/index.php',
                                        'target' => ''),
                                'in' => array(
                                        'REQUEST_URI' => '/index.php?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php',
                                        )),
                        array(
                                'out' => array(
                                        'context' => '/index.php',
                                        'target' => ''),
                                'in' => array(
                                        'REQUEST_URI' => '/index.php/?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php/',
                                        'PATH_INFO' => '/')),
                        array(
                                'out' => array(
                                        'context' => '/index.php',
                                        'target' => 'hello/there'),
                                'in' => array(
                                        'REQUEST_URI' => '/index.php/hello/there?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php/hello/there',
                                        'PATH_INFO' => '/hello/there')),
                        array(
                                'out' => array(
                                        'context' => '/index.php',
                                        'target' => 'hello/there.html'),
                                'in' => array(
                                        'REQUEST_URI' => '/index.php/hello/there.html?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php/hello/there.html',
                                        'PATH_INFO' => '/hello/there.html')),
                ),
                'apache alias with rewrite' => array(
                        array(
                                'out' => array(
                                        'context' => '/xkdl',
                                        'target' => ''),
                                'in' => array(
                                        'REQUEST_URI' => '/xkdl/?you',
                                        'SCRIPT_NAME' => '/xkdl/index.php',
                                        'PHP_SELF' => '/xkdl/index.php',
                                        )),
                        array(
                                'out' => array(
                                        'context' => '/xkdl',
                                        'target' => 'hello/there'),
                                'in' => array(
                                        'REQUEST_URI' => '/xkdl/hello/there?you',
                                        'SCRIPT_NAME' => '/xkdl/index.php',
                                        'PHP_SELF' => '/xkdl/index.php/hello/there',
                                        'PATH_INFO' => '/hello/there')),
                        array(
                                'out' => array(
                                        'context' => '/xkdl',
                                        'target' => 'hello/there.html'),
                                'in' => array(
                                        'REQUEST_URI' => '/xkdl/hello/there.html?you',
                                        'SCRIPT_NAME' => '/xkdl/index.php',
                                        'PHP_SELF' => '/xkdl/index.php/hello/there.html',
                                        'PATH_INFO' => '/hello/there.html')),
                ),
                'apache virtual host with rewrite' => array(
                        array(
                                'out' => array(
                                        'context' => '',
                                        'target' => ''),
                                'in' => array(
                                        'REQUEST_URI' => '/?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php',
                                        )),
                        array(
                                'out' => array(
                                        'context' => '',
                                        'target' => 'hello/there'),
                                'in' => array(
                                        'REQUEST_URI' => '/hello/there?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php/hello/there',
                                        'PATH_INFO' => '/hello/there')),
                        array(
                                'out' => array(
                                        'context' => '',
                                        'target' => 'hello/there.html'),
                                'in' => array(
                                        'REQUEST_URI' => '/hello/there.html?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php/hello/there.html',
                                        'PATH_INFO' => '/hello/there.html')),
                ),
                'apache virtual host without rewrite' => array(
                        array(
                                'out' => array(
                                        'context' => '/index.php',
                                        'target' => ''),
                                'in' => array(
                                        'REQUEST_URI' => '/index.php?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php',
                                        )),
                        array(
                                'out' => array(
                                        'context' => '/index.php',
                                        'target' => ''),
                                'in' => array(
                                        'REQUEST_URI' => '/index.php/?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php/hello/there',
                                        'PATH_INFO' => '/')),
                        array(
                                'out' => array(
                                        'context' => '/index.php',
                                        'target' => 'hello/there'),
                                'in' => array(
                                        'REQUEST_URI' => '/index.php/hello/there?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php/hello/there',
                                        'PATH_INFO' => '/hello/there')),
                        array(
                                'out' => array(
                                        'context' => '/index.php',
                                        'target' => 'hello/there.html'),
                                'in' => array(
                                        'REQUEST_URI' => '/index.php/hello/there.html?you',
                                        'SCRIPT_NAME' => '/index.php',
                                        'PHP_SELF' => '/index.php/hello/there.html',
                                        'PATH_INFO' => '/hello/there.html')),
                ),
                'apache alias without rewrite' => array(
                        array(
                                'out' => array(
                                        'context' => '/xkdl/index.php',
                                        'target' => ''),
                                'in' => array(
                                        'REQUEST_URI' => '/xkdl/index.php?you',
                                        'SCRIPT_NAME' => '/xkdl/index.php',
                                        'PHP_SELF' => '/xkdl/index.php',
                                        )),
                        array(
                                'out' => array(
                                        'context' => '/xkdl/index.php',
                                        'target' => ''),
                                'in' => array(
                                        'REQUEST_URI' => '/xkdl/index.php/?you',
                                        'SCRIPT_NAME' => '/xkdl/index.php',
                                        'PHP_SELF' => '/xkdl/index.php/ ',
                                        'PATH_INFO' => '/')),
                        array(
                                'out' => array(
                                        'context' => '/xkdl/index.php',
                                        'target' => 'hello/there'),
                                'in' => array(
                                        'REQUEST_URI' => '/xkdl/index.php/hello/there?you',
                                        'SCRIPT_NAME' => '/xkdl/index.php',
                                        'PHP_SELF' => '/xkdl/index.php/hello/there',
                                        'PATH_INFO' => '/hello/there')),
                        array(
                                'out' => array(
                                        'context' => '/xkdl/index.php',
                                        'target' => 'hello/there.html'),
                                'in' => array(
                                        'REQUEST_URI' => '/xkdl/index.php/hello/there.html?you',
                                        'SCRIPT_NAME' => '/xkdl/index.php',
                                        'PHP_SELF' => '/xkdl/index.php/hello/there.html',
                                        'PATH_INFO' => '/hello/there.html')),
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

    protected function setUp() {
        parent::setUp();
        $this->_SERVER = array();
        $this->_REQUEST = array();
    }

    private function givenTheServerEntry_WithValue($key, $value) {
        $this->_SERVER[$key] = $value;
    }

    private function whenICreateTheEnvironment() {
        $this->env = new WebEnvironment($this->_SERVER, $this->_REQUEST);
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

    private function thenTheArgument_ShouldBe($key, $value) {
        $this->assertEquals($value, $this->env->getArguments()->get($key));
    }

    private function thenTheContextShouldBe($context) {
        $this->assertEquals('http://example.com' . $context, $this->env->getContext()->toString());
    }

    private function thenTheTargetShouldBe($target) {
        $this->assertEquals($target, $this->env->getTarget()->toString());
    }

} 