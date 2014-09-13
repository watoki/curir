<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use spec\watoki\curir\IntroductionTest_TestDelivery as WebDelivery;
use spec\watoki\deli\fixtures\TestDelivery;
use watoki\curir\delivery\WebRequest;
use watoki\curir\protocol\Url;
use watoki\deli\Request;
use watoki\deli\router\DynamicRouter;
use watoki\deli\Router;
use watoki\deli\router\NoneRouter;
use watoki\deli\target\CallbackTarget;
use watoki\deli\target\RespondingTarget;
use watoki\factory\Factory;
use watoki\scrut\Specification;

/**
 * **Start Here**
 *
 * @property WebRequestBuilderFixture request <-
*/
class IntroductionTest extends Specification {

    function testQuickStart() {
        /**
         * The first thing you need in order to use *curir* is to forward all HTTP requests
         * to a single file (e.g. `index.php`) with the target path in the query as `$_REQUEST['-']`. For apache
         * it would look like
         * <a href="javascript:" onclick="$('#htaccess').toggle();">this</a>
         * <div id="htaccess" style="display: none;">
         * <code>
         * # content of .htaccess
         * RewriteEngine On
         * RewriteBase /
         * RewriteRule ^(.*)$ index.php?-=$1 [L,QSA]
         * </code>
         * </div>
         */

        /**
         * You can then easily route all requests to a class implementing `Responding`, e.g. like
         * <a href="javascript:" onclick="$('#myResource').toggle();">this</a>
         * <div id="myResource" style="display: none;">
         */
        eval('
            use \watoki\deli\Responding;
            use \watoki\deli\Request;
            use \watoki\curir\delivery\WebResponse;

            class MyResource implements Responding {
                public function respond(Request $request) {
                    return "Hello World";
                }
            }
        ');
        // </div>

        /**
         * with this line in your `index.php`
         */
        WebDelivery::quickStart('MyResource');

        $this->thenTheResponseShouldBe('Hello World');

        /**
         * Or if you think creating a whole file to return "Hello World" is a little over-engineered, you can
         * use a `DynamicRouter` to map incoming URLs to anything.
         */
        $router = new DynamicRouter();
        $router->setPath('hello', CallbackTarget::factory(function () {
            return "Hello stranger";
        }));

        /**
         * You can also use placeholders which will the set as request arguments
         */
        $router->setPath('hello/{name}', CallbackTarget::factory(function (WebRequest $request) {
            return "Hello " . $request->getArguments()->get('name');
        }));

        /**
         * And you can route to objects as well
         */
        $respondingClass = 'MyResource';
        $router->setPath('my', RespondingTarget::factory($this->factory, new $respondingClass));

        /**
         * To get the whole routing and delivering going just call
         */
        WebDelivery::quickRoute($router);

        /**
         * Let's give it a spin
         */

        $this->givenTheTargetIs('hello');
        WebDelivery::quickRoute($router);
        $this->thenTheResponseShouldBe('Hello stranger');

        $this->givenTheTargetIs('hello/Joe');
        WebDelivery::quickRoute($router);
        $this->thenTheResponseShouldBe('Hello Joe');

        $this->givenTheTargetIs('my/anything');
        WebDelivery::quickRoute($router);
        $this->thenTheResponseShouldBe('Hello World');
    }

    protected function background() {
        // We need to disable actual delivery so we don't get a bunch of output while executing this Specification
        $this->disableActualDelivery();
    }

    private function disableActualDelivery() {
        WebDelivery::$factory = $this->factory;
        WebDelivery::$test = new TestDelivery($this->request->whenIBuildTheRequest());
    }

    private function givenTheTargetIs($string) {
        $this->request->givenTheTargetPathIs($string);
        WebDelivery::$test = new TestDelivery($this->request->whenIBuildTheRequest());
    }

    private function thenTheResponseShouldBe($string) {
        $this->assertEquals($string, WebDelivery::$test->response);
    }

}

/**
 * Use this class instead of the real WebDelivery to avoid echoing a bunch of stuff during test execution
 */
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
class IntroductionTest_TestDelivery extends \watoki\curir\WebDelivery {

    /** @var Factory */
    public static $factory;

    /** @var TestDelivery */
    public static $test;

    public static function quickStart($rootResourceClass, Factory $factory = null) {
        $targetFactory = RespondingTarget::factory(self::$factory, self::$factory->getInstance($rootResourceClass));
        self::quickRoute(new NoneRouter($targetFactory));
    }

    public static function quickRoute(Router $router) {
        $delivery = new WebDelivery($router, Url::fromString('http://example.com'), self::$test, self::$test);
        try {
            $delivery->run();
        } catch (\Exception $e) {
            // Don't mind routing exceptions
        }
    }

    protected function error(Request $request, \Exception $exception) {
        throw $exception;
    }
}