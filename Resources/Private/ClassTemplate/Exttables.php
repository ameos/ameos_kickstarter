$TCA['{SQLTABLENAME}'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:{EXTENSION}/Resources/Private/Language/locallang_db.xml:{SQLTABLENAME}',
        'label' => 'uid', 
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete'            => 'deleted',
        'enablecolumns'     => array (
            'disabled' => 'hidden'
        ),
        'default_sortby' => 'ORDER BY crdate',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'Configuration/Tca/{MODEL}.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'ext_icon.gif',
        'searchFields' => '',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => '{LISTFIELDS}',
    )
);
