<?php
namespace watoki\webco\controller\sub;

use watoki\tempan\HtmlParser;

class HtmlSubComponent extends PlainSubComponent {

    public function render() {
        return $this->postProcess(parent::render());
    }

    private function postProcess($content) {
        $parser = new HtmlParser($content);

        $bodyElement = $this->findBodyElement($parser->getRoot());

        return substr($parser->toString($bodyElement), strlen('<body>'), -strlen('</body>'));
    }

    /**
     * @param $root
     * @return mixed
     * @throws \Exception
     */
    private function findBodyElement($root) {
        $bodyElement = $root;
        if ($bodyElement->nodeName == 'html') {
            $bodyElement = $bodyElement->firstChild;
            while ($bodyElement->nodeName != 'body') {
                $bodyElement = $bodyElement->nextSibling;
                if (!$bodyElement) {
                    throw new \Exception('Cannot find body element while parsing sub component [' . $this->name . ']');
                }
            }
            return $bodyElement;
        }
        return $bodyElement;
    }

}