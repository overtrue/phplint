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

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
interface OptionDefinition
{
    public const JOBS = 'jobs';
    public const PATH = 'path';
    public const EXCLUDE = 'exclude';
    public const EXTENSIONS = 'extensions';
    public const WARNING = 'warning';
    public const CACHE = 'cache';
    public const NO_CACHE = 'no-cache';
    public const CONFIGURATION = 'configuration';
    public const NO_CONFIGURATION = 'no-configuration';
    public const OPTION_MEMORY_LIMIT = 'memory-limit';
    public const PROGRESS = 'progress';
    public const NO_PROGRESS = 'no-progress';
    public const LOG_JSON = 'log-json';
    public const LOG_JUNIT = 'log-junit';
    public const IGNORE_EXIT_CODE = 'ignore-exit-code';

    public const DEFAULT_JOBS = 5;
    public const DEFAULT_PATH = '.';
    public const DEFAULT_EXCLUDES = [];
    public const DEFAULT_EXTENSIONS = ['php'];
    public const DEFAULT_CACHE_DIR = '.phplint.cache';
    public const DEFAULT_CONFIG_FILE = '.phplint.yml';
    public const DEFAULT_PROGRESS_WIDGET = 'printer';
    public const DEFAULT_STANDARD_OUTPUT_LABEL = 'standard output';
    public const DEFAULT_STANDARD_OUTPUT = 'php://stdout';
}
