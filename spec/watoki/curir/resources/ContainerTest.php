<?php
namespace spec\watoki\curir\resources;

use spec\watoki\curir\fixtures\FileFixture;
use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\curir\http\Response;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 * @property FileFixture file <-
 */
class ContainerTest extends Specification {

    protected function background() {
        $this->resource->givenIRequestTheFormat('json');
    }

    function testRespondsItself() {
        $this->resource->givenTheContainer_WithTheBody('MySelf', 'function doGet() {
            return new \watoki\curir\responder\DefaultResponder("Hello World");
        }');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Hello World');
    }

    function testNotExistingChild() {
        $this->resource->givenTheRequestHasTheTarget('notExisting');
        $this->resource->givenTheContainer('Childless');

        $this->resource->whenITryToSendTheRequestToThatResource();
        $this->resource->thenTheRequestShouldFailWith('Resource [notExisting] not found in container [ChildlessResource]');
        $this->resource->thenTheRequestShouldReturnTheStatus(Response::STATUS_NOT_FOUND);
    }

    function testForwardToStaticChild() {
        $this->file->givenTheFile_WithTheContent('staticChild/test.txt', 'Hello World');
        $this->resource->givenTheStaticResourceFor('test.txt');
        $this->resource->givenTheRequestHasTheTarget('test');
        $this->resource->givenIRequestTheFormat('txt');
        $this->resource->givenTheContainer('StaticChild');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Hello World');
    }

    function testForwardToDynamicChild() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('Child', 'test/withDynamicChild', 'function doGet() {
            return new \watoki\curir\responder\DefaultResponder(array("html" => "Html", "" => "Found it"));
        }');
        $this->resource->givenTheRequestHasTheTarget('Child');
        $this->resource->givenTheContainer_In('WithDynamicChild', 'test');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Found it');
    }

    function testForwardToGrandChild() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('GrandChild', 'withGrandChild/test/folder', 'function doGet() {
            return new \watoki\curir\responder\DefaultResponder(array("html" => "Html", "json" => "Found me", "" => "Default"));
        }');
        $this->resource->givenTheRequestHasTheTarget('test/folder/GrandChild');
        $this->resource->givenTheContainer('WithGrandChild');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Found me');
    }

    function testCaseInsensitive() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('InsensitiveChild', 'caseInsensitive/test/folder', 'function doGet() {
            return new \watoki\curir\responder\DefaultResponder("Gotcha");
        }');
        $this->resource->givenTheRequestHasTheTarget('TeSt/fOlder/insEnsitivEchIld');
        $this->resource->givenTheContainer('CaseInsensitive');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Gotcha');
    }

    function testForwardToDynamicContainer() {
        $this->resource->givenTheContainer_In_WithTheBody('Dynamic', 'dynamicParent',
            'public function respond(\watoki\curir\http\Request $r) {
                return new \watoki\curir\http\Response("Found");
            }');
        $this->resource->givenTheRequestHasTheTarget('Dynamic');
        $this->resource->givenTheContainer('DynamicParent');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Found');
    }

    function testDynamicChildIsPreferred() {
        $this->file->givenTheFile_WithTheContent('Test.json', 'The file');
        $this->resource->givenTheDynamicResource_In_WithTheBody('Test', 'prefersDynamicChild', 'function doGet() {
            return new \watoki\curir\responder\DefaultResponder("Dynamic content");
        }');
        $this->resource->givenTheRequestHasTheTarget('Test');
        $this->resource->givenTheContainer('PrefersDynamicChild');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Dynamic content');
    }

    function testDynamicContainerIsPreferred() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('Neglected', 'Overwritten', 'function doGet() {}');
        $this->resource->givenTheRequestHasTheTarget('Test/Neglected');
        $this->resource->givenTheContainer_In_WithTheBody('Overwritten', '', 'public function respond(\watoki\curir\http\Request $r) {
            return new \watoki\curir\http\Response("Me first");
        }');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Me first');
    }

    function testForwardToInheritedChild() {
        $this->resource->givenTheContainer_In('Base', 'other/place');
        $this->resource->givenTheDynamicResource_In_WithTheBody('InheritedChild', 'other/place/base', 'function doGet() {
            return new \watoki\curir\responder\DefaultResponder("I am inherited");
        }');
        $this->resource->givenTheContainer_In_Extending('Sub', 'parentOfInheriting', '\other\place\BaseResource');
        $this->resource->givenTheRequestHasTheTarget('Sub/InheritedChild');
        $this->resource->givenTheContainer('ParentOfInheriting');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('I am inherited');
    }

}
 