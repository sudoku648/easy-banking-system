<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\FunctionDeclarationArgumentSpacingSniff;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use PhpCsFixer\Fixer\Operator\TernaryToNullCoalescingFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocVarAnnotationCorrectOrderFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\Semicolon\SpaceAfterSemicolonFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return function (ECSConfig $ecsConfig): void {
    $ecsConfig->sets([
        SetList::CLEAN_CODE,
        SetList::PSR_12,
    ]);

    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/config',
        __FILE__,
    ]);

    $ecsConfig->ruleWithConfiguration(ArraySyntaxFixer::class, [
        'syntax' => 'short',
    ]);

    $ecsConfig->ruleWithConfiguration(SpaceAfterSemicolonFixer::class, [
        'remove_in_empty_for_expressions' => true,
    ]);

    $ecsConfig->ruleWithConfiguration(GlobalNamespaceImportFixer::class, [
        'import_classes' => false,
        'import_functions' => false,
        'import_constants' => false,
    ]);

    $ecsConfig->ruleWithConfiguration(VisibilityRequiredFixer::class, [
        'elements' => ['const', 'property', 'method'],
    ]);

    $ecsConfig->ruleWithConfiguration(ListSyntaxFixer::class, [
        'syntax' => 'short',
    ]);

    $ecsConfig->ruleWithConfiguration(NoSuperfluousPhpdocTagsFixer::class, [
        'allow_mixed' => true,
    ]);

    $ecsConfig->ruleWithConfiguration(PhpdocTypesOrderFixer::class, [
        'null_adjustment' => 'always_last',
        'sort_algorithm' => 'none',
    ]);

    $ecsConfig->ruleWithConfiguration(PhpUnitMethodCasingFixer::class, [
        'case' => 'camel_case',
    ]);

    $ecsConfig->ruleWithConfiguration(TrailingCommaInMultilineFixer::class, [
        'elements' => ['arrays', 'arguments', 'parameters'],
    ]);

    $ecsConfig->ruleWithConfiguration(FunctionDeclarationArgumentSpacingSniff::class, [
        'equalsSpacing' => 1,
    ]);

    $ecsConfig->rules([
        FullyQualifiedStrictTypesFixer::class,
        OrderedImportsFixer::class,
        ArrayIndentationFixer::class,
        ExplicitStringVariableFixer::class,
        PhpdocOrderFixer::class,
        PhpdocVarAnnotationCorrectOrderFixer::class,
        NoUnusedImportsFixer::class,
        TernaryToNullCoalescingFixer::class,
        DeclareStrictTypesFixer::class,
        SingleBlankLineAtEofFixer::class,
    ]);
};
