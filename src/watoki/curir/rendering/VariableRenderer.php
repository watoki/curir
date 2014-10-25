<?php
namespace watoki\curir\rendering;

class VariableRenderer implements Renderer {

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
        $eval = eval('return "' . str_replace('"', '\"', $template) . '";');
        if ($eval === false) {
            throw new \Exception("Could not parse template: "
                    . "\n--------------------------------\n"
                    . $template
                    . "\n--------------------------------\n");
        }
        return $eval;
    }
}