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
		$sClassName = $this->getNameBeingShunted();
		$sShuntClassName = $this->getName();

		$aMethodCodes = array();

		foreach ($this->getMethods() as $oMethod) {
			$oMethod = new ShuntMethod($sClassName, $oMethod->getName());

			if ($oMethod->isShuntable()) {
				$aMethodCodes[] = $oMethod->getDeclarationCode();
			}
		}
		$sMethods = implode('', $aMethodCodes);

		$sClassDefinition = <<<EOT
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
	 */
	public function getName()
	{
		$sClassName = $this->getNameBeingShunted();
		$sShuntClassName = self::ClassPrefix . $sClassName . self::ClassSuffix;

		return $sShuntClassName;
	}

	/**
	 * Return the shunt class name for the set class
	 */
	public function getNameBeingShunted()
	{
		return parent::getName();
	}
}
?>
