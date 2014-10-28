<?php
namespace watoki\curir\rendering;

class PhpRenderer implements Renderer {

    public static $CLASS = __CLASS__;

    /**
     * @param string $template
     * @param mixed $model
     * @throws \Exception
     * @return string
     */
    public function render($template, $model) {
        foreach ($model as $key => $value) {
            $$key = $value;
        }
        ob_start();
        $template = str_replace('<?= ', '<?php echo ', $template);
        $template = str_replace('<? ', '<?php ', $template);

        $eval = eval('?>' . $template);
        if ($eval === false) {
            throw new \Exception("Could not parse template: "
                    . "\n--------------------------------\n"
                    . $template
                    . "\n--------------------------------\n");
        }
        return ob_get_clean();
    }
}