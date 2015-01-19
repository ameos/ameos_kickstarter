<?php
namespace Ameos\AmeosKickstart;

/***************************************************************
* Copyright notice
*
* (c) 2004-2015
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
* A copy is found in the textfile GPL.txt and important notices to the license
* from the author is found in LICENSE.txt distributed with these scripts.
*
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use Ameos\AmeosKickstart\Help;
use Ameos\AmeosKickstart\Utility;

class CreateController {

	protected $extensionKey;
	protected $controller;
	protected $vendor;

	/**
	 * @constructor
	 *
	 * @param array $argv arguments
	 */
	public function __construct($argv) {
		$this->vendor       = isset($argv[2]) ? $argv[2] : FALSE;
		$this->extensionKey = isset($argv[3]) ? $argv[3] : FALSE;
		$this->controller   = isset($argv[4]) ? $argv[4] : FALSE;

		if($this->extensionKey === FALSE || $this->controller === FALSE || $this->vendor === FALSE) {
			Help::displayHelp();
		}

		if(!file_exists(EXTENSIONS_PATH . $this->extensionKey)) {
			echo 'Extension ' . $this->extensionKey . ' doesn\'t exist' . LF . LF;
			Help::displayHelp();
		}
	}

	/**
	 * execute
	 */
	public function execute() {
		$controllerDirectoryPath      = EXTENSIONS_PATH . $this->extensionKey . '/Classes/Controller/';
		$tempateDirectoryPath         = EXTENSIONS_PATH . $this->extensionKey . '/Resources/Private/Templates/' . $this->controller . '/';
		if(!file_exists($controllerDirectoryPath)) { mkdir($controllerDirectoryPath, 0770, TRUE); }
		if(!file_exists($tempateDirectoryPath)) { mkdir($tempateDirectoryPath, 0770, TRUE); }

		$controllerFilepath = $controllerDirectoryPath . $this->controller . 'Controller.php';
		
		$fileResource = fopen('php://stdin', 'r');
		$stop    = FALSE;
		$index   = 1;
		$actions = array();

		do {
			echo 'Name of the action ' . $index . ' (empty for stop) : ';
			$actionname = fgets($fileResource, 1024);
			$actionname = trim($actionname);
			
			if(empty($actionname)) {
				$stop = TRUE;					
			} 

			if(!$stop) {
				$actions[] = $actionname;
				$index++;
			}

		} while(!$stop);
		
		$stop   = FALSE;
		$index  = 1;
		$dependencies = array();

		do {
			echo 'Dependency ' . $index . ' (empty for stop) : ';
			$dependency = fgets($fileResource, 1024);
			$dependency = trim($dependency);
			
			if(empty($dependency)) {
				$stop = TRUE;					
			} 

			if(!$stop) {
				$varDependency = lcfirst(array_pop(explode('\\', $dependency)));				
				$dependencies[$varDependency] = $dependency;
				$index++;
			}

		} while(!$stop);

		fclose($fileResource);

		$controllerPhpcode = '';

		foreach($dependencies as $varDependency => $dependency) {
			$controllerPhpcode.= PHPTAB . '/**' . LF;
			$controllerPhpcode.= PHPTAB . ' * @var ' . $dependency . LF;
			$controllerPhpcode.= PHPTAB . ' */' . LF;
			$controllerPhpcode.= PHPTAB . 'protected $' . $varDependency . ';' . LF . LF;			
		}

		foreach($dependencies as $varDependency => $dependency) {
			$controllerPhpcode.= PHPTAB . '/**' . LF;
			$controllerPhpcode.= PHPTAB . ' * Dependency injection of the ' . $dependency . LF;
			$controllerPhpcode.= PHPTAB . ' * ' . LF;
			$controllerPhpcode.= PHPTAB . ' * @param ' . $dependency . ' $' . $varDependency . LF;
			$controllerPhpcode.= PHPTAB . ' * @return void' . LF;
			$controllerPhpcode.= PHPTAB . ' */' . LF;
			$controllerPhpcode.= PHPTAB . 'public function inject' . ucfirst($varDependency) . '(' . $dependency . ' $' . $varDependency . ') {' . LF;
			$controllerPhpcode.= PHPTAB . PHPTAB . '$this->' . $varDependency . ' = $' . $varDependency . ';' . LF;
			$controllerPhpcode.= PHPTAB . '}' . LF . LF;
		}

		foreach($actions as $action) {
			$controllerPhpcode.= PHPTAB . '/**' . LF;
			$controllerPhpcode.= PHPTAB . ' * Action ' . $action . LF;
			$controllerPhpcode.= PHPTAB . ' */' . LF;
			$controllerPhpcode.= PHPTAB . 'public function ' . $action . 'Action() {' . LF;
			$controllerPhpcode.= PHPTAB . PHPTAB . '// code here' . LF;
			$controllerPhpcode.= PHPTAB . '}' . LF . LF;
		}

		$controllerFilecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ClassTemplate/Controller.php');
		$controllerFilecontent = str_replace(
			array('{VENDOR}', '{EXTENSION}', '{CLASSNAME}', '{PHPCODE}'),
			array($this->vendor, Utility::camelCase($this->extensionKey), $this->controller, $controllerPhpcode),
			$controllerFilecontent
		);
		file_put_contents($controllerFilepath, $controllerFilecontent);
		echo 'Controller create.' . LF;

		foreach($actions as $action) {
			$actionTempateFilepath = $tempateDirectoryPath  . ucfirst($action) . '.html';
			file_put_contents($actionTempateFilepath, '');

			echo 'Template for action ' . $action . ' create.' . LF;
		}		
	}	 
}
