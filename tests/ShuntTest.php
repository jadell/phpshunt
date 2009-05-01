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

class ShuntTest extends PHPUnit_Framework_TestCase
{
	protected $sTestClass;
	protected $sChildClass;

	public function setup()
	{
		$this->sTestClass = uniqid('ShuntTester');
		$this->sChildClass = uniqid('ShuntTesterChild');
	}

	// generate()
	public function testGenerate_ExistingClassShunted_ReturnsCorrectNameAndShuntClassExists()
	{
		eval("class {$this->sTestClass}{}");

		$sResult = Shunt::generate($this->sTestClass);

		$oReflection = new ShuntClass($this->sTestClass);
		$sExpected = $oReflection->getName();

		self::assertEquals($sExpected, $sResult, 'Shunt class name matches expected');
		self::assertTrue(class_exists($sResult, false), 'Shunt class exists');
	}

	public function testGenerate_ExistingClassMultipleTimes_ReturnsSameNameEachTime()
	{
		eval("class {$this->sTestClass}{}");

		$sExpected = Shunt::generate($this->sTestClass);

		for ($i = 0; $i < 3; $i++) {
			$sResult = Shunt::generate($this->sTestClass);
			self::assertEquals($sExpected, $sResult, 'Shunt name matches on generate attempt ' . ($i+1));
		}
	}

	public function testGenerate_NonExistingClassShunted_ThrowsException()
	{
		$sTestClass = 'ClassDoesNotExist';

		self::setExpectedException('ShuntException');
		Shunt::generate($sTestClass);
	}

