<?php

namespace Yaroslavche\PhpProject;

use Composer\Script\Event;

final class Install
{
    /** @var string|null $projectRootDir */
    private $projectRootDir;
    /** @var array<string, string> $options */
    private $options;

    public function __construct(Event $event)
    {
        $this->ask($event);
        $this->saveComposerJson();
        $this->clearDirectories();
        $this->dumpAutoload();
    }

    private function ask(Event $event)
    {
        $io = $event->getIO();
        while (empty($this->options['vendor'])) {
            $this->options['vendor'] = $io->ask('Vendor:' . PHP_EOL);
        }
        while (empty($this->options['package'])) {
            $this->options['package'] = $io->ask('Package:' . PHP_EOL);
        }
        $this->options['packageName'] = sprintf('%s/%s', $this->options['vendor'], $this->options['package']);
        $this->options['description'] = 'description';
        $this->options['type'] = 'project';
        $this->options['license'] = 'MIT';
        $this->options['authorName'] = 'name';
        $this->options['authorEmail'] = 'email@email.em';
    }

    private function saveComposerJson()
    {
        $this->projectRootDir = realpath(sprintf('%s%s..%s', __DIR__, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR));
        $composerJsonFilePath = sprintf('%s%scomposer.json', $this->projectRootDir, DIRECTORY_SEPARATOR);
        $composerJson = json_decode(file_get_contents($composerJsonFilePath), true);

        $composerJson['name'] = $this->options['packageName'];
        $composerJson['description'] = $this->options['description'];
        $composerJson['type'] = $this->options['type'];
        $composerJson['license'] = $this->options['license'];
        $composerJson['authors'][0]['name'] = $this->options['authorName'];
        $composerJson['authors'][0]['email'] = $this->options['authorEmail'];

        $newNamespace = sprintf('%s\\%s\\', ucfirst($this->options['vendor']), ucfirst($this->options['package']));
        $composerJson['autoload']['psr-4'] = [$newNamespace => 'src/'];
        $composerJson['autoload-dev']['psr-4'] = [sprintf('%sTests\\', $newNamespace) => 'tests/'];

        unset($composerJson['scripts']['post-autoload-dump']);
        unset($composerJson['scripts']['post-install-cmd']);
        unset($composerJson['scripts']['post-create-project-cmd']);

        $composerJsonContent = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($composerJsonFilePath, $composerJsonContent);
    }

    private function clearDirectories(): void
    {
        // need rewrite for using filesystem
        echo 'clear src';
        array_map(
            'unlink',
            array_filter(
                (array)glob(sprintf('%s%ssrc/*', $this->projectRootDir, DIRECTORY_SEPARATOR))
            )
        );
        echo 'clear tests';
        array_map(
            'unlink',
            array_filter(
                (array)glob(sprintf('%s%stests/*', $this->projectRootDir, DIRECTORY_SEPARATOR))
            )
        );
        echo 'remove internal';
        system("rm -rf " . escapeshellarg(sprintf('%s%sinternal', $this->projectRootDir, DIRECTORY_SEPARATOR)));
    }

    private function dumpAutoload(): void
    {
    }
}
