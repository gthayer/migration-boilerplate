<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit71d4864510422e3e8b28147ea3dbe10b
{
    public static $files = array (
        'ed233f3c5c707ba194f8d7d712f5015a' => __DIR__ . '/../..' . '/includes/utils.php',
        'd813ea2291f69b7684adaf7350f57dc5' => __DIR__ . '/../..' . '/includes/class-wp-cli-command.php',
        '128bec78be347a5c16ad59ffe114c855' => __DIR__ . '/../..' . '/includes/logging.php',
        '23757b9378502c31276e80069b5ff9fd' => __DIR__ . '/../..' . '/classes/command.php',
        '741773fa1c2db367e9c7d4d0c4c7c975' => __DIR__ . '/../..' . '/classes/migrate-posts.php',
        '73814e1cf77d8a427cbe19ed63753a37' => __DIR__ . '/../..' . '/classes/delete-posts.php',
        '1f84192f6ca8a95071a4aa8ca902e740' => __DIR__ . '/../..' . '/classes/import-posts.php',
        '9f72191f110036f5d403b7206b821f54' => __DIR__ . '/../..' . '/classes/export-content-report.php',
        '485c1f5f2a9c3b21db3d8ab19e1ec22d' => __DIR__ . '/../..' . '/classes/brightspot/brightspot.php',
        '71b7e42cc023ea41ffe631cac44150d1' => __DIR__ . '/../..' . '/classes/database.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit71d4864510422e3e8b28147ea3dbe10b::$classMap;

        }, null, ClassLoader::class);
    }
}
