#!/usr/bin/php
<?php

$extensionsPath = str_replace('ameos_kickstarter/Resources/Private/Scripts', '', $_SERVER['PWD']);
define("EXTENSIONS_PATH", $extensionsPath);
define("LF",              "\n");
define("TAB",             "  ");
define("PHPTAB",          "\t");

require_once($extensionsPath . 'ameos_kickstarter/Classes/Help.php');
require_once($extensionsPath . 'ameos_kickstarter/Classes/CreateModel.php');
require_once($extensionsPath . 'ameos_kickstarter/Classes/CreateFullModel.php');
require_once($extensionsPath . 'ameos_kickstarter/Classes/CreateController.php');
require_once($extensionsPath . 'ameos_kickstarter/Classes/CreateExtension.php');
require_once($extensionsPath . 'ameos_kickstarter/Classes/Utility.php');

use Ameos\AmeosKickstart\Help;
use Ameos\AmeosKickstart\CreateModel;
use Ameos\AmeosKickstart\CreateFullModel;
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

	case '-createfullmodel':
		$createFullModel = new CreateFullModel($argv);
		$createFullModel->execute();
		break;
		
	case '-createcontroller':
		$createModel = new CreateController($argv);
		$createModel->execute();
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
