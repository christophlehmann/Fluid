<?php
declare(ENCODING = 'utf-8');
namespace F3::Beer3::Core;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package 
 * @subpackage 
 * @version $Id:$
 */

include_once(__DIR__ . '/Fixtures/F3_Beer3_TestTagBasedViewHelper.php');
/**
 * Testcase for [insert classname here]
 *
 * @package
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TagBasedViewHelperTest extends F3::Testing::BaseTestCase {

	public function setUp() {
		$this->viewHelper = new F3::Beer3::TestTagBasedViewHelper();
	}
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function oneTagAttributeIsRenderedCorrectly() {
		$this->viewHelper->registerTagAttribute('x', 'Description', FALSE);
		$arguments = new F3::Beer3::Core::ViewHelperArguments(array('x' => 'Hallo'));
		$expected = 'x="Hallo"';
		
		$this->viewHelper->arguments = $arguments;
		$this->assertEquals($expected, $this->viewHelper->render(), 'A simple tag attribute was not rendered correctly.');
	}
}



?>