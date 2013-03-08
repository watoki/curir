<?php
namespace spec\watoki\webco;

use spec\watoki\webco\steps\Given;
use spec\watoki\webco\steps\Then;
use spec\watoki\webco\steps\When;

/**
 * @property Given given
 * @property When when
 * @property Then then
 */
abstract class Test extends \PHPUnit_Framework_TestCase {

    public $undos = array();

    protected function setUp() {
        parent::setUp();

        foreach (array('given', 'when', 'then') as $step) {
            $class = get_class($this) . '_' . ucfirst($step);
            if (!class_exists($class)) {
                $class = 'spec\watoki\webco\steps\\' . ucfirst($step);
            }
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
