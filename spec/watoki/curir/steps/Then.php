<?php
namespace spec\watoki\curir\steps;
 
use spec\watoki\curir\Step;

class Then extends Step {

    public function theResponseBodyShouldBe($body) {
        $this->test->assertEquals($this->clean($body),
            $this->clean($this->test->when->response->getBody()));
    }

    public function theHtmlResponseBodyShouldBe($expected) {
        $this->test->assertEquals(urldecode(html_entity_decode($this->clean($expected))),
            urldecode(html_entity_decode($this->clean($this->test->when->response->getBody()))));
    }

    private function clean($str) {
        return str_replace(array("  ", "\n", "\r", "\t"), "", $str);
    }

    public function theResponseHeader_ShouldBe($field, $value) {
        $this->test->assertEquals($value, $this->test->when->response->getHeaders()->get($field));
    }

    public function anExceptionContaining_ShouldBeThrown($message) {
        $this->test->assertNotNull($this->test->when->caught);
        $this->test->assertContains($message, $this->test->when->caught->getMessage());
    }

}
