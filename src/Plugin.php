<?php

declare(strict_types=1);

namespace Pest\Drift;

use Pest\Contracts\Plugins\HandlesArguments;
use Pest\Drift\Converters\CodeConverterFactory;
use Pest\Drift\Converters\DirectoryConverter;
use Pest\Drift\Converters\FileConverter;
use Pest\Drift\Finder\Finder;
use Pest\Drift\Support\View;
use Pest\Exceptions\InvalidOption;
use Pest\Plugins\Concerns\HandleArguments;
use function Pest\testDirectory;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class Plugin implements HandlesArguments
{
    use HandleArguments;

    /**
     * Creates a new plugin instance.
     */
    public function __construct(private readonly OutputInterface $output)
    {
        //
    }

    /**
     * Handles the arguments, adding the `--stop-on-defect` when the `--bail` argument is present.
     */
    public function handleArguments(array $arguments): array
    {
        if (! $this->hasArgument('--drift', $arguments)) {
            return $arguments;
        }

        $arguments = array_slice($arguments, (int) array_search('--drift', $arguments, true) + 1);

        if ($arguments === []) {
            $directory = testDirectory();
        } elseif (count($arguments) === 1) {
            $directory = $arguments[0];
        } else {
            throw new InvalidOption('The [--drift] argument only accepts the directory to convert as argument.');
        }

        $directory = rtrim($directory, '/');

        $finder = new Finder($directory);
        $codeConverterFactory = (new CodeConverterFactory());
        $directoryConverter = new DirectoryConverter(new FileConverter($codeConverterFactory->codeConverter(), $directory));

        $this->output->writeln('');
        $this->output->write('  ');

        $changedTotal = $directoryConverter->convert($finder, function (bool $changed): void {
            $this->output->write($changed ? '<fg=green;options=bold>✔</>' : '<fg=gray>.</>');
        });

        $this->output->writeln('');

        View::renderUsing($this->output);
        View::render('components.badge', [
            'type' => 'INFO',
            'content' => 'The ['.$directory.'] directory has been migrated to PEST with '.$changedTotal.' files changed.',
        ]);

        exit(0);
    }
}
