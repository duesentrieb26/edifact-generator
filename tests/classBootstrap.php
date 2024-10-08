<?php

/**
 * Bootstrap
 * @package 
 */
class Bootstrap {
    /**
     * init
     * @return void 
     * @throws TypeError 
     */
    public static function init() {
        static::initAutoloader();
    }

    /**
     * initAutoloader
     * @return void 
     * @throws TypeError 
     */
    protected static function initAutoloader() {
        $vendorPath = static::findParentPath('vendor');
        if (file_exists($vendorPath . '/autoload.php')) {
            include $vendorPath . '/autoload.php';
        }
        spl_autoload_register(function ($className) {
            $path = str_replace('\\', '/', $className);
            if (file_exists('../src/' . $path . '.php')) {
                include_once '../src/' . $path . '.php';
            }
        });
    }

    /**
     * findParentPath
     * @param  string $path 
     * @return string|false 
     * @throws TypeError 
     */
    public static function findParentPath($path) {
        $dir = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);
            if ($previousDir === $dir) {
                return false;
            }
            $previousDir = $dir;
        }
        return $dir . '/' . $path;
    }
}
