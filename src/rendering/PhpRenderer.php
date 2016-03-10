<?php
namespace watoki\curir\rendering;

class PhpRenderer implements Renderer {

    /**
     * @param string $template
     * @param mixed $model
     * @throws \Exception
     * @return string
     */
    public function render($template, $model) {
        $template = str_replace('<?= ', '<?php echo ', $template);
        $template = str_replace('<? ', '<?php ', $template);

        ob_start();
        $eval = $this->evalWithVariables($model, $template);
        if ($eval === false) {
            throw new \Exception("Could not parse template: "
                    . "\n--------------------------------\n"
                    . $template
                    . "\n--------------------------------\n");
        }
        return ob_get_clean();
    }

    private function evalWithVariables($__model, $__template) {
        foreach ($__model as $__key => $__value) {
            $$__key = $__value;
        }
        return eval('?>' . $__template);
    }
}