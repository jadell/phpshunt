<?php
/**
 * Copyright 2009 iContact Corp.
 * 
 *    Licensed under the Apache License, Version 2.0 (the "License");
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 * 
 *        http://www.apache.org/licenses/LICENSE-2.0
 * 
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an "AS IS" BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 * 
 * @package    Shunt
 * @subpackage Test
 * @author     Josh Adell <jadell@icontact.com>
 * @version    $Id$
 */

class ShuntClassTest extends PHPUnit_Framework_TestCase
{
	protected $sTestClass;
	protected $sChildClass;

	public function setup()
	{
		$this->sTestClass = uniqid('ShuntTester');
		$this->sChildClass = uniqid('ShuntTesterChild');
	}

	// getDeclarationCode()
	public function testGetDeclarationCode_BaseClass_ReturnsCorrectCode()
	{
		eval("
			class {$this->sTestClass}
			{
				public function methodPublic(){}
				protected function methodProtected(){}
				private function methodPrivate(){}
			}");

		$oClassShunt = new ShuntClass($this->sTestClass);
		$sClassCode = $oClassShunt->getDeclarationCode();
		
		$sShuntClassName = $oClassShunt->getName();
		$sBaseClassName = $oClassShunt->getNameBeingShunted();

		$sExpectedDeclaration = "class {$sShuntClassName} extends {$sBaseClassName}";
		self::assertContains($sExpectedDeclaration, $sClassCode, 'Code contains correct class declaration');

		$aExpectedMethods = array(
			'_shuntGet',
			'_shuntSet',
			'_shuntGetStatic',
			'_shuntSetStatic',
			'methodProtectedShunt',
		);
		foreach ($aExpectedMethods as $sExpectedMethod) {
			self::assertContains($sExpectedMethod, $sClassCode, 'Code contains expected method: ' . $sExpectedMethod);
		}
		
		$aUnexpectedMethods = array(
			'methodPublicShunt',
			'methodPrivateShunt',
		);
		foreach ($aUnexpectedMethods as $sUnexpectedMethod) {
			self::assertNotContains($sUnexpectedMethod, $sClassCode, 'Code does not contain unexpected method: ' . $sUnexpectedMethod);
		}
	}

	public function testGetDeclarationCode_ChildClass_ReturnsCorrectCodeWithoutInheritedMethods()
	{
		eval("
			class {$this->sTestClass}
			{
				protected function methodInParent(){}
				protected function methodOverwritten(){}
			}");

		eval("
			class {$this->sChildClass} extends {$this->sTestClass}
			{
				protected function methodOverwritten(){}
				protected function methodInChild(){}
			}");


		$oClassShunt = new ShuntClass($this->sChildClass);
		$sClassCode = $oClassShunt->getDeclarationCode();

		$sShuntClassName = $oClassShunt->getName();
		$sBaseClassName = $oClassShunt->getNameBeingShunted();

		$sExpectedDeclaration = "class {$sShuntClassName} extends {$sBaseClassName}";
		self::assertContains($sExpectedDeclaration, $sClassCode, 'Code contains correct class declaration');

		$aExpectedMethods = array(
			'_shuntGet',
			'_shuntSet',
			'_shuntGetStatic',
			'_shuntSetStatic',
			// inherited but overwritten
			'methodOverwrittenShunt',
			// child only method
			'methodInChildShunt',
		);
		foreach ($aExpectedMethods as $sExpectedMethod) {
			self::assertContains($sExpectedMethod, $sClassCode, 'Code contains expected method: ' . $sExpectedMethod);
		}
		
		$aUnexpectedMethods = array(
			// inherited and not overwritten
			'methodInParentShunt',
			
		);
		foreach ($aUnexpectedMethods as $sUnexpectedMethod) {
			self::assertNotContains($sUnexpectedMethod, $sClassCode, 'Code does not contain unexpected method: ' . $sUnexpectedMethod);
		}
	}

	public function testGetDeclarationCode_AbstractClass_ReturnsCorrectCode()
	{
		eval("
			abstract class {$this->sTestClass}
			{
				abstract public function methodPublicAbstract();
				abstract protected function methodProtectedAbstract();
			}");

		$oClassShunt = new ShuntClass($this->sTestClass);
		$sClassCode = $oClassShunt->getDeclarationCode();
		
		$aExpectedMethods = array(
			'_shuntGet',
			'_shuntSet',
			'_shuntGetStatic',
			'_shuntSetStatic',
			'methodPublicAbstract',
			'methodProtectedAbstract',
		);
		foreach ($aExpectedMethods as $sExpectedMethod) {
			self::assertContains($sExpectedMethod, $sClassCode, 'Code contains expected method: ' . $sExpectedMethod);
		}
	}

	// getName()
	public function testGetName_ReturnsCorrectName()
	{
		eval("class {$this->sTestClass}{}");
		$oClassShunt = new ShuntClass($this->sTestClass);

		$sExpected = ShuntClass::ClassPrefix . $this->sTestClass . ShuntClass::ClassSuffix;
		$sResult = $oClassShunt->getName();
		self::assertEquals($sExpected, $sResult, 'Shunt class name matches expected.');
	}

	// getNameBeingShunted()
	public function testGetNameBeingShunted_ReturnsCorrectName()
	{
		eval("class {$this->sTestClass}{}");
		$oClassShunt = new ShuntClass($this->sTestClass);

		$sResult = $oClassShunt->getNameBeingShunted();
		self::assertEquals($this->sTestClass, $sResult, 'Class name matches expected.');
	}
}
?>