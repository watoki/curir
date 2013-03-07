<?php
namespace spec\watoki\webco;
 
abstract class Test extends \PHPUnit_Framework_TestCase {

    public $undos = array();

    protected function setUp() {
        parent::setUp();

        foreach (array('given', 'when', 'then') as $step) {
            $class = get_class($this) . '_' . ucfirst($step);
            $this->$step = new $class($this);
        }
    }

    protected function tearDown() {
        foreach (array_reverse($this->undos) as $undo) {
            $undo();
        }

        parent::tearDown();
    }

}
