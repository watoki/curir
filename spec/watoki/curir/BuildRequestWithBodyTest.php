<?php
namespace spec\watoki\curir;

use spec\watoki\curir\fixtures\WebRequestBuilderFixture;
use watoki\curir\decoder\FormDecoder;
use watoki\curir\decoder\JsonDecoder;
use watoki\scrut\Specification;

/**
 * A WebRequest is also built from the body of the HTTP request, if present. The body may be encoded
 * in different ways and is always decoded to request arguments.
 *
 * @property WebRequestBuilderFixture request <-
 */
class BuildRequestWithBodyTest extends Specification {

    protected function background() {
        $this->request->givenTheMethodArgumentIs('put');
    }

    function testUndefinedContentType() {
        $this->request->whenIBuildTheRequest();
        $this->request->thenTheArgumentsShouldBeEmpty();
    }

    function testFormData() {
        $this->request->given_IsRegisteredForTheContentType(new FormDecoder(), 'some/form');
        $this->request->givenTheHeader_Is('CONTENT_TYPE', 'some/form');
        $this->request->givenTheBodyIs('a[]=1&a[]=2&a[b]=4');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsArgument_ShouldBe('a', array(0 => '1', 1 => '2', 'b' => '4'));
    }

    function testEmptyFormData() {
        $this->request->given_IsRegisteredForTheContentType(new FormDecoder(), 'some/form');
        $this->request->givenTheHeader_Is('CONTENT_TYPE', 'some/form');
        $this->request->givenTheBodyIs('');

        $this->request->whenIBuildTheRequest();
        $this->request->thenTheArgumentsShouldBeEmpty();
    }

    function testJson() {
        $this->request->given_IsRegisteredForTheContentType(new JsonDecoder(), 'Jason');
        $this->request->givenTheHeader_Is('CONTENT_TYPE', 'Jason');
        $this->request->givenTheBodyIs('{"a":["c", "d"],"b":1}');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsArgument_ShouldBe('a', array('c', 'd'));
        $this->request->thenItsArgument_ShouldBe('b', 1);
    }

    function testEmptyJson() {
        $this->request->given_IsRegisteredForTheContentType(new JsonDecoder(), 'Jason');
        $this->request->givenTheHeader_Is('CONTENT_TYPE', 'Jason');
        $this->request->givenTheBodyIs('');

        $this->request->whenIBuildTheRequest();
        $this->request->thenTheArgumentsShouldBeEmpty();
    }

    function testInvalidJson() {
        $this->request->given_IsRegisteredForTheContentType(new JsonDecoder(), 'Jason');
        $this->request->givenTheHeader_Is('CONTENT_TYPE', 'Jason');
        $this->request->givenTheBodyIs('not json');

        $this->request->whenIBuildTheRequest();
        $this->request->thenTheArgumentsShouldBeEmpty();
    }

    function testOverwriteQueryParameters() {
        $this->request->given_IsRegisteredForTheContentType(new JsonDecoder(), 'Jason');
        $this->request->givenTheHeader_Is('CONTENT_TYPE', 'Jason');
        $this->request->givenTheBodyIs('{"a":["c", "d"]}');
        $this->request->givenTheQueryArgument_Is('a', array(1, 2));
        $this->request->givenTheQueryArgument_Is('b', 'foo');

        $this->request->whenIBuildTheRequest();
        $this->request->thenItsArgument_ShouldBe('a', array('c', 'd'));
        $this->request->thenItsArgument_ShouldBe('b', 'foo');
    }

} 