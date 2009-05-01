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
 * Generates classes that provide access to protected properties and methods
 */
class Shunt
{
	/**
	 * Generates a shunt class for testing
	 *
	 * @param string $sClassName    required=true class to provide access to
	 * @return string name of shunt class that was created
	 * @throws Exception if the given class does not exist
	 */
	public static function generate($sClassName)
	{
		if (!class_exists($sClassName)) {
			throw new ShuntException("Class [{$sClassName}] can not be shunted; it does not exist.", ShuntException::ClassNotFound);
		}

		$oShuntClass = new ShuntClass($sClassName);
		$sShuntClassName = $oShuntClass->getName();

		if (!class_exists($sShuntClassName)) {
			$sClassDefinition = $oShuntClass->getDeclarationCode();
			eval($sClassDefinition);
		}

		return $sShuntClassName;
	}

	/**
	 * Generate the shunt class and return an instantiation of it
	 *
	 * @param string $sClassName          required=true class to provide access to
	 * @param array  $aConstructorArgs    required=false
	 * @return object instantiated from the args passes
	 * @throws Exception if the given class does not exist
	 */
	public static function get($sClassName, $aConstructorArgs=array())
	{
		$sShuntName = self::generate($sClassName);
		$oClass = new ReflectionClass($sShuntName);

		$oConstructor = $oClass->getConstructor();
		if ($oConstructor) {
			return $oClass->newInstanceArgs($aConstructorArgs);
		} else {
			return $oClass->newInstance();
		}
	}
}
?>