	// get()
	public function testGet_ExistingClassWithArgs_ReturnsShuntedClassWithSetProperties()
	{
		eval("
			class {$this->sTestClass}
			{
				public \$iValue;

				public function __construct(\$iValue)
				{
					\$this->iValue = \$iValue;
				}
			}
		");

		$oReflection = new ShuntClass($this->sTestClass);
		$sShuntClass = $oReflection->getName();

		$iValue = 123;
		$oTest = Shunt::get($this->sTestClass, array($iValue));

		$iResultProperty = $oTest->_shuntGet('iValue');
		
		self::assertType($sShuntClass, $oTest, 'Class has expected type');
		self::assertEquals($iResultProperty, $iValue, 'Constructor properly set value');
	}

	public function testGet_ClassWIthNoConstructor_ReturnsShuntedClass()
	{
		eval("class {$this->sTestClass}{}");
		$oTest = Shunt::get($this->sTestClass);
		self::assertType($this->sTestClass, $oTest, 'Class has expected type');
	}

	public function testGet_AbstractClass_ReturnsShuntedClass()
	{
		eval("abstract class {$this->sTestClass}{ abstract protected function methodAbstract(); }");
		$oTest = Shunt::get($this->sTestClass);
		self::assertType($this->sTestClass, $oTest, 'Class has expected type');
		
		self::setExpectedException('ShuntException');
		$oTest->methodAbstract();
	}

	////////////////////////////////////////////////////////////////////////////////
	// The following tests work with the actual shunted classes
	// to prove that the shunt class properly passes through to the
	// class being shunted
	////////////////////////////////////////////////////////////////////////////////
	
	public function testShunt_ShuntGetAndShuntSet_CanSetAndInspectProtectedValues()
	{
		eval("
			class {$this->sTestClass}
			{
				protected \$iValue;

				public function __construct(\$iValue)
				{
					\$this->iValue = \$iValue;
				}
			}
		");

		$iOldValue = 123;
		$iNewValue = 987;
		$oTest = Shunt::get($this->sTestClass, array($iOldValue));

		self::assertEquals($iOldValue, $oTest->_shuntGet('iValue'), 'Protected property is inspectable');
		
		$oTest->_shuntSet('iValue', $iNewValue);
		self::assertEquals($iNewValue, $oTest->_shuntGet('iValue'), 'Protected property is settable');
	}

	public function testShunt_ShuntGetStaticAndShuntSetStatic_CanSetAndInspectStaticProtectedValues()
	{
		eval("
			class {$this->sTestClass}
			{
				protected static \$iValueStatic;
			}
		");

		$iOldStaticValue = 456;
		$iNewStaticValue = 987;
		
		$sTestClass = Shunt::generate($this->sTestClass);

		call_user_func(array($sTestClass, '_shuntSetStatic'), 'iValueStatic', $iOldStaticValue);
		$iResultValue = call_user_func(array($sTestClass, '_shuntGetStatic'), 'iValueStatic');
		self::assertEquals($iResultValue, $iOldStaticValue, 'Static protected property is settable and inspectable');

		call_user_func(array($sTestClass, '_shuntSetStatic'), 'iValueStatic', $iNewStaticValue);
		$iResultValue = call_user_func(array($sTestClass, '_shuntGetStatic'), 'iValueStatic');
		self::assertEquals($iResultValue, $iNewStaticValue, 'Static protected property can be reset');

	}

	public function testShunt_MethodWithDefault_CalledAndReturnsCorrectValuesArray()
	{
		eval("
			class {$this->sTestClass}
			{
				protected function methodWithDefault(\$arg0, \$arg1=1)
				{
					return array(\$arg0, \$arg1);
				}
			}
		");

		$oTest = Shunt::get($this->sTestClass);

		$arg0 = 'test0';
		$arg1 = 9;

		$aExpected = array($arg0, $arg1);
		$aResult = $oTest->methodWithDefaultShunt($arg0, $arg1);
		self::assertEquals($aExpected, $aResult, 'Result array expected with both given');

		$aExpected = array($arg0, 1);
		$aResult = $oTest->methodWithDefaultShunt($arg0);
		self::assertEquals($aExpected, $aResult, 'Result array expected with one given');
	}

	public function testShunt_MethodWithReference_CalledAndReturnsCorrectValuesArray()
	{
		eval("
			class {$this->sTestClass}
			{
				protected function methodWithReference(&\$arg0)
				{
					\$arg0 = 'blarg!';
					return array(\$arg0);
				}
			}
		");

		$oTest = Shunt::get($this->sTestClass, array(123));

		$arg0 = 'test0';
		$sExpectedArg0 = 'blarg!';
		$aExpected = array($sExpectedArg0);

		$aResult = $oTest->methodWithReferenceShunt($arg0);
		self::assertEquals($aExpected, $aResult, 'Result array expected');
		self::assertEquals($aExpected[0], $arg0, 'Argument passed by reference modified as expected');
	}

	public function testShunt_MethodWithManyArgTypes_CalledAndReturnsCorrectValuesArray()
	{
		eval("
			class {$this->sTestClass}
			{
				protected function methodWithManyArgTypes(\$arg0=true, \$arg1=false, \$arg2=null, \$arg3='somestring', \$arg4=array())
				{
					return array(\$arg0, \$arg1, \$arg2, \$arg3, \$arg4);
				}
			}
		");

		$oTest = Shunt::get($this->sTestClass, array(123));

		$arg0 = false;
		$arg1 = true;
		$arg2 = -123;
		$arg3 = 'anotherstring';
		$arg4 = array(9,8,7);

		$aExpected = array($arg0, $arg1, $arg2, $arg3, $arg4);
		$aResult = $oTest->methodWithManyArgTypesShunt($arg0, $arg1, $arg2, $arg3, $arg4);
		self::assertEquals($aExpected, $aResult, 'Result array expected with all given');

		$aExpected = array(true, false, null, 'somestring', array());
		$aResult = $oTest->methodWithManyArgTypesShunt();
		self::assertEquals($aExpected, $aResult, 'Result array expected with none given');
	}

	public function testShunt_MethodStatic_CalledAndReturnsCorrectValuesArray()
	{
		eval("
			class {$this->sTestClass}
			{
				protected static function methodStatic(\$arg0)
				{
					return array(\$arg0);
				}
			}
		");

		$oTest = Shunt::get($this->sTestClass, array(123));

		$arg0 = 'test0';

		$aExpected = array($arg0);
		$aResult = call_user_func(get_class($oTest) .'::methodStaticShunt', $arg0);
		self::assertEquals($aExpected, $aResult, 'Result array expected from static call');
	}

	public function testShunt_MethodPrivate_MethodStillPrivate()
	{
		eval("
			class {$this->sTestClass}
			{
				private function methodPrivate(\$arg0)
				{
					return array(\$arg0);
				}
			}
		");

		$oTest = Shunt::get($this->sTestClass, array(123));

		$oMethod = new ReflectionMethod(get_class($oTest), 'methodPrivate');
		self::setExpectedException('ReflectionException', "Trying to invoke private method {$this->sTestClass}::methodPrivate() from scope ReflectionMethod");
		$oMethod->invoke($oTest);
	}
	
	public function testShunt_MethodPrivate_MethodUnshunted()
	{
		eval("
			class {$this->sTestClass}
			{
				private function methodPrivate(\$arg0)
				{
					return array(\$arg0);
				}
			}
		");

		$oTest = Shunt::get($this->sTestClass);

		self::setExpectedException('ReflectionException', "Method {$this->sTestClass}Shunt::methodPrivateShunt() does not exist");
		$oMethod = new ReflectionMethod(get_class($oTest), 'methodPrivateShunt');
	}
	
	public function testShunt_MethodPassToParent_CalledAndReturnsCorrectValuesArray()
	{
		eval("
			class {$this->sTestClass}
			{
				protected function methodWithDefault(\$arg0, \$arg1=1)
				{
					return array(\$arg0, \$arg1);
				}
			}
		");
	
		eval("
			class {$this->sChildClass} extends {$this->sTestClass}
			{
				protected function methodPassToParent(\$arg0)
				{
					return parent::methodWithDefault(\$arg0);
				}
			}
		");
	
		$oTest = Shunt::get($this->sChildClass);

		$arg0 = 'test0';

		$aExpected = array($arg0, 1);
		$aResult = $oTest->methodPassToParentShunt($arg0);
		self::assertEquals($aExpected, $aResult, 'Result array expected from child pass-thru to parent');
	}

	public function testShunt_MethodWithDefaultOverWritten_CalledAndReturnsCorrectValuesArray()
	{
		eval("
			class {$this->sTestClass}
			{
				protected function methodWithDefault(\$arg0, \$arg1=1)
				{
					return array(\$arg0, \$arg1);
				}
			}
		");
	
		eval("
			class {$this->sChildClass} extends {$this->sTestClass}
			{
				protected function methodWithDefault(\$arg0, \$arg1=2)
				{
					return array(\$arg0, \$arg1);
				}
			}
		");

		$oTest = Shunt::get($this->sChildClass);

		$arg0 = 'test0';
		$arg1 = 9;

		$aExpected = array($arg0, $arg1);
		$aResult = $oTest->methodWithDefaultShunt($arg0, $arg1);
		self::assertEquals($aExpected, $aResult, 'Result array expected from overwritten method with both given');

		$aExpected = array($arg0, 2);
		$aResult = $oTest->methodWithDefaultShunt($arg0);
		self::assertEquals($aExpected, $aResult, 'Result array expected from overwritten method with one given');
	}
}
?>