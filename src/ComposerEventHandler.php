<?php

namespace App;

use Composer\Script\Event;

class ComposerEventHandler
{
    /** @var string|null $projectRootDir */
    private static $projectRootDir;

    public static function postInstall(?Event $event): int
    {
        if (null === $event) {
            return 0;
        }
        $io = $event->getIO();
        while (empty($vendor)) {
            $vendor = $io->ask('Vendor:' . PHP_EOL);
        }
        while (empty($package)) {
            $package = $io->ask('Package:' . PHP_EOL);
        }
        $packageName = sprintf('%s/%s', $vendor, $package);
        $description = 'description';
        $type = 'project';
        $license = 'MIT';
        $authorName = 'name';
        $authorEmail = 'email@email.em';

        self::$projectRootDir = realpath(sprintf('%s%s..%s', __DIR__, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR));
        $composerJsonFilePath = sprintf('%s%scomposer.json', self::$projectRootDir, DIRECTORY_SEPARATOR);
        $composerJson = json_decode(file_get_contents($composerJsonFilePath), true);

        $composerJson['name'] = $packageName;
        $composerJson['description'] = $description;
        $composerJson['type'] = $type;
        $composerJson['license'] = $license;
        $composerJson['authors'][0]['name'] = $authorName;
        $composerJson['authors'][0]['email'] = $authorEmail;

        $newNamespace = sprintf('%s\\%s\\', ucfirst($vendor), ucfirst($package));
        $composerJson['autoload']['psr-4'] = [$newNamespace => 'src/'];
        $composerJson['autoload-dev']['psr-4'] = [sprintf('%sTests\\', $newNamespace) => 'tests/'];

        unset($composerJson['scripts']['post-install-cmd']);

        $composerJsonContent = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($composerJsonFilePath, $composerJsonContent);

        self::clearDirectories();
    }

    private static function clearDirectories(): void
    {
        array_map(
            'unlink',
            array_filter(
                array_merge(
                    (array)glob(sprintf('%s%ssrc/*', self::$projectRootDir, DIRECTORY_SEPARATOR)),
                    (array)glob(sprintf('%s%stests/*', self::$projectRootDir, DIRECTORY_SEPARATOR))
                )
            )
        );
    }
}
