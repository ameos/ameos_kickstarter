<?php

namespace Ameos\AmeosKickstart;

class Help {

	/**
	 * help
	 */
	public static function displayHelp() {
		echo LF . LF . 'Usage: kickstart <action> <params>' . LF .
			'where possible action include:' . LF .
			TAB . '-createmodel <vendor> <extensionkey> <model> Create model for extension' . LF .
			TAB . '-createfullmodel <vendor> <extensionkey> <model> Create model and tca and update ext_tables files for extension' . LF .
			TAB . '-createcontroller <vendor> <extensionkey> <controller> Create controller for extension' . LF . 
			TAB . '-createextension <extensionkey> Create new extension' . LF . 
			TAB . '-help Display help' . LF . LF;
		die();
	}
}
