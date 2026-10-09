<?php
namespace App\Core;

class View {
    public static function render($view, $data = [], $layout = null) {
        extract($data);
        $viewFile = __DIR__ . '/../../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            die("View $view not found.");
        }
        
        if ($layout) {
            ob_start();
            require $viewFile;
            $content = ob_get_clean();
            $layoutFile = __DIR__ . '/../../views/layouts/' . $layout . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
            } else {
                echo $content;
            }
        } else {
            require $viewFile;
        }
    }
}
