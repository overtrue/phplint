<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Command;

use Overtrue\PHPLint\Configuration\ConsoleOptionsResolver;
use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Console\Application;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Overtrue\PHPLint\Output\LinterOutput;
use PHP_Parallel_Lint\PhpConsoleColor\InvalidStyleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder as SymfonyFinder;
use Throwable;

use function count;
use function microtime;

/**
 * @author Overtrue
 * @author Laurent Laville (since v9.0)
 */
final class LintCommand extends Command
{
    use ConfigureCommandTrait;

    private EventDispatcherInterface $dispatcher;
    private LinterOutput $results;

    public function __construct(EventDispatcherInterface $dispatcher, string $name = 'lint')
    {
        parent::__construct($name);
        $this->dispatcher = $dispatcher;
        $this->results = new LinterOutput([], new SymfonyFinder());
    }

    public function getResults(): LinterOutput
    {
        return $this->results;
    }

    protected function configure(): void
    {
        $this->setDescription('Files syntax check only');
        $this->configureCommand($this);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // initializes correctly command and path arguments when lint is set as default command
        $cmd = $input->getArgument('command');

        if ($cmd === $this->getName()) {
            $paths = $input->getArgument('path');
        } else {
            $paths = [$cmd];
        }

        $input->setArgument('path', $paths);
        $input->setArgument('command', $this->getName());
    }

    /**
     * @throws InvalidStyleException|Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);

        if (true === $input->hasParameterOption(['--no-configuration'], true)) {
            $configResolver = new ConsoleOptionsResolver($input, $this->getDefinition());
        } else {
            $configResolver = new FileOptionsResolver($input, $this->getDefinition());
        }

        $finder = (new Finder($configResolver))->getFiles();
        $fileCount = count($finder);
        /** @var Application $app */
        $app = $this->getApplication();
        $linter = new Linter($configResolver, $this->dispatcher, $app->getLongVersion());
        $this->results = $linter->lintFiles($finder, $startTime);

        $data = $this->results->getFailures();

        if ($configResolver->getOption(OptionDefinition::OPTION_IGNORE_EXIT_CODE)) {
            return self::SUCCESS;
        }

        if ($fileCount === 0 || count($data)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
