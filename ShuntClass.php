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
 * Generates a shunt of a specific class
 */
class ShuntClass extends ReflectionClass
{
	const ClassPrefix = '';
	const ClassSuffix = 'Shunt';

	/**
	 * Return complete code for a shunt class
	 *
	 * @return string function declaration and internal code
	 */
	public function getDeclarationCode()
	{
		$sOriginalClassName = $this->getNameBeingShunted();
		$sClassNamespace    = $this->getNamespace();
		$sShuntClassName    = $this->getName($sClassNamespace);
		if ($sClassNamespace) {
			// Strip off the namespace
			$sClassName = substr($sOriginalClassName, strlen($sClassNamespace)+1);
		} else {
			$sClassName = $sOriginalClassName;
		}

		$aMethodCodes = array();

		foreach ($this->getMethods() as $oMethod) {
			$oMethod = new ShuntMethod($sOriginalClassName, $oMethod->getName());

			if ($oMethod->isShuntable()) {
				$aMethodCodes[] = $oMethod->getDeclarationCode();
			}
		}
		$sMethods = implode('', $aMethodCodes);

		if ($sClassNamespace) {
			$sNamespaceDefinition = "namespace {$sClassNamespace};";
		} else {
			$sNamespaceDefinition = '';
		}
		$sClassDefinition = <<<EOT
{$sNamespaceDefinition}
class {$sShuntClassName} extends {$sClassName}
{
	static public function _shuntGetStatic(\$sPropertyName)
	{
		return self::\${\$sPropertyName};
	}

	static public function _shuntSetStatic(\$sPropertyName, \$sPropertyValue=null)
	{
		self::\${\$sPropertyName} = \$sPropertyValue;
		return self::_shuntGetStatic(\$sPropertyName);
	}

	public function _shuntGet(\$sPropertyName)
	{
		return \$this->\$sPropertyName;
	}

	public function _shuntSet(\$sPropertyName, \$sPropertyValue=null)
	{
		\$this->\$sPropertyName = \$sPropertyValue;
		return \$this->_shuntGet(\$sPropertyName);
	}

	{$sMethods}
}
EOT;

		return $sClassDefinition;
	}

	/**
	 * Return the shunt class name for the set class
	 * @param string $sNamespace The namespace of the shunted class
	 */
	public function getName($sNamespace=false)
	{
		$sClassName = $this->getNameBeingShunted();
		if ($sNamespace) {
			// Strip off the namespace
			$sClassName = substr($sClassName, strlen($sNamespace)+1);
		}
		$sShuntClassName = self::ClassPrefix . $sClassName . self::ClassSuffix;

		return $sShuntClassName;
	}

	/**
	 * Return the namespace for the shunt class, or False if none
	 */
	public function getNamespace() {
		$sClassName = $this->getNameBeingShunted();
		$sWithoutNamespace = strrchr($sClassName, '\\');
		if ($sWithoutNamespace === false) {
			return false;
		} else {
			// Strip off class name
			return substr($sClassName, 0, -strlen($sWithoutNamespace));
		}
	}

	/**
	 * Return the shunt class name for the set class
	 */
	public function getNameBeingShunted()
	{
		return parent::getName();
	}
}
