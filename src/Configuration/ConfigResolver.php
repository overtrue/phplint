<?php

declare(strict_types=1);

/*
 * This file is part of the overtrue/phplint package
 *
 * (c) overtrue
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\PHPLint\Configuration;

use Symfony\Component\Console\Input\InputInterface;

use function array_merge;

/**
 * Replaced 'ConsoleOptionsResolver' and 'FileOptionsResolver' components deprecated since version 9.8.0
 * and avoid API breaks until we reach the next major version 10.0, that will clean up old components.
 *
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class ConfigResolver extends AbstractOptionsResolver
{
    /**
     * @param InputInterface $input Not used on version 9.8.x;
     *                              Keep for BC signature with others components available on version 9.7
     */

    public function __construct(InputInterface $input, array $configuration = [])
    {
        $this->defaults = array_merge([
            // specific to the 'Finder' component
            OptionDefinition::FILE_EXTENSIONS => OptionDefinition::DEFAULT_EXTENSIONS,

            // specific to the 'Linter' component
            OptionDefinition::JOBS => OptionDefinition::DEFAULT_JOBS,
            OptionDefinition::OPTION_MEMORY_LIMIT => ini_get('memory_limit'),
            OptionDefinition::WARNING => false,

            // specific to the 'cache_manager'
            OptionDefinition::NO_CACHE => true, //false,
            OptionDefinition::CACHE_TTL => OptionDefinition::DEFAULT_CACHE_TTL,
            OptionDefinition::CACHE_DIR => OptionDefinition::DEFAULT_CACHE_DIR,
            OptionDefinition::CACHE => OptionDefinition::DEFAULT_CACHE_DIR, // @deprecated keep BC with previous 9.7.x versions

            // specific to the 'output_manager'
            OptionDefinition::OUTPUT_FILE => null,
            OptionDefinition::OUTPUT_FORMAT => OptionDefinition::DEFAULT_FORMATS,

            // specific to the 'progress_manager'
            OptionDefinition::NO_PROGRESS => false,
            OptionDefinition::PROGRESS => OptionDefinition::DEFAULT_PROGRESS_WIDGET,

            // for this command only
            OptionDefinition::IGNORE_EXIT_CODE => false,
        ], $configuration);
    }

    public function factory(): Options
    {
        return new OptionsFactory($this->defaults);
    }
}
