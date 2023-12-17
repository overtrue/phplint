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

namespace Overtrue\CodingStandard\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * Custom PHP CS Fixer added in context of https://github.com/overtrue/phplint/issues/199
 *
 * @link https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/blob/master/doc/cookbook_fixers.rst
 * @link https://tomasvotruba.com/blog/2017/07/24/how-to-write-custom-fixer-for-php-cs-fixer-24
 *
 * @author Laurent Laville
 * @since Release 9.1.0
 */
final class ApplicationVersionFixer extends AbstractFixer
{
    /**
     * @inheritDoc
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([T_CLASS, T_CONSTANT_ENCAPSED_STRING]);
    }

    /**
     * @inheritDoc
     */
    public function isRisky(): bool
    {
        return false;
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }
            if (!$this->isVersionConst($tokens, $index)) {
                continue;
            }

            $tag = @exec('git describe --tags --abbrev=0 2>&1');

            if ($token->getContent() !== $tag) {
                $tokens[$index] = new Token([$token->getId(), "'$tag'"]);
            }
        }
    }

    private function isVersionConst(Tokens $tokens, int $index): bool
    {
        $prevTokenIndex = $tokens->getPrevMeaningfulToken($index);
        if (!$tokens[$prevTokenIndex]->equals('=')) {
            return false;
        }

        $constantNamePosition = $tokens->getPrevMeaningfulToken($prevTokenIndex);
        return $tokens[$constantNamePosition]->equals([T_STRING, 'VERSION']);
    }

    /**
     * @inheritDoc
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Application::VERSION constant value must match the current git tag.',
            []
        );
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::name();
    }

    public static function name(): string
    {
        return 'OvertrueCsFixer/application_version';
    }

    /**
     * @inheritDoc
     */
    public function supports(SplFileInfo $file): bool
    {
        return $file->getBasename() === 'Application.php';
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 0;
    }
}
