<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

$TCA['{SQLTABLENAME}'] = array(
    'ctrl' => array(
        'title'         => 'LLL:EXT:{EXTENSION}/Resources/Private/Language/locallang_db.xlf:{SQLTABLENAME}',
        'label'         => 'uid', 
        'tstamp'        => 'tstamp',
        'crdate'        => 'crdate',
        'cruser_id'     => 'cruser_id',
        'delete'        => 'deleted',
        'enablecolumns' => array(
			'disabled'  => 'hidden',{ENABLECOLUMNS}
        ),
        'default_sortby'    => 'ORDER BY crdate',{SORTING}
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Configuration/Tca/{MODEL}.php',
        'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'ext_icon.gif',
        'searchFields'      => '{LISTFIELDS}',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => '{LISTFIELDS}',
    )
);
