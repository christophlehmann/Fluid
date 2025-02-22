<?php

declare(strict_types=1);

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace TYPO3Fluid\Fluid\Tests\Unit\Core\Compiler;

use TYPO3Fluid\Fluid\Core\Cache\SimpleFileCache;
use TYPO3Fluid\Fluid\Core\Compiler\NodeConverter;
use TYPO3Fluid\Fluid\Core\Compiler\StopCompilingException;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture;
use TYPO3Fluid\Fluid\Tests\UnitTestCase;

class TemplateCompilerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testConstructorCreatesNodeConverter(): void
    {
        $instance = new TemplateCompiler();
        self::assertAttributeInstanceOf(NodeConverter::class, 'nodeConverter', $instance);
    }

    /**
     * @test
     */
    public function testWarmupModeToggle(): void
    {
        $instance = new TemplateCompiler();
        $instance->enterWarmupMode();
        self::assertAttributeSame(TemplateCompiler::MODE_WARMUP, 'mode', $instance);
        self::assertTrue($instance->isWarmupMode());
    }

    /**
     * @test
     */
    public function testSetRenderingContext(): void
    {
        $instance = new TemplateCompiler();
        $renderingContext = new RenderingContextFixture();
        $instance->setRenderingContext($renderingContext);
        self::assertAttributeSame($renderingContext, 'renderingContext', $instance);
    }

    /**
     * @test
     */
    public function testHasReturnsFalseWithoutCache(): void
    {
        $instance = $this->getMock(TemplateCompiler::class, ['sanitizeIdentifier']);
        $renderingContext = $this->getMock(RenderingContextFixture::class, ['getCache']);
        $renderingContext->cacheDisabled = true;
        $renderingContext->expects(self::never())->method('getCache');
        $instance->setRenderingContext($renderingContext);
        $instance->expects(self::once())->method('sanitizeIdentifier')->willReturn('');
        $result = $instance->has('test');
        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function testHasAsksCache(): void
    {
        $cache = $this->getMock(SimpleFileCache::class, ['get']);
        $cache->expects(self::once())->method('get')->with('test')->willReturn(true);
        $renderingContext = new RenderingContextFixture();
        $renderingContext->setCache($cache);
        $instance = $this->getMock(TemplateCompiler::class, ['sanitizeIdentifier']);
        $instance->expects(self::once())->method('sanitizeIdentifier')->willReturnArgument(0);
        $instance->setRenderingContext($renderingContext);
        $result = $instance->has('test');
        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function testWrapViewHelperNodeArgumentEvaluationInClosure(): void
    {
        $instance = new TemplateCompiler();
        $arguments = ['value' => new TextNode('sometext')];
        $renderingContext = new RenderingContextFixture();
        $viewHelperNode = new ViewHelperNode($renderingContext, 'f', 'format.raw', $arguments, new ParsingState());
        $result = $instance->wrapViewHelperNodeArgumentEvaluationInClosure($viewHelperNode, 'value');
        $expected = 'function() use ($renderingContext, $self) {' . chr(10);
        $expected .= chr(10);
        $expected .= 'return \'sometext\';' . chr(10);
        $expected .= '}';
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function testStoreReturnsEarlyIfDisabled(): void
    {
        $renderingContext = new RenderingContextFixture();
        $renderingContext->cacheDisabled = true;
        $instance = $this->getMock(TemplateCompiler::class, ['generateSectionCodeFromParsingState']);
        $instance->setRenderingContext($renderingContext);
        $instance->expects(self::never())->method('generateSectionCodeFromParsingState');
        $instance->store('foobar', new ParsingState());
    }

    /**
     * @test
     */
    public function testSupportsDisablingCompiler(): void
    {
        $this->expectException(StopCompilingException::class);
        $instance = new TemplateCompiler();
        $instance->disable();
    }

    /**
     * @test
     */
    public function testGetNodeConverterReturnsNodeConverterInstance(): void
    {
        $instance = new TemplateCompiler();
        self::assertInstanceOf(NodeConverter::class, $instance->getNodeConverter());
    }

    /**
     * @test
     */
    public function testStoreSavesUncompilableState(): void
    {
        $cacheMock = $this->getMockBuilder(SimpleFileCache::class)->onlyMethods(['set'])->getMock();
        $cacheMock->expects(self::once())->method('set')->with('fakeidentifier', self::anything());
        $renderingContext = new RenderingContextFixture();
        $renderingContext->setCache($cacheMock);
        $state = new ParsingState();
        $state->setCompilable(false);
        $instance = new TemplateCompiler();
        $instance->setRenderingContext($renderingContext);
        $instance->store('fakeidentifier', $state);
    }

    /**
     * @test
     */
    public function testVariableNameDelegatesToNodeConverter(): void
    {
        $instance = new TemplateCompiler();
        $nodeConverter = $this->getMock(NodeConverter::class, ['variableName'], [$instance]);
        $nodeConverter->expects(self::once())->method('variableName')->willReturnArgument(0);
        $instance->setNodeConverter($nodeConverter);
        self::assertEquals('foobar', $instance->variableName('foobar'));
    }

    /**
     * @test
     */
    public function testGetRenderingContextGetsRenderingContext(): void
    {
        $context = new RenderingContextFixture();
        $instance = new TemplateCompiler();
        $instance->setRenderingContext($context);
        self::assertSame($context, $instance->getRenderingContext());
    }
}
