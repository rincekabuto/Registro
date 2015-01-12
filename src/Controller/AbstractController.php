<?php

namespace Controller;

abstract class AbstractController {

    protected $dbAdapter;
    protected $currentLocale;
    protected $i18n;

    public function __construct() {
        $this->dbAdapter = \Database\PDOAdapterProvider::getAdapter();
        $this->i18n = require '../i18n.php';
        $this->currentLocale = (array_key_exists('l', $_SESSION) && $_SESSION['l'] != '') ? $_SESSION['l'] : 'en';
    }

    public function render($template, $params = null){
        $templateDir = strtolower( preg_filter('~Controller\\\~','',get_called_class()) );
        $templatePath = '../src/view/'.$templateDir.'/'.$template.'.phtml';

        $i18n = $this->i18n;
        $currentLocale = $this->currentLocale;
        $l = function ($string) use ($i18n, $currentLocale){
            if($currentLocale !== 'en' && array_key_exists($string, $i18n)){
                print $i18n[$string][$currentLocale];
            }else{
                print $string;
            }
        };

        if($params && is_array($params)){
            extract($params);
        }
        ob_start();
        include $templatePath;
        $content = ob_get_clean();
        $layoutPath = '../src/view/layout/layout.phtml';
        ob_start();
        include $layoutPath;
        $content = ob_get_clean();
        return $content;
    }
}

?>
