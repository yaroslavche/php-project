<?php

namespace Yaroslavche\PhpProject;

use Composer\Script\Event;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

final class Install
{
    /** @var Event */
    private $event;
    /** @var string|null $projectRootDir */
    private $projectRootDir;
    /** @var array<string, string|null> $optionsKeys */
    private $options;
    /** @var Filesystem $filesystem */
    private $filesystem;

    public function __construct(Event $event)
    {
        $this->options = array_fill_keys(
            ['vendor', 'package', 'description', 'type', 'license', 'authorName', 'authorEmail'],
            null
        );
        $this->event = $event;
        $this->filesystem = new Filesystem();

        $this->projectRootDir = realpath(sprintf('%s%s..%s', __DIR__, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR));

        $this->getOptions();
        $this->saveComposerJson();
    }

    private function getOptions()
    {
        foreach ($this->options as $optionKey => $optionValue) {
            $this->options[$optionKey] = $this->ask($optionKey);
        }
        $this->options['packageName'] = sprintf('%s/%s', $this->options['vendor'], $this->options['package']);
        $this->options['self_destroy'] = $this->event->getIO()->askConfirmation('Remove installer? [Y,n] ');
    }

    private function ask(string $optionKey): string
    {
        $value = null;
        while (null === $value) {
            $value = $this->event->getIO()->ask($optionKey . ': ');
            if (null === $value && !in_array($optionKey, ['vendor', 'package'])) {
                switch ($optionKey) {
                    case 'type':
                        $value = 'project';
                        break;
                    case 'license':
                        $value = 'MIT';
                        break;
                    default:
                        $value = '';
                        break;
                }
            }
        }
        return $value;
    }

    private function saveComposerJson()
    {
        $composerJsonFilePath = sprintf('%s%scomposer.json', $this->projectRootDir, DIRECTORY_SEPARATOR);
        $composerJson = json_decode(file_get_contents($composerJsonFilePath), true);

        $composerJson['name'] = $this->options['packageName'];
        if (!empty($this->options['description'])) {
            $composerJson['description'] = $this->options['description'];
        } else {
            unset($composerJson['description']);
        }
        $composerJson['type'] = $this->options['type'];
        $composerJson['license'] = $this->options['license'];
        if (filter_var($this->options['authorEmail'], FILTER_VALIDATE_EMAIL)) {
            if (!empty($this->options['authorName'])) {
                $composerJson['authors'][0]['name'] = $this->options['authorName'];
            }
            $composerJson['authors'][0]['email'] = $this->options['authorEmail'];
        } else {
            unset($composerJson['authors']);
        }

        $newNamespace = sprintf('%s\\%s\\', ucfirst($this->options['vendor']), ucfirst($this->options['package']));
        $composerJson['autoload']['psr-4'] = [$newNamespace => 'src/'];
        if ($this->options['self_destroy']) {
            $composerJson['autoload-dev']['psr-4'] = [sprintf('%sTests\\', $newNamespace) => 'tests/'];
            unset($composerJson['scripts']['post-install-cmd']);
            unset($composerJson['scripts']['post-create-project-cmd']);
        } else {
            $composerJson['autoload-dev']['psr-4'] = [
                sprintf('%sTests\\', $newNamespace) => 'tests/',
                __NAMESPACE__ . '\\' => 'internal/'
            ];
        }

        $composerJsonContent = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->filesystem->dumpFile($composerJsonFilePath, $composerJsonContent);
    }

    public function __destruct()
    {
        if ($this->options['self_destroy'] === true) {
            $this->filesystem->remove(sprintf('%s%sinternal', $this->projectRootDir, DIRECTORY_SEPARATOR));
        }
    }
}
