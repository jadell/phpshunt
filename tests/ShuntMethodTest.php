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

class ShuntMethodTest extends PHPUnit_Framework_TestCase
{
	protected $sTestClass;
	protected $sChildClass;

	public function setup()
	{
		$this->sTestClass = uniqid('ShuntTester');
		$this->sChildClass = uniqid('ShuntTesterChild');
	}

	// getDeclarationCode()
	public function testGetDeclarationCode_Socket_ReturnsCorrectCode()
	{
		eval("class {$this->sTestClass}{ protected function someMethod(\$arg0=false){} }");
		$oMethodShunt = new ShuntMethod($this->sTestClass, 'someMethod');

		$sParams = $oMethodShunt->getParameterDeclarations(true);
		$sSignature = $oMethodShunt->getMethodSignature();
		$sCode = $oMethodShunt->getMethodCode();

		$sExpected = "{$sSignature}({$sParams}){$sCode}";

		$sResult = $oMethodShunt->getDeclarationCode();
		self::assertEquals($sExpected, $sResult, 'Shunt method declaration code matches expected.');
	}

	// getMethodCode()
	public function testGetMethodCode_ReturnsCorrectCode()
	{
		eval("class {$this->sTestClass}{ protected function someMethod(\$arg0=false){} }");

		$sMethodName = 'someMethod';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = "{ return parent::{$sMethodName}(\$arg0); }";

		$sResult = $oMethodShunt->getMethodCode();
		self::assertEquals($sExpected, $sResult, 'Shunt method code matches expected.');
	}

	// getMethodSignature()
	public function testGetMethodSignature_PublicMethod_ReturnsCorrectSignature()
	{
		eval("class {$this->sTestClass}{ public function methodPublic(){} }");

		$sMethodName = 'methodPublic';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = 'public function methodPublicShunt';
		$sResult = $oMethodShunt->getMethodSignature();
		self::assertEquals($sExpected, $sResult, 'Shunt method signature matches expected.');
	}

	public function testGetMethodSignature_ProtectedMethod_ReturnsCorrectSignature()
	{
		eval("class {$this->sTestClass}{ protected function methodProtected(){} }");

		$sMethodName = 'methodProtected';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = 'public function methodProtectedShunt';
		$sResult = $oMethodShunt->getMethodSignature();
		self::assertEquals($sExpected, $sResult, 'Shunt method signature matches expected.');
	}

	public function testGetMethodSignature_AbstractMethod_ReturnsCorrectSignature()
	{
		eval("abstract class {$this->sTestClass}{ abstract protected function methodProtectedAbstract(); }");

		$sMethodName = 'methodProtectedAbstract';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = 'public function methodProtectedAbstract';
		$sResult = $oMethodShunt->getMethodSignature();
		self::assertEquals($sExpected, $sResult, 'Shunt method signature matches expected.');
	}

	// getName()
	public function testGetName_ReturnsCorrectName()
	{
		eval("class {$this->sTestClass}{ public function methodPublic(){} }");

		$sMethodName = 'methodPublic';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = ShuntMethod::MethodPrefix . $sMethodName . ShuntMethod::MethodSuffix;
		$sResult = $oMethodShunt->getName();
		self::assertEquals($sExpected, $sResult, 'Shunt method name matches expected.');
	}

	// getNameBeingShunted()
	public function testGetNameBeingShunted_ReturnsCorrectName()
	{
		eval("class {$this->sTestClass}{ public function methodPublic(){} }");

		$sMethodName = 'methodPublic';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sResult = $oMethodShunt->getNameBeingShunted();
		self::assertEquals($sMethodName, $sResult, 'Method name matches expected.');
	}

	// getParameterDeclarations()
	public function testGetParameterDeclarations_AsArray_ReturnsCorrectArrayOfParameters()
	{
		eval("class {$this->sTestClass}{ protected function someMethod(&\$arg0, \$arg1=1, \$arg2='something'){} }");

		$sMethodName = 'someMethod';
		$oMethodShunt = new ShuntMethod($this->sTestClass, 'someMethod');

		$sExpected = array(
			'arg0' => "&\$arg0",
			'arg1' => "\$arg1=1",
			'arg2' => "\$arg2='something'",
		);

		$sResult = $oMethodShunt->getParameterDeclarations();
		self::assertEquals($sExpected, $sResult, 'Parameter declarations match expected.');
	}

	public function testGetParameterDeclarations_AsArrayWithArrayParam_ReturnsCorrectArrayOfParameters()
	{
		eval("class {$this->sTestClass}{ protected function someMethod(\$arg0, \$arg1=array()){} }");

		$sMethodName = 'someMethod';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = array(
			'arg0' => "\$arg0",
			'arg1' => "\$arg1=array (\n)",
		);

		$sResult = $oMethodShunt->getParameterDeclarations();
		self::assertEquals($sExpected, $sResult, 'Parameter declarations match expected.');
	}

	public function testGetParameterDeclarations_AsArrayWithBooleanParams_ReturnsCorrectArrayOfParameters()
	{
		eval("class {$this->sTestClass}{ protected function someMethod(\$arg0=true, \$arg1=false){} }");

		$sMethodName = 'someMethod';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = array(
			'arg0' => "\$arg0=true",
			'arg1' => "\$arg1=false",
		);

		$sResult = $oMethodShunt->getParameterDeclarations();
		self::assertEquals($sExpected, $sResult, 'Parameter declarations match expected.');
	}

