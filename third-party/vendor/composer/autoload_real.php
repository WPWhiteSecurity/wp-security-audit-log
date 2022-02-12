<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit156a3496e1a6da7b59eda0c2cbc531ff
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('WSAL_Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \WSAL_Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit156a3496e1a6da7b59eda0c2cbc531ff', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \WSAL_Composer\Autoload\ClassLoader();
        spl_autoload_unregister(array('ComposerAutoloaderInit156a3496e1a6da7b59eda0c2cbc531ff', 'loadClassLoader'));

        $useStaticLoader = PHP_VERSION_ID >= 50600 && !defined('HHVM_VERSION') && (!function_exists('zend_loader_file_encoded') || !zend_loader_file_encoded());
        if ($useStaticLoader) {
            require_once __DIR__ . '/autoload_static.php';

            call_user_func(\WSAL_Composer\Autoload\ComposerStaticInit156a3496e1a6da7b59eda0c2cbc531ff::getInitializer($loader));
        } else {
            $classMap = require __DIR__ . '/autoload_classmap.php';
            if ($classMap) {
                $loader->addClassMap($classMap);
            }
        }

        $loader->setClassMapAuthoritative(true);
        $loader->register(true);

        $includeFiles = require __DIR__ . '/autoload_files.php';
        foreach ( $includeFiles as $fileIdentifier => $file ) {
            if ( empty( $GLOBALS['__composer_autoload_files'][ $fileIdentifier ] ) ) {
                require $file;

                $GLOBALS['__composer_autoload_files'][ $fileIdentifier ] = true;
            }
        }

        return $loader;
    }
}
