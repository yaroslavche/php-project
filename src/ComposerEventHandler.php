<?php

namespace App;

use Composer\Script\Event;

class ComposerEventHandler
{
    public static function postInstall(?Event $event): void
    {
        if (null === $event) {
            return;
        }
        $vendor = 'vendor';
        $package = 'package';
        $packageName = sprintf('%s/%s', $vendor, $package);
        $description = 'description';
        $type = 'project';
        $license = 'MIT';
        $authorName = 'name';
        $authorEmail = 'email@email.em';

        $projectRootDir = realpath(sprintf('%s%s..%s', __DIR__, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR));
        $composerJsonFile = sprintf('%s%scomposer.json', $projectRootDir, DIRECTORY_SEPARATOR);
        $composerJson = json_decode(file_get_contents($composerJsonFile), true);
        // change composer.json and other files
        array_map('unlink', array_filter((array) glob(sprintf('%s%ssrc/*', $projectRootDir, DIRECTORY_SEPARATOR))));
        array_map('unlink', array_filter((array) glob(sprintf('%s%stests/*', $projectRootDir, DIRECTORY_SEPARATOR))));
    }
}