	public function testGetParameterDeclarations_AsArrayWithNullParam_ReturnsCorrectArrayOfParameters()
	{
		eval("class {$this->sTestClass}{ protected function someMethod(\$arg0=null){} }");

		$sMethodName = 'someMethod';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = array(
			'arg0' => "\$arg0=NULL",
		);

		$sResult = $oMethodShunt->getParameterDeclarations();
		self::assertEquals($sExpected, $sResult, 'Parameter declarations match expected.');
	}

	public function testGetParameterDeclarations_AsArrayWithReferenceParam_ReturnsCorrectArrayOfParameters()
	{
		eval("class {$this->sTestClass}{ protected function someMethod(&\$arg0){} }");

		$sMethodName = 'someMethod';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = array(
			'arg0' => "&\$arg0",
		);

		$sResult = $oMethodShunt->getParameterDeclarations();
		self::assertEquals($sExpected, $sResult, 'Parameter declarations match expected.');
	}

	public function testGetParameterDeclarations_AsString_ReturnsCorrectParameterString()
	{
		eval("class {$this->sTestClass}{ protected function someMethod(\$arg0, \$arg1=1){} }");

		$sMethodName = 'someMethod';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = '$arg0, $arg1=1';

		$sResult = $oMethodShunt->getParameterDeclarations(true);
		self::assertEquals($sExpected, $sResult, 'Parameter declarations match expected.');
	}

	public function testGetParameterDeclarations_AsMethodArgument_ReturnsCorrectArgumentString()
	{
		eval("class {$this->sTestClass}{ protected function someMethod(&\$arg0, \$arg1=1){} }");

		$sMethodName = 'someMethod';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$sExpected = '$arg0, $arg1';

		$sResult = $oMethodShunt->getParameterDeclarations(true, true);
		self::assertEquals($sExpected, $sResult, 'Parameter declarations match expected.');
	}
	
	// isInherited()
	public function testIsInherited_InheritedMethod_ReturnsTrue()
	{
		eval("class {$this->sTestClass}{ public function methodPublic(){} }");
		eval("class {$this->sChildClass} extends {$this->sTestClass}{}");

		$sMethodName = 'methodPublic';
		$oMethodShunt = new ShuntMethod($this->sChildClass, $sMethodName);

		$bResult = $oMethodShunt->isInherited();
		self::assertTrue($bResult, 'Method is inherited.');
	}

	public function testIsInherited_OverwrittenInheritedMethod_ReturnsFalse()
	{
		eval("class {$this->sTestClass}{ public function methodPublic(){} }");
		eval("class {$this->sChildClass} extends {$this->sTestClass}{ public function methodPublic(){} }");

		$sMethodName = 'methodPublic';
		$oMethodShunt = new ShuntMethod($this->sChildClass, $sMethodName);

		$bResult = $oMethodShunt->isInherited();
		self::assertFalse($bResult, 'Method is not inherited.');
	}

	public function testIsInherited_NonInheritedMethod_ReturnsFalse()
	{
		eval("class {$this->sTestClass}{ public function methodPublic(){} }");
		eval("class {$this->sChildClass} extends {$this->sTestClass}{ public function methodNotInherited(){} }");

		$sMethodName = 'methodNotInherited';
		$oMethodShunt = new ShuntMethod($this->sChildClass, $sMethodName);

		$bResult = $oMethodShunt->isInherited();
		self::assertFalse($bResult, 'Method is not inherited.');
	}

	// isShuntable()
	public function testIsShuntable_ProtectedMethod_ReturnsTrue()
	{
		eval("class {$this->sTestClass}{ protected function methodProtected(){} }");

		$sMethodName = 'methodProtected';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$bResult = $oMethodShunt->isShuntable();
		self::assertTrue($bResult, 'Method can be shunted.');
	}

	public function testIsShuntable_PublicMethod_ReturnsFalse()
	{
		eval("class {$this->sTestClass}{ public function methodPublic(){} }");

		$sMethodName = 'methodPublic';
		$oMethodShunt = new ShuntMethod($this->sTestClass, $sMethodName);

		$bResult = $oMethodShunt->isShuntable();
		self::assertFalse($bResult, 'Method can not be shunted.');
	}

	public function testIsShuntable_InheritedMethod_ReturnsFalse()
	{
		eval("class {$this->sTestClass}{ public function methodPublic(){} }");
		eval("class {$this->sChildClass} extends {$this->sTestClass}{}");

		$sMethodName = 'methodPublic';
		$oMethodShunt = new ShuntMethod($this->sChildClass, $sMethodName);

		$bResult = $oMethodShunt->isShuntable();
		self::assertFalse($bResult, 'Method can not be shunted.');
	}

	public function testIsShuntable_AbstractMethods_ReturnsTrue()
	{
		eval("abstract class {$this->sTestClass}{ abstract public function methodPublic(); abstract protected function methodProtected(); }");

		$sMethodName1 = 'methodPublic';
		$sMethodName2 = 'methodProtected';
		
		$oMethodShunt1 = new ShuntMethod($this->sTestClass, $sMethodName1);
		$oMethodShunt2 = new ShuntMethod($this->sTestClass, $sMethodName2);

		$bResult1 = $oMethodShunt1->isShuntable();
		$bResult2 = $oMethodShunt2->isShuntable();
		self::assertTrue($bResult1, 'Abstract public method can be shunted.');
		self::assertTrue($bResult2, 'Abstract protected method can be shunted.');
	}
}
?>