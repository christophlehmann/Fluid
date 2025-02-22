<?php

declare(strict_types=1);

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace TYPO3Fluid\Fluid\Tests\Unit\Core\Parser\SyntaxTree;

use TYPO3Fluid\Fluid\Core\Parser\Exception;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture;
use TYPO3Fluid\Fluid\Tests\UnitTestCase;

class TextNodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderReturnsSameStringAsGivenInConstructor(): void
    {
        $string = 'I can work quite effectively in a train!';
        $node = new TextNode($string);
        $renderingContext = new RenderingContextFixture();
        self::assertEquals($node->evaluate($renderingContext), $string, 'The rendered string of a text node is not the same as the string given in the constructor.');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfNoStringGiven(): void
    {
        $this->expectException(Exception::class);
        new TextNode(123);
    }
}
