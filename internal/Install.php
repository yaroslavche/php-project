<?php

declare(strict_types=1);

namespace Yaroslavche\PhpProject;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

/** @internal */
final class Install
{
    private Event $event;
    private ?string $projectRootDir;
    /** @var array<string, null|string> $options */
    private array $options;
    /** @var array<string, array> $optionsConfig */
    private array $optionsConfig;
    /** @var Filesystem $filesystem */
    private Filesystem $filesystem;

    public function __construct(Event $event)
    {
        $this->options = array_fill_keys(
            ['vendor', 'package', 'description', 'type', 'license', 'authorName', 'authorEmail'],
            null
        );
        $this->optionsConfig = [
            'vendor' => ['required' => true, 'question_addition' => ' ("<comment>vendor</comment>/package")'],
            'package' => ['required' => true, 'question_addition' => ' ("vendor/<comment>package</comment>")'],
            'type' => ['default' => 'project'],
            'license' => ['default' => 'MIT'],
            'authorName' => ['question' => 'Author'],
            'authorEmail' => ['question' => 'Email'],
        ];
        $this->event = $event;
        $this->filesystem = new Filesystem();

        $this->projectRootDir = realpath(sprintf('%s%s..%s', __DIR__, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR));

        $this->getOptions();
        $this->saveComposerJson();
        $this->changeInformation();
    }

    private function getOptions(): void
    {
        foreach ($this->options as $optionKey => $optionValue) {
            $this->options[$optionKey] = $this->ask($optionKey);
        }
        $this->options['packageName'] = sprintf('%s/%s', $this->options['vendor'], $this->options['package']);
        $this->options['self_destroy'] = $this->event->getIO()->askConfirmation('Remove installer? [<comment>Y</comment>,n] ');
    }

    private function ask(string $optionKey): string
    {
        $question = sprintf(
            '<info>%s</info>%s',
            ucfirst($this->optionsConfig[$optionKey]['question'] ?? $optionKey),
            $this->optionsConfig[$optionKey]['question_addition'] ?? ''
        );
        $default = $this->optionsConfig[$optionKey]['default'] ?? '';
        $required = ($this->optionsConfig[$optionKey]['required'] ?? false) === true;
        $question = sprintf(
            '%s %s: ',
            $question,
            !empty($default) ? sprintf('[<comment>"%s"</comment>]', $default) : ($required ? '' : '[<comment>""</comment>]')
        );
        $value = null;
        while (null === $value) {
            $value = $this->event->getIO()->ask($question);
            if (null === $value && !$required) {
                $value = $default;
            }
        }
        return $value;
    }

    private function saveComposerJson(): void
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

    private function changeInformation(): void
    {
        /** README.md */
        $readmeFilePath = sprintf('%s%sREADME.md', $this->projectRootDir, DIRECTORY_SEPARATOR);
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

    private function replaceInFile(string $filePath, array $search, array $replace): void
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
