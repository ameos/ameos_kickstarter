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

class CreateModel {

	protected $extensionKey;
	protected $model;
	protected $vendor;

	/**
	 * @constructor
	 *
	 * @param array $argv arguments
	 */
	public function __construct($argv) {
		$this->vendor       = isset($argv[2]) ? $argv[2] : FALSE;
		$this->extensionKey = isset($argv[3]) ? $argv[3] : FALSE;
		$this->model        = isset($argv[4]) ? $argv[4] : FALSE;

		if($this->extensionKey === FALSE || $this->model === FALSE || $this->vendor === FALSE) {
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
		$modelDirectoryPath      = EXTENSIONS_PATH . $this->extensionKey . '/Classes/Domain/Model/';
		$repositoryDirectoryPath = EXTENSIONS_PATH . $this->extensionKey . '/Classes/Domain/Repository/';
		if(!file_exists($modelDirectoryPath)) { mkdir($modelDirectoryPath, 0770, TRUE); }
		if(!file_exists($repositoryDirectoryPath)) { mkdir($repositoryDirectoryPath, 0770, TRUE); }

		$modelFilepath      = $modelDirectoryPath . $this->model . '.php';
		$repositoryFilepath = $repositoryDirectoryPath . $this->model . 'Repository.php';

		
		$fileResource = fopen('php://stdin', 'r');
		$stop   = FALSE;
		$index  = 1;
		$fields = array();
		
		do {
			echo 'Name of the field ' . $index . ' (empty for stop) : ';
			$fieldname = fgets($fileResource, 1024);
			$fieldname = trim($fieldname);
			
			if(empty($fieldname)) {
				$stop = TRUE;					
			} else {
				$fieldtype = '';
				do  {
					echo 'Type of the ' . $index . ' : ';
					$fieldtype = fgets($fileResource, 1024);
					$fieldtype = trim($fieldtype);

				} while(trim($fieldtype) == '');				
			}

			if(!$stop) {
				$fields[$fieldname] = $fieldtype;
				$index++;
			}

		} while(!$stop);
		
		fclose($fileResource);		

		$repositoryFilecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ClassTemplate/Repository.php');
		$repositoryFilecontent = str_replace(
			array('{VENDOR}', '{EXTENSION}', '{CLASSNAME}'),
			array($this->vendor, Utility::camelCase($this->extensionKey), $this->model . 'Repository'),
			$repositoryFilecontent
		);
		file_put_contents($repositoryFilepath, $repositoryFilecontent);
		echo 'Repository create.' . LF;

		$modelPhpcode = '';
		foreach($fields as $fieldname => $filetype) {
			$modelPhpcode.= PHPTAB . '/**' . LF;
			$modelPhpcode.= PHPTAB . ' * @var ' . $filetype . LF;
			$modelPhpcode.= PHPTAB . ' */' . LF;
			$modelPhpcode.= PHPTAB . 'protected $' . $fieldname . ';' . LF . LF;			
		}

		foreach($fields as $fieldname => $filetype) {
			$modelPhpcode.= PHPTAB . '/**' . LF;
			$modelPhpcode.= PHPTAB . ' * return ' . $fieldname . ' value' . LF;
			$modelPhpcode.= PHPTAB . ' * ' . LF;
			$modelPhpcode.= PHPTAB . ' * @return ' . $filetype . ' the value' . LF;
			$modelPhpcode.= PHPTAB . ' */' . LF;
			$modelPhpcode.= PHPTAB . 'public function get' . ucfirst($fieldname) . '() {' . LF;
			$modelPhpcode.= PHPTAB . PHPTAB . 'return $this->' . $fieldname . ';' . LF;
			$modelPhpcode.= PHPTAB . '}' . LF . LF;

			$modelPhpcode.= PHPTAB . '/**' . LF;
			$modelPhpcode.= PHPTAB . ' * set ' . $fieldname . ' value' . LF;
			$modelPhpcode.= PHPTAB . ' * ' . LF;
			$modelPhpcode.= PHPTAB . ' * @params ' . $filetype . ' $' . $fieldname . ' the value' . LF;
			$modelPhpcode.= PHPTAB . ' * @return \\' . $this->vendor . '\\' . Utility::camelCase($this->extensionKey) . '\\Domain\\Model\\' . $this->model . ' the current instance' . LF;
			$modelPhpcode.= PHPTAB . ' */' . LF;
			$modelPhpcode.= PHPTAB . 'public function set' . ucfirst($fieldname) . '($' . $fieldname . ') {' . LF;
			$modelPhpcode.= PHPTAB . PHPTAB . '$this->' . $fieldname . ' = $' . $fieldname . ';' . LF;
			$modelPhpcode.= PHPTAB . PHPTAB . 'return $this;' . LF;
			$modelPhpcode.= PHPTAB . '}' . LF . LF;
		}

		$modelFilecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ClassTemplate/Model.php');
		$modelFilecontent = str_replace(
			array('{VENDOR}', '{EXTENSION}', '{CLASSNAME}', '{PHPCODE}'),
			array($this->vendor, Utility::camelCase($this->extensionKey), $this->model, $modelPhpcode),
			$modelFilecontent
		);
		file_put_contents($modelFilepath, $modelFilecontent);
		echo 'Model create.' . LF;		
		die();
	}	 
}
