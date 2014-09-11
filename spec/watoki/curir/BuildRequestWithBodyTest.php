<?php
namespace spec\watoki\curir;

use watoki\scrut\Specification;

/**
 * A WebRequest is also built from the body of the HTTP request, if present. The body may be encoded
 * in different ways and is always decoded to request arguments.
 */
class BuildRequestWithBodyTest extends Specification {

    function testUndefinedContentType() {
        $this->markTestIncomplete();
    }

    function testFormData() {
        $this->markTestIncomplete();
    }

    function testEmptyFormData() {
        $this->markTestIncomplete();
    }

    function testJson() {
        $this->markTestIncomplete();
    }

    function testEmptyJson() {
        $this->markTestIncomplete();
    }

    function testInvalidJson() {
        $this->markTestIncomplete();
    }

    function testOverwriteQueryParameters() {
        $this->markTestIncomplete();
    }

} 