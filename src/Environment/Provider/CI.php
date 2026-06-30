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

namespace Overtrue\PHPLint\Environment\Provider;

use OndraM\CiDetector\CiDetector;
use OndraM\CiDetector\Exception\CiNotDetectedException;
use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class CI implements ProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function describe(): ?array
    {
        $packageName = 'ondram/ci-detector';

        // @see https://getcomposer.org/doc/07-runtime.md#installed-versions
        if (!\Composer\InstalledVersions::isInstalled($packageName)) {
            $this->logger->warning(
                'Package "{packageName}" is not installed.',
                ['packageName' => $packageName]
            );
            return null;
        }

        $ciDetector = new CiDetector();

        if (!$ciDetector->isCiDetected()) {
            $this->logger->warning(
                'Package "{packageName}" does not detect any CI environment.',
                ['packageName' => $packageName]
            );
            return null;
        }

        try {
            $ci = $ciDetector->detect();
        } catch (CiNotDetectedException $e) {
            $this->logger->error($e->getMessage());
            return null;
        }

        $data = [];
        foreach ($ci->describe() as $setting => $value) {
            $data[] = new ProviderData($setting, $value);
        }
        return $data;
    }
}
