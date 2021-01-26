<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace MolliePrefix\PhpCsFixer\Fixer\Semicolon;

use MolliePrefix\PhpCsFixer\AbstractFixer;
use MolliePrefix\PhpCsFixer\FixerDefinition\CodeSample;
use MolliePrefix\PhpCsFixer\FixerDefinition\FixerDefinition;
use MolliePrefix\PhpCsFixer\Tokenizer\Tokens;
use MolliePrefix\PhpCsFixer\Tokenizer\TokensAnalyzer;
/**
 * @author SpacePossum
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class NoEmptyStatementFixer extends \MolliePrefix\PhpCsFixer\AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new \MolliePrefix\PhpCsFixer\FixerDefinition\FixerDefinition('Remove useless (semicolon) statements.', [new \MolliePrefix\PhpCsFixer\FixerDefinition\CodeSample("<?php \$a = 1;;\n"), new \MolliePrefix\PhpCsFixer\FixerDefinition\CodeSample("<?php echo 1;2;\n"), new \MolliePrefix\PhpCsFixer\FixerDefinition\CodeSample("<?php while(foo()){\n    continue 1;\n}\n")]);
    }
    /**
     * {@inheritdoc}
     *
     * Must run before BracesFixer, CombineConsecutiveUnsetsFixer, MultilineWhitespaceBeforeSemicolonsFixer, NoExtraBlankLinesFixer, NoSinglelineWhitespaceBeforeSemicolonsFixer, NoTrailingWhitespaceFixer, NoUselessElseFixer, NoUselessReturnFixer, NoWhitespaceInBlankLineFixer, ReturnAssignmentFixer, SpaceAfterSemicolonFixer, SwitchCaseSemicolonToColonFixer.
     * Must run after NoUselessSprintfFixer.
     */
    public function getPriority()
    {
        return 26;
    }
    /**
     * {@inheritdoc}
     */
    public function isCandidate(\MolliePrefix\PhpCsFixer\Tokenizer\Tokens $tokens)
    {
        return $tokens->isTokenKindFound(';');
    }
    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, \MolliePrefix\PhpCsFixer\Tokenizer\Tokens $tokens)
    {
        for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
            if ($tokens[$index]->isGivenKind([\T_BREAK, \T_CONTINUE])) {
                $index = $tokens->getNextMeaningfulToken($index);
                if ($tokens[$index]->equals([\T_LNUMBER, '1'])) {
                    $tokens->clearTokenAndMergeSurroundingWhitespace($index);
                }
                continue;
            }
            // skip T_FOR parenthesis to ignore double `;` like `for ($i = 1; ; ++$i) {...}`
            if ($tokens[$index]->isGivenKind(\T_FOR)) {
                $index = $tokens->findBlockEnd(\MolliePrefix\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $tokens->getNextMeaningfulToken($index)) + 1;
                continue;
            }
            if (!$tokens[$index]->equals(';')) {
                continue;
            }
            $previousMeaningfulIndex = $tokens->getPrevMeaningfulToken($index);
            // A semicolon can always be removed if it follows a semicolon, '{' or opening tag.
            if ($tokens[$previousMeaningfulIndex]->equalsAny(['{', ';', [\T_OPEN_TAG]])) {
                $tokens->clearTokenAndMergeSurroundingWhitespace($index);
                continue;
            }
            // A semicolon might be removed if it follows a '}' but only if the brace is part of certain structures.
            if ($tokens[$previousMeaningfulIndex]->equals('}')) {
                $this->fixSemicolonAfterCurlyBraceClose($tokens, $index, $previousMeaningfulIndex);
                continue;
            }
            // A semicolon might be removed together with its noop statement, for example "<?php 1;"
            $prePreviousMeaningfulIndex = $tokens->getPrevMeaningfulToken($previousMeaningfulIndex);
            if ($tokens[$prePreviousMeaningfulIndex]->equalsAny([';', '{', '}', [\T_OPEN_TAG]]) && $tokens[$previousMeaningfulIndex]->isGivenKind([\T_CONSTANT_ENCAPSED_STRING, \T_DNUMBER, \T_LNUMBER, \T_STRING, \T_VARIABLE])) {
                $tokens->clearTokenAndMergeSurroundingWhitespace($index);
                $tokens->clearTokenAndMergeSurroundingWhitespace($previousMeaningfulIndex);
            }
        }
    }
    /**
     * Fix semicolon after closing curly brace if needed.
     *
     * Test for the following cases
     * - just '{' '}' block (following open tag or ';')
     * - if, else, elseif
     * - interface, trait, class (but not anonymous)
     * - catch, finally (but not try)
     * - for, foreach, while (but not 'do - while')
     * - switch
     * - function (declaration, but not lambda)
     * - declare (with '{' '}')
     * - namespace (with '{' '}')
     *
     * @param int $index           Semicolon index
     * @param int $curlyCloseIndex
     */
    private function fixSemicolonAfterCurlyBraceClose(\MolliePrefix\PhpCsFixer\Tokenizer\Tokens $tokens, $index, $curlyCloseIndex)
    {
        static $beforeCurlyOpeningKinds = null;
        if (null === $beforeCurlyOpeningKinds) {
            $beforeCurlyOpeningKinds = [\T_ELSE, \T_FINALLY, \T_NAMESPACE, \T_OPEN_TAG];
        }
        $curlyOpeningIndex = $tokens->findBlockStart(\MolliePrefix\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyCloseIndex);
        $beforeCurlyOpeningIndex = $tokens->getPrevMeaningfulToken($curlyOpeningIndex);
        if ($tokens[$beforeCurlyOpeningIndex]->isGivenKind($beforeCurlyOpeningKinds) || $tokens[$beforeCurlyOpeningIndex]->equalsAny([';', '{', '}'])) {
            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            return;
        }
        // check for namespaces and class, interface and trait definitions
        if ($tokens[$beforeCurlyOpeningIndex]->isGivenKind(\T_STRING)) {
            $classyTestIndex = $tokens->getPrevMeaningfulToken($beforeCurlyOpeningIndex);
            while ($tokens[$classyTestIndex]->equals(',') || $tokens[$classyTestIndex]->isGivenKind([\T_STRING, \T_NS_SEPARATOR, \T_EXTENDS, \T_IMPLEMENTS])) {
                $classyTestIndex = $tokens->getPrevMeaningfulToken($classyTestIndex);
            }
            $tokensAnalyzer = new \MolliePrefix\PhpCsFixer\Tokenizer\TokensAnalyzer($tokens);
            if ($tokens[$classyTestIndex]->isGivenKind(\T_NAMESPACE) || $tokens[$classyTestIndex]->isClassy() && !$tokensAnalyzer->isAnonymousClass($classyTestIndex)) {
                $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            }
            return;
        }
        // early return check, below only control structures with conditions are fixed
        if (!$tokens[$beforeCurlyOpeningIndex]->equals(')')) {
            return;
        }
        $openingBraceIndex = $tokens->findBlockStart(\MolliePrefix\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $beforeCurlyOpeningIndex);
        $beforeOpeningBraceIndex = $tokens->getPrevMeaningfulToken($openingBraceIndex);
        if ($tokens[$beforeOpeningBraceIndex]->isGivenKind([\T_IF, \T_ELSEIF, \T_FOR, \T_FOREACH, \T_WHILE, \T_SWITCH, \T_CATCH, \T_DECLARE])) {
            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            return;
        }
        // check for function definition
        if ($tokens[$beforeOpeningBraceIndex]->isGivenKind(\T_STRING)) {
            $beforeStringIndex = $tokens->getPrevMeaningfulToken($beforeOpeningBraceIndex);
            if ($tokens[$beforeStringIndex]->isGivenKind(\T_FUNCTION)) {
                $tokens->clearTokenAndMergeSurroundingWhitespace($index);
                // implicit return
            }
        }
    }
}