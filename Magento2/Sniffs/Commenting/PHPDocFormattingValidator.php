<?php

/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento2\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;

/**
 * Helper class for common DocBlock validations
 */
class PHPDocFormattingValidator
{
    /**
     * Finds matching PHPDoc for current pointer
     *
     * @param int $startPtr
     * @param File $phpcsFile
     * @return int
     */
    public function findPHPDoc($startPtr, $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();

        $commentStartPtr = $phpcsFile->findPrevious(
            [
                T_WHITESPACE,
                T_DOC_COMMENT_STAR,
                T_DOC_COMMENT_WHITESPACE,
                T_DOC_COMMENT_TAG,
                T_DOC_COMMENT_STRING,
                T_DOC_COMMENT_CLOSE_TAG
            ],
            $startPtr - 1,
            null,
            true,
            null,
            true
        );

        if ($tokens[$commentStartPtr]['code'] !== T_DOC_COMMENT_OPEN_TAG) {
            return -1;
        }

        return $commentStartPtr;
    }

    /**
     * Determines if the comment identified by $commentStartPtr provides additional meaning to origin at $namePtr
     *
     * @param int $namePtr
     * @param int $commentStartPtr
     * @param array $tokens
     * @return bool
     */
    public function providesMeaning($namePtr, $commentStartPtr, $tokens)
    {
        $commentCloserPtr = $tokens[$commentStartPtr]['comment_closer'];
        $name = strtolower(str_replace([' ', '"', '_'], '', $tokens[$namePtr]['content']));

        $hasTags = false;
        $hasDescription = false;

        for ($i = $commentStartPtr; $i <= $commentCloserPtr; $i++) {
            $token = $tokens[$i];

            // Important, but not the string we are looking for
            if ($token['code'] === T_DOC_COMMENT_TAG) {
                $hasTags = true;
                continue;
            }

            // Not an interesting string
            if ($token['code'] !== T_DOC_COMMENT_STRING) {
                continue;
            }

            // Wrong kind of string
            if ($tokens[$i - 2]['code'] === T_DOC_COMMENT_TAG) {
                continue;
            }

            $hasDescription = true;

            // Comment is the same as the origin name
            $docComment = str_replace(['_', ' ', '.', ','], '', strtolower($token['content']));
            if ($docComment === $name) {
                continue;
            }

            // Only difference is word Class or Interface
            $docComment = str_replace(['class', 'interface'], '', $docComment);
            if ($docComment === $name) {
                continue;
            }

            // We have found at lease one meaningful line in comment description
            return true;
        }

        // Contains nothing but the tags
        if ($hasTags === true && $hasDescription === false) {
            return true;
        }

        return false;
    }
}
