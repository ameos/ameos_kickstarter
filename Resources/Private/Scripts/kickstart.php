#!/usr/bin/php
<?php

$scriptPath = realpath($_SERVER['PWD'] . '/' . $_SERVER['PHP_SELF']);
$extensionsPath = '/' . trim(str_replace('ameos_kickstarter/Resources/Private/Scripts', '', dirname($scriptPath)) , '/') . '/';

define("EXTENSIONS_PATH", $extensionsPath);
define("LF",              "\n");
define("TAB",             "  ");
define("PHPTAB",          "\t");

require_once($extensionsPath . 'ameos_kickstarter/Classes/Help.php');
require_once($extensionsPath . 'ameos_kickstarter/Classes/CreateModel.php');
require_once($extensionsPath . 'ameos_kickstarter/Classes/CreateRecord.php');
require_once($extensionsPath . 'ameos_kickstarter/Classes/CreateController.php');
require_once($extensionsPath . 'ameos_kickstarter/Classes/CreateExtension.php');
require_once($extensionsPath . 'ameos_kickstarter/Classes/Utility.php');

use Ameos\AmeosKickstart\Help;
use Ameos\AmeosKickstart\CreateModel;
use Ameos\AmeosKickstart\CreateRecord;
use Ameos\AmeosKickstart\CreateController;
use Ameos\AmeosKickstart\TestPierre;
use Ameos\AmeosKickstart\CreateExtension;
use Ameos\AmeosKickstart\Utility;

if($argc <= 1) {
	Help::displayHelp();
}

$action = $argv[1];

switch($action) {	
	case '-createmodel':
		$createModel = new CreateModel($argv);
		$createModel->execute();
		break;

	case '-createrecord':
		$createRecord = new CreateRecord($argv);
		$createRecord->execute();
		break;
		
	case '-createcontroller':
		$createCreateController = new CreateController($argv);
		$createCreateController->execute();
		break;

	case '-createextension':
		$createExtension = new CreateExtension($argv);
		$createExtension->execute();
		break;
	
	case '-help':
		Help::displayHelp();
		break;
		
	default:
		Help::displayHelp();
		break;
}
