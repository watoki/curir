<?php
namespace spec\watoki\webco\steps;
 
use spec\watoki\webco\Step;

class Then extends Step {

    public function theResponseBodyShouldBe($body) {
        $this->test->assertEquals($body, $this->test->when->response->getBody());
    }

    public function theResponseHeader_ShouldBe($field, $value) {
        $this->test->assertEquals($value, $this->test->when->response->getHeaders()->get($field));
    }

    public function anExceptionContaining_ShouldBeThrown($message) {
        $this->test->assertNotNull($this->test->when->caught);
        $this->test->assertContains($message, $this->test->when->caught->getMessage());
    }

}
