<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyStrposLowerRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\NullsafeMethodCall\CleanupUnneededNullsafeOperatorRector;
use Rector\CodeQuality\Rector\Switch_\SwitchTrueToIfRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\CodingStyle\Rector\ClassConst\SplitGroupedClassConstantsRector;
use Rector\CodingStyle\Rector\ClassMethod\FuncGetArgsToVariadicParamRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\CodeQuality\Rector\BinaryOp\ResponseStatusCodeRector;
use Rector\Symfony\CodeQuality\Rector\Class_\MakeCommandLazyRector;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\ActionSuffixRemoverRector;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\ResponseReturnTypeControllerActionRector;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\TemplateAnnotationToThisRenderRector;
use Rector\Symfony\CodeQuality\Rector\MethodCall\LiteralGetToRequestClassConstantRector;
use Rector\Symfony\Set\TwigSetList;
use Rector\Symfony\Twig134\Rector\Return_\SimpleFunctionAndFilterRector;
use Rector\Transform\Rector\String_\StringToClassConstantRector;
use Rector\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromStrictScalarReturnsRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/server-status',
        __DIR__ . '/server-status/admin',
        __DIR__ . '/server-status/api',
        __DIR__ . '/server-status/classes',
        __DIR__ . '/server-status/libs',
    ]);

    $rectorConfig->rules(
        [
            InlineConstructorDefaultToPropertyRector::class,
            ActionSuffixRemoverRector::class,
            ChangeArrayPushToArrayAssignRector::class,
            ChangeIfElseValueAssignToEarlyReturnRector::class,
            ChangeNestedForeachIfsToEarlyContinueRector::class,
            CombineIfRector::class,
            CountArrayToEmptyArrayComparisonRector::class,
            DeclareStrictTypesRector::class,
            StringToClassConstantRector::class,
            FuncGetArgsToVariadicParamRector::class,
            InlineIfToExplicitIfRector::class,
            LiteralGetToRequestClassConstantRector::class,
            MakeCommandLazyRector::class,
            MakeInheritedMethodVisibilitySameAsParentRector::class,
            PreparedValueToEarlyReturnRector::class,
            RemoveAlwaysElseRector::class,
            ResponseReturnTypeControllerActionRector::class,
            ResponseStatusCodeRector::class,
            ShortenElseIfRector::class,
            SimpleFunctionAndFilterRector::class,
            SimplifyEmptyArrayCheckRector::class,
            SimplifyIfElseToTernaryRector::class,
            SimplifyIfReturnBoolRector::class,
            SimplifyRegexPatternRector::class,
            SimplifyStrposLowerRector::class,
            SimplifyUselessVariableRector::class,
            SplitGroupedClassConstantsRector::class,
            TemplateAnnotationToThisRenderRector::class,
            UnnecessaryTernaryExpressionRector::class,
            UnusedForeachValueToArrayKeysRector::class,
            IfIssetToCoalescingRector::class,
            CleanupUnneededNullsafeOperatorRector::class,
            BoolReturnTypeFromStrictScalarReturnsRector::class,
            SwitchTrueToIfRector::class,
        ]
    );

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_74,
        TwigSetList::TWIG_240,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::NAMING,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::PHP_74,
    ]);
};
