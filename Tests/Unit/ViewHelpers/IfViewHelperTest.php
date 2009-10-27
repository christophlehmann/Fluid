<?php
declare(ENCODING = 'utf-8');
namespace F3\Fluid\ViewHelpers;

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for IfViewHelper
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class IfViewHelperTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRendersChildrenIfConditionIsTrueAndNoThenViewHelperChildExists() {
		$mockViewHelper = $this->getMock('F3\Fluid\ViewHelpers\IfViewHelper', array('renderChildren'));
		$mockViewHelper->expects($this->at(0))->method('renderChildren')->will($this->returnValue('foo'));

		$actualResult = $mockViewHelper->render(TRUE);
		$this->assertEquals('foo', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRendersThenViewHelperChildIfConditionIsTrueAndThenViewHelperChildExists() {
		$renderingContext = $this->getMock('F3\Fluid\Core\Rendering\RenderingContext');

		$mockThenViewHelperNode = $this->getMock('F3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode', array('getViewHelperClassName', 'evaluate', 'setRenderingContext'), array(), '', FALSE);
		$mockThenViewHelperNode->expects($this->at(0))->method('getViewHelperClassName')->will($this->returnValue('F3\Fluid\ViewHelpers\ThenViewHelper'));
		$mockThenViewHelperNode->expects($this->at(1))->method('setRenderingContext')->with($renderingContext);
		$mockThenViewHelperNode->expects($this->at(2))->method('evaluate')->will($this->returnValue('ThenViewHelperResults'));

		$viewHelper = new \F3\Fluid\ViewHelpers\IfViewHelper();
		$viewHelper->setChildNodes(array($mockThenViewHelperNode));
		$viewHelper->setRenderingContext($renderingContext);
		$actualResult = $viewHelper->render(TRUE);
		$this->assertEquals('ThenViewHelperResults', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyStringIfConditionIsFalseAndNoThenViewHelperChildExists() {
		$viewHelper = new \F3\Fluid\ViewHelpers\IfViewHelper();

		$actualResult = $viewHelper->render(FALSE);
		$this->assertEquals('', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRendersElseViewHelperChildIfConditionIsFalseAndNoThenViewHelperChildExists() {
		$renderingContext = $this->getMock('F3\Fluid\Core\Rendering\RenderingContext');

		$mockElseViewHelperNode = $this->getMock('F3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode', array('getViewHelperClassName', 'evaluate', 'setRenderingContext'), array(), '', FALSE);
		$mockElseViewHelperNode->expects($this->at(0))->method('getViewHelperClassName')->will($this->returnValue('F3\Fluid\ViewHelpers\ElseViewHelper'));
		$mockElseViewHelperNode->expects($this->at(1))->method('setRenderingContext')->with($renderingContext);
		$mockElseViewHelperNode->expects($this->at(2))->method('evaluate')->will($this->returnValue('ElseViewHelperResults'));

		$viewHelper = new \F3\Fluid\ViewHelpers\IfViewHelper();
		$viewHelper->setChildNodes(array($mockElseViewHelperNode));
		$viewHelper->setRenderingContext($this->getMock('F3\Fluid\Core\Rendering\RenderingContext'));

		$actualResult = $viewHelper->render(FALSE);
		$this->assertEquals('ElseViewHelperResults', $actualResult);
	}
}

?>
