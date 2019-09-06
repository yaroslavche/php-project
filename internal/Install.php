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
        $this->changeInformation();
    }

    private function getOptions()
    {
        foreach ($this->options as $optionKey => $optionValue) {
            $this->options[$optionKey] = $this->ask($optionKey);
        }
        $this->options['packageName'] = sprintf('%s/%s', $this->options['vendor'], $this->options['package']);
        $this->options['self_destroy'] = $this->event->getIO()->askConfirmation('Remove installer? [<comment>Y,n</comment>] ');
    }

    private function ask(string $optionKey): string
    {
        $value = null;
        while (null === $value) {
            $question = $optionKey;
            $default = '';
            switch ($optionKey) {
                case 'type':
                    $default = 'project';
                    break;
                case 'license':
                    $default = 'MIT';
                    break;
            }
            $question = sprintf('%s%s: ', $question, !empty($default) ? sprintf(' [<comment>%s</comment>]', $default) : '');
            $value = $this->event->getIO()->ask($question);
            if (null === $value && !in_array($optionKey, ['vendor', 'package'])) {
                $value = $default;
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

    private function changeInformation()
    {
        /** README.md */
        $readmeFilePath = sprintf('%s%sREADME2.md', $this->projectRootDir, DIRECTORY_SEPARATOR);
        $this->replaceInFile(
            $readmeFilePath,
            [
                'yaroslavche',
                'php-project'
            ],
            [
                $this->options['vendor'],
                $this->options['package']
            ]
        );

        /** .gitattributes */
        $gitattributesFilePath = sprintf('%s%s.gitattributes', $this->projectRootDir, DIRECTORY_SEPARATOR);
        $this->replaceInFile($gitattributesFilePath, ['# '], ['']);
    }

    private function replaceInFile(string $filePath, array $search, array $replace)
    {
        if ($this->filesystem->exists($filePath)) {
            $content = file_get_contents($filePath);
            $content = str_replace($search, $replace, $content);
            $this->filesystem->dumpFile($filePath, $content);
        }
    }

    public function __destruct()
    {
        if ($this->options['self_destroy'] === true) {
            $this->filesystem->remove(sprintf('%s%sinternal', $this->projectRootDir, DIRECTORY_SEPARATOR));
        }
    }
}
