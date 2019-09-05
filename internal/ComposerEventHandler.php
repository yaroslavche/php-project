<?php

namespace Yaroslavche\PhpProject;

use Composer\Script\Event;

class ComposerEventHandler
{
    /** @var string|null $projectRootDir */
    private static $projectRootDir;
    /** @var array<string, string> $options */
    private static $options;

    public static function postAutoloadDump(Event $event): void
    {
        echo $event->getName();
    }

    public static function postInstall(Event $event): void
    {
        self::ask($event);
        self::saveComposerJson();
        self::clearDirectories();
        self::dumpAutoload();
        echo $event->getName();
    }

    public static function postCreateProject(Event $event): void
    {
        echo $event->getName();
    }

    /** move to class */
    private static function ask(Event $event)
    {
        $io = $event->getIO();
        while (empty(self::$options['vendor'])) {
            self::$options['vendor'] = $io->ask('Vendor:' . PHP_EOL);
        }
        while (empty(self::$options['package'])) {
            self::$options['package'] = $io->ask('Package:' . PHP_EOL);
        }
        self::$options['packageName'] = sprintf('%s/%s', self::$options['vendor'], self::$options['package']);
        self::$options['description'] = 'description';
        self::$options['type'] = 'project';
        self::$options['license'] = 'MIT';
        self::$options['authorName'] = 'name';
        self::$options['authorEmail'] = 'email@email.em';
    }

    private static function saveComposerJson()
    {
        self::$projectRootDir = realpath(sprintf('%s%s..%s', __DIR__, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR));
        $composerJsonFilePath = sprintf('%s%scomposer.json', self::$projectRootDir, DIRECTORY_SEPARATOR);
        $composerJson = json_decode(file_get_contents($composerJsonFilePath), true);

        $composerJson['name'] = self::$options['packageName'];
        $composerJson['description'] = self::$options['description'];
        $composerJson['type'] = self::$options['type'];
        $composerJson['license'] = self::$options['license'];
        $composerJson['authors'][0]['name'] = self::$options['authorName'];
        $composerJson['authors'][0]['email'] = self::$options['authorEmail'];

        $newNamespace = sprintf('%s\\%s\\', ucfirst(self::$options['vendor']), ucfirst(self::$options['package']));
        $composerJson['autoload']['psr-4'] = [$newNamespace => 'src/'];
        $composerJson['autoload-dev']['psr-4'] = [sprintf('%sTests\\', $newNamespace) => 'tests/'];

        unset($composerJson['scripts']['post-autoload-dump']);
        unset($composerJson['scripts']['post-install-cmd']);
        unset($composerJson['scripts']['post-create-project-cmd']);

        $composerJsonContent = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($composerJsonFilePath, $composerJsonContent);
    }

    private static function clearDirectories(): void
    {
        // need rewrite for using filesystem
        echo 'clear src';
        array_map(
            'unlink',
            array_filter(
                (array)glob(sprintf('%s%ssrc/*', self::$projectRootDir, DIRECTORY_SEPARATOR))
            )
        );
        echo 'clear tests';
        array_map(
            'unlink',
            array_filter(
                (array)glob(sprintf('%s%stests/*', self::$projectRootDir, DIRECTORY_SEPARATOR))
            )
        );
        echo 'remove internal';
        system("rm -rf ".escapeshellarg(sprintf('%s%sinternal', self::$projectRootDir, DIRECTORY_SEPARATOR)));
    }

    private static function dumpAutoload(): void
    {
    }
}
