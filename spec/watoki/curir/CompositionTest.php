<?php
namespace spec\watoki\curir;

use spec\watoki\curir\steps\Given;
use watoki\collections\Liste;
use watoki\curir\Request;

/**
 * @property CompositionTest_Given given
 */
class CompositionTest extends Test {

    protected function setUp() {
        parent::setUp();

        $this->given->aTestRenderer();
        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theRequestResourceIs('super.test');
    }

    function testIncludeSimple() {
        $this->given->theFolder_WithModule('snippet');
        $this->given->theComponent_In_WithTemplate('snippet\SubComponent', 'snippet', '%msg%!');
        $this->given->theSuperComponent_In_WithTheBody('snippet\SuperComponent', 'snippet', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'snippet', 'Hello %sub%');

        $this->when->iSendTheRequestTo('snippet\Module');

        $this->then->theResponseBodyShouldBe('Hello World!');
    }

    function testIncludeHtmlDocument() {
        $this->given->theFolder_WithModule('document');
        $this->given->theComponent_In_WithTemplate('document\SubComponent', 'document',
            '<html>
                <head><title>Sub Component</title></head>
                <body><b>%msg%</b></body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('document\SuperComponent', 'document', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'document', '<html><head></head><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('document\Module');

        $this->then->theResponseBodyShouldBe('<html><head></head><body>Hello <b>World</b></body></html>');
    }

    function testAbsorbHeader() {
        $this->given->theFolder_WithModule('assets');
        $this->given->theComponent_In_WithTemplate('assets\SubComponent', 'assets',
            '<html>
                <head>
                    <link href="http://twitter.github.com/bootstrap/assets/css/bootstrap.css" rel="stylesheet">
                </head>
                <body><i>%msg%</i></body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('assets\SuperComponent', 'assets', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'assets', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('assets\Module');

        $this->then->theResponseBodyShouldBe(
            '<html>
                <head>
                    <link href="http://twitter.github.com/bootstrap/assets/css/bootstrap.css" rel="stylesheet">
                </head>
                <body>Hello <i>World</i></body>
            </html>');
    }

    function testRelativeUrls() {
        $this->given->theFolder_WithModule('relative');
        $this->given->theFolder_WithModule('relative/inner');
        $this->given->theComponent_In_WithTemplate('relative\inner\SubComponent', 'relative/inner',
            '<html>
                <head>
                    <link href="relative/path/file.css" rel="stylesheet">
                </head>
                <body>
                    <p>%msg%</p>
                    <img src="also/relative.png">
                    <img src="/not/relative.png">
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('relative\SuperComponent', 'relative', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(inner\SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'relative', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('relative\Module');

        $this->then->theResponseBodyShouldBe(
            '<html>
                <head>
                    <link href="/base/inner/relative/path/file.css" rel="stylesheet">
                </head>
                <body>
                    Hello <p>World</p>
                    <img src="/base/inner/also/relative.png">
                    <img src="/not/relative.png">
                </body>
            </html>');
    }

    function testDeepLinkTarget() {
        $this->given->theFolder_WithModule('target');
        $this->given->theComponent_In_WithTemplate('target\SubComponent', 'target',
            '<html>
                <body>
                    <a href="top.html" target="_top">top</a>
                    <a href="self.html" target="_self">self</a>
                    <a href="blank.html" target="_blank">blank</a>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('target\SuperComponent', 'target', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'target', '<html><body>Links:  %sub%</body></html>');

        $this->when->iSendTheRequestTo('target\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <body>
                    Links:
                    <a href="/base/top.html">top</a>
                    <a href="/base/super.test?.[sub][~]=/base/self.html">self</a>
                    <a href="/base/blank.html" target="_blank">blank</a>
                </body>
            </html>');
    }

    function testDeepLinkReplacement() {
        $this->given->theFolder_WithModule('deeplink');
        $this->given->theComponent_In_WithTemplate('deeplink\SubComponent', 'deeplink',
            '<html>
                <body>
                    <a href="some/link.test?param1[map][map2]=val1&amp;param2=val2">%msg%</a>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('deeplink\SuperComponent', 'deeplink', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'deeplink', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('deeplink\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <body>
                    Hello <a href="/base/super.test?.[sub][~]=/base/some/link.test&.[sub][param1][map][map2]=val1&.[sub][param2]=val2">World</a>
                </body>
            </html>');
    }

    function testSubTarget() {
        $this->given->theFolder_WithModule('subtarget');
        $this->given->theComponent_In_WithTemplate('subtarget\NotComponent', 'subtarget', '<html><head></head><body>NOT</body></html>');

        $this->given->theFolder('subtarget/inner');
        $this->given->theComponent_In_WithTemplate('subtarget\inner\SubComponent', 'subtarget/inner',
            '<html><head></head><body>%msg%</body></html>');

        $this->given->theSuperComponent_In_WithTheBody('subtarget\SuperComponent', 'subtarget', '
        function doGet() {
            $sub = $this->subComponent(NotComponent::$CLASS);
            $sub->getRequest()->setResource(\watoki\curir\Path::parse($this->getBaseRoute()->toString() . "/inner/sub.test"));
            return array(
                "sub" => $sub
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'subtarget', '<html><body>Hello <a href="super.test">%sub%</a></body></html>');

        $this->when->iSendTheRequestTo('subtarget\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <body>
                    Hello <a href="super.test">World</a>
                </body>
            </html>');
    }

    function testReplaceFormFieldNames() {
        $this->given->theFolder_WithModule('formfields');
        $this->given->theComponent_In_WithTemplate('formfields\SubComponent', 'formfields',
            '<html>
                <body>
                    <form action="sub.test" method="post">
                        <input name="field[1]">
                        <textarea name="field[2]"></textarea>
                        <select name="field[3]"></select>
                    </form>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('formfields\SuperComponent', 'formfields', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'formfields', '<html><body>Hello  %sub%</body></html>');

        $this->when->iSendTheRequestTo('formfields\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <body>
                    Hello
                    <form action="/base/super.test?!=sub&.[sub][~]=/base/sub.test" method="post">
                        <input name=".[sub][field][1]">
                        <textarea name=".[sub][field][2]"></textarea>
                        <select name=".[sub][field][3]"></select>
                    </form>
                </body>
            </html>');
    }

    function testPrimaryAction() {
        $this->given->theFolder_WithModule('primaryactioncomp');
        $this->given->theComponent_In_WithTemplate('primaryactioncomp\SubComponent', 'primaryactioncomp',
            '<html>
                <body>
                    <form action="sub.test" method="post"></form>
                    <a href="sub.test?action=myAction">%msg%</a>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('primaryactioncomp\SuperComponent', 'primaryactioncomp', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'primaryactioncomp', '<html><body>Hello  %sub%</body></html>');

        $this->when->iSendTheRequestTo('primaryactioncomp\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <body>
                    Hello
                    <form action="/base/super.test?!=sub&.[sub][~]=/base/sub.test" method="post"></form>
                    <a href="/base/super.test?!=sub&.[sub][~]=/base/sub.test&.[sub][action]=myAction">World</a>
                </body>
            </html>');
    }

    function testOmitPrimaryRequest() {
        $this->given->theFolder_WithModule('omitprimary');
        $this->given->theComponent_In_WithTemplate('omitprimary\SubComponent', 'omitprimary',
            '<html>
                <body>
                    <form action="sub.test" method="get"></form>
                    <a href="sub.test">%msg%</a>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('omitprimary\SuperComponent', 'omitprimary', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'omitprimary', '<html><body>Hello  %sub%</body></html>');

        $this->when->iSendTheRequestTo('omitprimary\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <body>
                    Hello
                    <form action="/base/super.test" method="get"></form>
                    <a href="/base/super.test">World</a>
                </body>
            </html>');
    }

    function testDefaultRoute() {
        $this->given->theFolder_WithModule('defroute');
        $this->given->theComponent_In_WithTemplate('defroute\SubComponent', 'defroute',
            '<html>
                <body>
                    <a href="sub.test?param1=val1">%msg%</a>
                </body>
            </html>');
        $this->given->theSuperComponent_In_WithTheBody('defroute\SuperComponent', 'defroute', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'defroute', '<html><body>Hello %sub%</body></html>');

        $this->when->iSendTheRequestTo('defroute\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html>
                <body>
                    Hello <a href="/base/super.test?.[sub][param1]=val1">World</a>
                </body>
            </html>');
    }

    function testCollectState() {
        $this->given->theFolder_WithModule('colstate');
        $this->given->theComponent_In_WithTemplate('colstate\Sub1Component', 'colstate',
            '<html><body>Sub1:%msg%</body></html>');
        $this->given->theComponent_In_WithTemplate('colstate\Sub2Component', 'colstate',
            '<html><body><a href="sub2.test">Sub2</a>:%msg%</body></html>');
        $this->given->theSuperComponent_In_WithTheBody('colstate\SuperComponent', 'colstate', '
        function doGet() {
            $sub1 = $this->subComponent(Sub1Component::$CLASS);
            $sub1->getRequest()->getParameters()->set("param1", "val1");

            $sub2 = $this->subComponent(Sub2Component::$CLASS);
            return array(
                "sub1" => $sub1,
                "sub2" => $sub2
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'colstate', '<html><body>Hello %sub1%,  hello %sub2%</body></html>');

        $this->when->iSendTheRequestTo('colstate\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html><body>
                Hello Sub1:World,
                hello <a href="/base/super.test?.[sub1][param1]=val1">Sub2</a>:World
            </body></html>');
    }

    function testDefaultStateByConstructor() {
        $this->given->theFolder_WithModule('defbyconstr');
        $this->given->theComponent_In_WithTemplate('defbyconstr\Sub1Component', 'defbyconstr',
            '<html><body>Sub1:%msg%</body></html>');
        $this->given->theComponent_In_WithTemplate('defbyconstr\Sub2Component', 'defbyconstr',
            '<html><body><a href="sub2.test">Sub2</a>:%msg%</body></html>');
        $this->given->theSuperComponent_In_WithTheBody('defbyconstr\SuperComponent', 'defbyconstr', '
        function doGet() {
            $sub1 = $this->subComponent(Sub1Component::$CLASS,
                new \watoki\collections\Map(array("param1" => "val1", "param2" => "val2", "param3" => "val3")));
            $sub1->getRequest()->getParameters()->set("param1", "val1");
            $sub1->getRequest()->getParameters()->set("param2", "other");
            $sub1->getRequest()->getParameters()->set("param4", "new");

            $sub2 = $this->subComponent(Sub2Component::$CLASS);
            return array(
                "sub1" => $sub1,
                "sub2" => $sub2
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'defbyconstr', '<html><body>Hello %sub1%,  hello %sub2%</body></html>');

        $this->when->iSendTheRequestTo('defbyconstr\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html><body>
                Hello Sub1:World,
                hello <a href="/base/super.test?.[sub1][param2]=other&.[sub1][param4]=new">Sub2</a>:World
            </body></html>');
    }

    function testDefaultStateByAction() {
        $this->given->theFolder_WithModule('defaultargs');
        $this->given->theComponent_In_WithTheBody('defaultargs\Sub1Component', 'defaultargs', '
        function doGet($param1, $param2 = "default") {
            return array("msg" => $param1 . ":" . $param2);
        }');
        $this->given->theFile_In_WithContent('sub1.test', 'defaultargs', '<html><body>Sub1:%msg%</body></html>');

        $this->given->theComponent_In_WithTemplate('defaultargs\Sub2Component', 'defaultargs',
            '<html><body><a href="sub2.test">Sub2</a>:%msg%</body></html>');
        $this->given->theSuperComponent_In_WithTheBody('defaultargs\SuperComponent', 'defaultargs', '
        function doGet() {
            $sub1 = $this->subComponent(Sub1Component::$CLASS,
                new \watoki\collections\Map(array("param1" => "World")));
            $sub1->getRequest()->getParameters()->set("param2", "default");

            $sub2 = $this->subComponent(Sub2Component::$CLASS);
            return array(
                "sub1" => $sub1,
                "sub2" => $sub2
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'defaultargs', '<html><body>Hello %sub1%,  hello %sub2%</body></html>');

        $this->when->iSendTheRequestTo('defaultargs\Module');

        $this->then->theHtmlResponseBodyShouldBe(
            '<html><body>
                Hello Sub1:World:default,
                hello <a href="/base/super.test">Sub2</a>:World
            </body></html>');
    }

    function testSubInSubParameters() {
        $this->given->theFolder_WithModule('subsub');
        $this->given->theComponent_In_WithTemplate('subsub\Sub2Component', 'subsub',
            '<html><body><a href="sub2.test?a=b">Sub2</a>');
        $this->given->theSuperComponent_In_WithTheBody('subsub\Sub1Component', 'subsub', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(Sub2Component::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('sub1.test', 'subsub',
            '<html><body><a href="sub1.test?x=y">Sub1</a>  %sub%</body></html>');

        $this->given->theSuperComponent_In_WithTheBody('subsub\SuperComponent', 'subsub', '
        function doGet() {
            $sub = $this->subComponent(Sub1Component::$CLASS);
            $sub->getRequest()->getParameters()->set("p1", "v1");
            return array(
                "sub" => $sub
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'subsub',
            '<html><body><a href="super.test?i=k">Super</a>  %sub%</body></html>');

        $this->when->iSendTheRequestTo('subsub\Module');

        $this->then->theHtmlResponseBodyShouldBe('
        <html>
            <body>
                <a href="super.test?i=k">Super</a>
                <a href="/base/super.test?.[sub][x]=y">Sub1</a>
                <a href="/base/super.test?.[sub][p1]=v1&.[sub][.][sub][a]=b">Sub2</a>
            </body>
        </html>');
    }

    function testPrimaryRequestSubInSub() {
        $this->given->theFolder_WithModule('primarysubsub');
        $this->given->theComponent_In_WithTemplate('primarysubsub\Sub2Component', 'primarysubsub',
            '<html><body><a href="sub2?action=primary&amp;z=u">Sub2</a></body></html>');
        $this->given->theSuperComponent_In_WithTheBody('primarysubsub\Sub1Component', 'primarysubsub', '
        function doGet() {
            return array(
                "sub" => $this->subComponent(Sub2Component::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('sub1.test', 'primarysubsub',
            '<html><body>Sub1  %sub%</body></html>');

        $this->given->theSuperComponent_In_WithTheBody('primarysubsub\SuperComponent', 'primarysubsub', '
        function doGet() {
            $sub = $this->subComponent(Sub1Component::$CLASS);
            $sub->getRequest()->getParameters()->set("p", "v");
            return array(
                "sub" => $sub
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'primarysubsub',
            '<html><body>Super  %sub%</body></html>');

        $this->when->iSendTheRequestTo('primarysubsub\Module');

        $this->then->theHtmlResponseBodyShouldBe('
        <html>
            <body>
                Super
                Sub1
                <a href="/base/super.test
                    ?!=sub
                    &.[sub][~]=/base/Sub1&.[sub][!]=sub&.[sub][p]=v
                    &.[sub][.][sub][~]=/base/sub2&.[sub][.][sub][action]=primary&.[sub][.][sub][z]=u">Sub2</a>
            </body>
        </html>');
    }

    function testSubNestedInModel() {
        $this->given->theFolder_WithModule('nested');
        $this->given->theComponent_In_WithTemplate('nested\SubComponent', 'nested',
            '<html><body><a href="sub">Sub</a></body></html>');
        $this->given->theSuperComponent_In_WithTheBody('nested\SuperComponent', 'nested', '
        function doGet() {
            $item = $this->subComponent(SubComponent::$CLASS);
            $item->getRequest()->getParameters()->set("x", "y");
            return array(
                "list" => array(
                    array(
                        "item" => $item
                    )
                ),
                "sub" => $this->subComponent(SubComponent::$CLASS)
            );
        }');
        $this->given->theFile_In_WithContent('super.test', 'nested',
            '<html><body>%sub%</body></html>');

        $this->when->iSendTheRequestTo('nested\Module');

        $this->then->theHtmlResponseBodyShouldBe('<html>
            <body>
                <a href="/base/super.test?.[list.0.item][x]=y">Sub</a>
            </body>
        </html>');
    }

}

class CompositionTest_Given extends steps\CompositionTestGiven {
}