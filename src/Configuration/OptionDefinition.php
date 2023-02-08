<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
interface OptionDefinition
{
    public const OPTION_JOBS = 'jobs';
    public const OPTION_PATH = 'path';
    public const OPTION_EXCLUDE = 'exclude';
    public const OPTION_EXTENSIONS = 'extensions';
    public const OPTION_WARNING = 'warning';
    public const OPTION_CACHE = 'cache';
    public const OPTION_NO_CACHE = 'no-cache';
    public const OPTION_CONFIG_FILE = 'configuration';
    public const OPTION_NO_CONFIG_FILE = 'no-configuration';
    public const OPTION_MEMORY_LIMIT = 'memory-limit';
    public const OPTION_PROGRESS = 'progress';
    public const OPTION_NO_PROGRESS = 'no-progress';
    public const OPTION_JSON_FILE = 'log-json';
    public const OPTION_JUNIT_FILE = 'log-junit';
    public const OPTION_IGNORE_EXIT_CODE = 'ignore-exit-code';

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
