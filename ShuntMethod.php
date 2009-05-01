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
 * @author     Josh Adell <jadell@icontact.com>
 * @version    $Id$
 */

/**
 * Generates code for creating a shunt of a specific method
 */
class ShuntMethod extends ReflectionMethod
{
	const MethodPrefix = '';
	const MethodSuffix = 'Shunt';

	/**
	 * Return complete code for a shunt method
	 *
	 * @return string function declaration and internal code
	 */
	public function getDeclarationCode()
	{
		$sParamDeclarations = $this->getParameterDeclarations(true);
		$sMethodSignature = $this->getMethodSignature();
		$sMethodCode = $this->getMethodCode();

		$sMethodDeclaration = "{$sMethodSignature}({$sParamDeclarations}){$sMethodCode}";
		return $sMethodDeclaration;
	}

	/**
	 * Return shunt method code for calling protected methods
	 *
	 * @return string
	 */
	public function getMethodCode()
	{
		$sMethodName = $this->getNameBeingShunted();

		$sArgs = $this->getParameterDeclarations(true, true);
			
		if ($this->isAbstract()) {
			$sCode = "{ throw new ShuntException('Cannot call an abstract method.', ShuntException::AbstractMethodCall); }";
		} else {
			$sCode = "{ return parent::{$sMethodName}({$sArgs}); }";
		}
		return $sCode;
	}

	/**
	 * Return method signature for calling shunted method
	 * Methods declared 'protected' are returned as 'public'
	 *
	 * @return string
	 */
	public function getMethodSignature()
	{
		$sClassName = $this->getDeclaringClass()->getName();
		$sMethodName = $this->getNameBeingShunted();
		
		if ($this->isAbstract()) {
			$sShuntName = $sMethodName;
		} else {
			$sShuntName = $this->getName();
		}

		$aModifiers = array();
		$aModifiers[] = 'public';
		if ($this->isStatic()) {
			$aModifiers[] = 'static';
		}
		$sModifiers = implode(' ', $aModifiers);

		$sMethodSignature = sprintf("%s function %s", $sModifiers, $sShuntName);
		return $sMethodSignature;
	}

	/**
	 * Return the shunt method name for the set method
	 *
	 * @return string name of method as a shunt method
	 */
	public function getName()
	{
		$sMethodName = $this->getNameBeingShunted();
		$sShuntMethodName = self::MethodPrefix . $sMethodName . self::MethodSuffix;

		return $sShuntMethodName;
	}

	/**
	 * Return the shunt method name for the set method
	 *
	 * @return string name of method being shunted
	 */
	public function getNameBeingShunted()
	{
		return parent::getName();
	}

	/**
	 * Returns an array of method parameter declaration strings
	 *
	 * @param boolean $bAsString    required=false if true, return as a comma-separated string instead of an array
	 * @param boolean $bAsArgs      required=false if true, return as method arguments instead of parameters
	 * @return mixed an array of strings from ShuntParameter::getDeclarationCode where name => declaration code, or a comma-separated string
	 */
	public function getParameterDeclarations($bAsString=false, $bAsArgs=false)
	{
		$sClassName = $this->getDeclaringClass()->getName();
		$sMethodName = $this->getName();
		$aParamDeclarations = array();

		foreach ($this->getParameters() as $i => $oParam) {
			$sParamName = $oParam->getName();
			$sCode = "\${$sParamName}";
			
			if (!$bAsArgs) {
				if ($oParam->isPassedByReference()) {
					$sCode = "&{$sCode}";
				}
				if ($oParam->isOptional()) {
					$sCode .= '=' . var_export($oParam->getDefaultValue(), true);
				}
			}

			$aParamDeclarations[$sParamName] = $sCode;
		}

		if ($bAsString) {
			$sParamDeclarations = implode(', ', $aParamDeclarations);
			return $sParamDeclarations;
		} else {
			return $aParamDeclarations;
		}
	}

	/**
	 * Is the current method declared in a parent class?
	 *
	 * @return bool true if this method was declared in a parent class
	 */
	public function isInherited()
	{
		$sDeclaringClass = $this->getDeclaringClass()->getName();
		$sClassName = $this->class;
		return ($sDeclaringClass != $sClassName);
	}

	/**
	 * Should the method be shunted?
	 *
	 * @return bool true if this method should (can) be shunted
	 */
	public function isShuntable()
	{
		return (
			($this->isProtected() && !$this->isInherited())
			|| ($this->isAbstract())
		);
	}
}
?>
