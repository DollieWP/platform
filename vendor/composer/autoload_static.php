<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb3cdf9cd029cb5fddd671c1db9850b29
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPD_Platform\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPD_Platform\\' => 
        array (
            0 => __DIR__ . '/../..' . '/core',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb3cdf9cd029cb5fddd671c1db9850b29::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb3cdf9cd029cb5fddd671c1db9850b29::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb3cdf9cd029cb5fddd671c1db9850b29::$classMap;

        }, null, ClassLoader::class);
    }
}
