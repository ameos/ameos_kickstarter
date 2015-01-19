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

class CreateExtension {

	protected $extensionKey;
	protected $extensionPath;

	/**
	 * @constructor
	 *
	 * @param array $argv arguments
	 */
	public function __construct($argv) {
		$this->extensionKey = isset($argv[2]) ? $argv[2] : FALSE;

		if($this->extensionKey === FALSE) {
			Help::displayHelp();
		}

		$this->extensionPath = EXTENSIONS_PATH . $this->extensionKey . '/';
		if(file_exists($this->extensionPath)) {
			echo 'Extension ' . $this->extensionKey . ' already exist' . LF . LF;
			Help::displayHelp();
		}
	}

	/**
	 * execute
	 */
	public function execute() {
		$title = $description = $category = $author = $authorEmail = $authorCompany = '';
		
		$fileResource = fopen('php://stdin', 'r');
		
		$stop = FALSE;	
		do {
			echo 'Extension title : ';
			$title = fgets($fileResource, 1024);
			$title = trim($title);
			
			if(!empty($title)) {
				$stop = TRUE;					
			}
		} while(!$stop);
		
		$stop = FALSE;
		do {
			echo 'Extension description : ';
			$description = fgets($fileResource, 1024);
			$description = trim($description);
			
			if(!empty($description)) {
				$stop = TRUE;					
			} 
		} while(!$stop);

		$stop = FALSE;
		do {
			echo 'Extension category (be,misc,fe) : ';
			$category = fgets($fileResource, 1024);
			$category = trim($category);
			
			if(!empty($category)) {
				$stop = TRUE;					
			} 
		} while(!$stop);

		$stop = FALSE;
		do {
			echo 'Author : ';
			$author = fgets($fileResource, 1024);
			$author = trim($author);
			
			if(!empty($author)) {
				$stop = TRUE;					
			}
		} while(!$stop);

		$stop = FALSE;
		do {
			echo 'Author email : ';
			$authorEmail = fgets($fileResource, 1024);
			$authorEmail = trim($authorEmail);
			
			if(!empty($authorEmail)) {
				$stop = TRUE;					
			}
		} while(!$stop);

		$stop = FALSE;
		do {
			echo 'Author company : ';
			$authorCompany = fgets($fileResource, 1024);
			$authorCompany = trim($authorCompany);
			
			if(!empty($authorCompany)) {
				$stop = TRUE;					
			}
		} while(!$stop);
	
		fclose($fileResource);

		mkdir($this->extensionPath, 0770, TRUE);

		$emconfFilecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/RootFileTemplate/ext_emconf.php');
		$emconfFilecontent = str_replace(
			array('{title}', '{description}', '{category}', '{author}', '{author_email}', '{author_company}'),
			array($title, $description, $category, $author, $authorEmail, $authorCompany),
			$emconfFilecontent
		);
		file_put_contents($this->extensionPath . 'ext_emconf.php', $emconfFilecontent);

		copy(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/RootFileTemplate/ext_icon.gif',      $this->extensionPath . 'ext_icon.gif');
		copy(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/RootFileTemplate/ext_localconf.php', $this->extensionPath . 'ext_localconf.php');
		copy(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/RootFileTemplate/ext_tables.php',    $this->extensionPath . 'ext_tables.php');

		echo 'Extension create.' . LF;
	}	 
}
