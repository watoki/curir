<?php
namespace spec\watoki\curir\resources;
 
use spec\watoki\curir\fixtures\FileFixture;
use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 * @property FileFixture file <-
 */
class ContainerTest extends Specification {

    function testRespondsItself() {
        $this->resource->givenTheContainer_WithTheBody('MySelf', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter("Hello World");
        }');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Hello World"');
    }

    function testNotExistingChild() {
        $this->resource->givenTheContainer('Childless');
        $this->resource->givenTheRequestHasTheTarget('notexisting');

        $this->resource->whenITryToSendTheRequestToThatResource();
        $this->resource->thenTheRequestShouldFailWith('Resource [notexisting] not found in container [Childless]');
    }

    function testForwardToStaticChild() {
        $this->file->givenTheFile_WithTheContent('test.txt', 'Hello World');
        $this->resource->givenTheStaticResourceFor('test.txt');
        $this->resource->givenTheContainer('StaticChild');
        $this->resource->givenTheRequestHasTheTarget('test');
        $this->resource->givenIRequestTheFormat('txt');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Hello World');
    }

    function testForwardToDynamicChild() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('ChildResource', 'test', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter("Found it");
        }');
        $this->resource->givenTheContainer_In('WithDynamicChild', 'test');
        $this->resource->givenTheRequestHasTheTarget('Child');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Found it"');
    }

    function testForwardToGrandChild() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('ChildResource', 'test/folder', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter("Found me");
        }');
        $this->resource->givenTheContainer('WithGrandChild');
        $this->resource->givenTheRequestHasTheTarget('test/folder/Child');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Found me"');
    }

    function testForwardToDynamicContainer() {
        $this->resource->givenTheContainer_WithTheBody('DynamicResource', 'public function respond(\watoki\curir\http\Request $r) {
            return new \watoki\curir\http\Response("Found");
        }');
        $this->resource->givenTheContainer('ParentOfDynamicResource');
        $this->resource->givenTheRequestHasTheTarget('Dynamic');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Found');
    }

    function testDynamicChildIsPreferred() {
        $this->file->givenTheFile_WithTheContent('Test.json', 'The file');
        $this->resource->givenTheDynamicResource_WithTheBody('TestResource', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter("Dynamic content");
        }');
        $this->resource->givenTheContainer('PrefersDynamicChild');
        $this->resource->givenTheRequestHasTheTarget('Test');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Dynamic content"');
    }

    function testDynamicContainerIsPreferred() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('NeglectedResource', 'Overwritten', 'function doGet() {}');
        $this->resource->givenTheContainer_In_WithTheBody('OverwrittenResource', '', 'public function respond(\watoki\curir\http\Request $r) {
            return new \watoki\curir\http\Response("Me first");
        }');
        $this->resource->givenTheRequestHasTheTarget('Test/Neglected');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Me first');
    }

    function testForwardToInheritedChild() {
        $this->markTestIncomplete();
    }

}
 