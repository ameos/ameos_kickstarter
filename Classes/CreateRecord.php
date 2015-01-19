<?php

namespace Ameos\AmeosKickstart;

use Ameos\AmeosKickstart\Help;
use Ameos\AmeosKickstart\Utility;

class CreateRecord {

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
		$this->errors 		= '';
		$this->warning 		= '';
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
					echo 'Type of field ' . $fieldname . ' (string / int / foreignkey) : ';
					$fieldtype = fgets($fileResource, 1024);
					$fieldtype = trim($fieldtype);

				} while(trim($fieldtype) == '');

				$fieldlabel = '';
				do  {
					echo 'label of field ' . $fieldname . ' : ';
					$fieldlabel = fgets($fileResource, 1024);
					$fieldlabel = trim($fieldlabel);

				} while(trim($fieldlabel) == '');
				$fields[$fieldname]['label'] = $fieldlabel;
			}

			if(!$stop) {				
				$fields[$fieldname]['type'] = $fieldtype;
				switch ($fieldtype) {
					case 'string':
						echo 'Tca type of the "' . $fieldname . '" field (input / text / rte / select / radio) '. LF;
						echo 'If select or radio, only base will be set. : ';
						$tcaType = fgets($fileResource, 1024);
						$fields[$fieldname]['tca'] = trim($tcaType);
						break;
						
					case 'int':
						echo 'Tca type of the "' . $fieldname . '" field (input / select / radio / date / datetime / boolean) '. LF;
						echo 'If select or radio, only base will be set. : ';
						$tcaType = fgets($fileResource, 1024);
						$fields[$fieldname]['tca'] = trim($tcaType);
						break;
						
					case 'foreignkey':
						echo 'Tca type of the "' . $fieldname . '" field (select / group) : ';
						$tcaType = fgets($fileResource, 1024);
						$fields[$fieldname]['tca'] = trim($tcaType);

						echo 'Is multiple relation (yes/no) : ';
						$isMultiple = fgets($fileResource, 1024);
						$fields[$fieldname]['is_multiple'] = (strtolower(trim($isMultiple)) == 'yes' ? TRUE : FALSE);

						echo 'Foreign table (e.g. tx_myextension_domain_model_record): ';
						$foreignTable = fgets($fileResource, 1024);
						$fields[$fieldname]['foreign_table'] = trim($foreignTable);

						echo 'Associated model (e.g. \Vendor\MyExtension\Domain\Model\Record ) : ';
						$associatedModel = fgets($fileResource, 1024);
						$fields[$fieldname]['associated_model'] = trim($associatedModel);
						break;
						
					default:
						break;
				}
				
				$index++;
			}

		} while(!$stop);

		
		echo 'Use starttime and endtime (yes/no) : ';
		$useStartEndtime = fgets($fileResource, 1024);
		$useStartEndtime = (strtolower(trim($useStartEndtime)) == 'yes' ? TRUE : FALSE);

		echo 'Use fe_group access (yes/no) : ';
		$useFegroup = fgets($fileResource, 1024);
		$useFegroup = (strtolower(trim($useFegroup)) == 'yes' ? TRUE : FALSE);

		echo 'Use manual sorting (yes/no) : ';
		$useSorting = fgets($fileResource, 1024);
		$useSorting = (strtolower(trim($useSorting)) == 'yes' ? TRUE : FALSE);
		
		fclose($fileResource);		

		$this->createRepository();
		$this->createModel($fields, $useStartEndtime, $useFegroup, $useSorting);
		$this->createTca($fields, $useStartEndtime, $useFegroup, $useSorting);
		
		echo LF .'Record created.' . LF;

		if($this->errors != '') {
			echo LF .'--------------------' . LF . LF;
			echo 'Some errors occured : ' . LF . LF;
			echo $this->errors;
			echo LF . LF .'--------------------' . LF . LF;
		}
		if($this->warning != '') {
			echo LF .'--------------------' . LF . LF;
			echo 'You should verify these info : ' . LF . LF;
			echo $this->warning;
			echo LF . LF .'--------------------' . LF . LF;
		}

		die();
	}

	/**
	 * create tca configuration
	 * @param array $fields fields informations
	 * @param bool $useStartEndtime use start and endtime field
	 * @param bool $useFegroup use fe group access
	 * @param bool $useSorting use manuel sorting
	 */
	protected function createTca($fields, $useStartEndtime = FALSE, $useFegroup = FALSE, $useSorting = FALSE) {
		$extensionPath = EXTENSIONS_PATH . $this->extensionKey . '/';
		
		$tcaDirectoryPath = $extensionPath . 'Configuration/Tca/';
		if(!file_exists($tcaDirectoryPath)) { mkdir($tcaDirectoryPath, 0770, TRUE); }
		
		$tcaFilepath = $tcaDirectoryPath . Utility::camelCase($this->model) . '.php';
		$sqlFilepath = $extensionPath . 'ext_tables.sql';
		$extTablesFilepath = $extensionPath . 'ext_tables.php';
		$locallangFilepath = $extensionPath . 'Resources/protected/Language/locallang_db.xlf';
		
		$sqlTableName = 'tx_' . str_replace('_', '', $this->extensionKey) . '_domain_model_' . strtolower($this->model);
		
		$tcaPhpCode = '';
		$sqlFields = '';

		$tcaPhpCode .= '$TCA[\'' . $sqlTableName . '\'] = array(' . LF;
		$tcaPhpCode .= PHPTAB . '\'ctrl\' => $TCA[\''.$sqlTableName.'\'][\'ctrl\'],' . LF;
    	$tcaPhpCode .= PHPTAB . '\'interface\' => array(' . LF;
        $tcaPhpCode .= PHPTAB . PHPTAB . '\'showRecordFieldList\' => \'\'' . LF;
    	$tcaPhpCode .= PHPTAB . '),' . LF;
    	$tcaPhpCode .= PHPTAB . '\'feInterface\' => $TCA[\''.$sqlTableName.'\'][\'feInterface\'],' . LF;
    	$tcaPhpCode .= PHPTAB . '\'columns\' => array(' . LF;

		if($useStartEndtime) {
			$fields['starttime'] = array('type' => 'int', 'tca' => 'datetime', 'label' => 'Start time');
			$fields['endtime']   = array('type' => 'int', 'tca' => 'datetime', 'label' => 'End time');
		}

		if($useFegroup) {
			$fields['fe_group'] = array(
				'type'          => 'foreignkey',
				'tca'           => 'select',
				'foreign_table' => 'fe_groups',
				'is_multiple'   => TRUE,
				'label'         => 'Access frontend group',
			);
		}
		
		$fieldList = array();
		$labels = '';
		foreach ($fields as $fieldname => $fieldinfo) {
			// On met à jour la liste des items
			$fieldList[] = $fieldname;

			$tcaPhpCode .= PHPTAB . PHPTAB . '\''.$fieldname.'\' => array(' . LF;
			$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . '\'exclude\' => 1,' . LF;
			$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . '\'label\' =>  \'LLL:EXT:' . $this->extensionKey . '/Resources/protected/Language/locallang_db.xlf:' . $sqlTableName . '.' . $fieldname . '\',' . LF;
			$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . '\'config\' => array(' . LF;
			switch ($fieldinfo['type']) {
				case 'string':
					switch ($fieldinfo['tca']) {
						case 'input':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'input\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'eval\' => \'trim\',' . LF;
							$sqlFields  .= PHPTAB .$fieldname . ' varchar(255) DEFAULT \'\' NOT NULL,' . LF;
							break;
						case 'text':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'text\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'cols\' => \'40\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'rows\' => \'15\',' . LF;
							$sqlFields  .= PHPTAB .$fieldname . ' text,' . LF;
							break;
						case 'rte':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'input\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'wizards\' => array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'RTE\' => array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'notNewRecords\' => 1,' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'RTEonly\' => 1,' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'script\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'title\' => \'LLL:EXT:cms/locallang_ttc.xlf:bodytext.W.RTE\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'icon\' => \'wizard_rte2.gif\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'module\' => array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'name\' => \'wizard_rte\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . ')' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '),' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . ')' . LF;
							$sqlFields  .= PHPTAB .$fieldname . ' text,' . LF;
							break;
						case 'select':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'select\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'maxitems\' => 1,' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'size\' => 1,' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '/* List handle by hand' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'items\' => array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . 'array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item1\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item1\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '),' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . 'array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item2\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item2\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . ')' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '),' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '*/' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '/* List handled by foreign table' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'foreign_table\' => \'fe_users\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'foreign_table_where\' => \'ORDER BY fe_users.username\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '*/' . LF;
							$sqlFields  .= PHPTAB .$fieldname . ' varchar(255) DEFAULT \'\' NOT NULL,' . LF;
							break;
						case 'radio':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'radio\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'items\' => array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . 'array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item1\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item1\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '),' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . 'array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item2\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item2\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . ')' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . ')' . LF;
							$sqlFields  .= PHPTAB . $fieldname . ' varchar(255) DEFAULT \'\' NOT NULL,' . LF;
							break;
						
						default:
							$sqlFields  .= PHPTAB .$fieldname . ' varchar(255) DEFAULT \'\' NOT NULL,' . LF;
							$this->warning .= 'TCA type "'.$fieldinfo['tca'].'" not found for field "'.$fieldname.'", please check manualy TCA file and ext_tables.sql' . LF;
							break;
					}
					break;
				case 'int':
					switch ($fieldinfo['tca']) {
						case 'input':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'input\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'eval\' => \'trim,int\',' . LF;
							$sqlFields  .= PHPTAB . $fieldname . ' int(11) DEFAULT \'0\' NOT NULL,' . LF;
							break;
						case 'select':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'select\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'maxitems\' => 1,' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'size\' => 1,' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '/* List handle by hand' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'items\' => array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . 'array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item1\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item1\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '),' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . 'array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item2\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item2\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . ')' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '),' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '*/' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '/* List handled by foreign table' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'foreign_table\' => \'fe_users\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'foreign_table_where\' => \'ORDER BY fe_users.username\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '*/' . LF;
							$sqlFields  .= PHPTAB . $fieldname . ' int(11) DEFAULT \'0\' NOT NULL,' . LF;
							break;
						case 'radio':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'radio\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'items\' => array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . 'array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item1\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'1\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '),' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . 'array(' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'Item2\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'2\'' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . PHPTAB . ')' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . ')' . LF;
							$sqlFields  .= PHPTAB . $fieldname . ' int(11) DEFAULT \'0\' NOT NULL,' . LF;
							break;
						case 'date':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'input\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'eval\' => \'trim, date\',' . LF;
							$sqlFields  .= PHPTAB . $fieldname . ' int(11) DEFAULT \'0\' NOT NULL,' . LF;
							break;
						case 'datetime':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'input\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'eval\' => \'trim, datetime\',' . LF;
							$sqlFields  .= PHPTAB . $fieldname . ' int(11) DEFAULT \'0\' NOT NULL,' . LF;
							break;
						case 'boolean':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'check\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'default\' => \'0\'' . LF;
							$sqlFields  .= PHPTAB . $fieldname . ' tinyint(4) DEFAULT \'0\' NOT NULL,' . LF;
							break;
						default:
							$sqlFields  .= PHPTAB . $fieldname . ' int(11) DEFAULT \'0\' NOT NULL,' . LF;
							$this->warning .= 'TCA type "'. $fieldinfo['tca'] .'" not found for field "'. $fieldname .'", please check manualy TCA file and ext_tables.sql' . LF;
							break;
					}
					break;
				case 'float':
					$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'input\',' . LF;
					$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'eval\' => \'double2\',' . LF;
					$sqlFields  .= PHPTAB .$fieldname . ' varchar(255) DEFAULT \'\' NOT NULL,' . LF;
					break;
				case 'double':
					$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'input\',' . LF;
					$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'eval\' => \'double2\',' . LF;
					$sqlFields  .= PHPTAB .$fieldname . ' varchar(255) DEFAULT \'\' NOT NULL,' . LF;
					break;
				case 'boolean':
					$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'check\',' . LF;
					$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'default\' => \'0\'' . LF;
					$sqlFields  .= PHPTAB .$fieldname . " tinyint(4) DEFAULT '0' NOT NULL," . LF;
					break;
				case 'foreignkey':
					$maxItems = $fieldinfo['is_multiple'] ? '10' : 1;
					switch($fieldinfo['tca']) {
						case 'select':							
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'select\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'maxitems\' => ' . $maxItems . ',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'size\' => ' . $maxItems . ',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'foreign_table\' => \'' . $fieldinfo['foreign_table'] . '\',' . LF;
							break;

						case 'group':
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'group\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'internal_type\' => \'db\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'allowed\' => \'' . $fieldinfo['foreign_table'] . '\',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'maxitems\' => ' . $maxItems . ',' . LF;
							$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'size\' => ' . $maxItems . ',' . LF;
							break;
					}
					if($fieldinfo['is_multiple']) {
						$sqlFields .= PHPTAB . $fieldname . " text," . LF;
					} else {
						$sqlFields .= PHPTAB . $fieldname . " int(11) DEFAULT '0' NOT NULL," . LF;
					}
					break;
			}
			$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . ')' . LF;
			$tcaPhpCode .= PHPTAB . PHPTAB . '),' . LF;

			if($labels !== '') {
				$labels.= PHPTAB . PHPTAB;
			}
			$labels.= PHPTAB . '<trans-unit id="' . $sqlTableName . '.' . $fieldname . '" xml:space="preserve"><source>' . $fieldinfo['label'] . '</source></trans-unit>' . LF;
		}

		$sortingExttable = '';
		if($useSorting) {
			$sqlFields.= PHPTAB . 'sorting int(11) DEFAULT \'0\' NOT NULL,' . LF;
			$sortingExttable = LF . PHPTAB . PHPTAB . '\'sortby\'            => \'sorting\',';
		}

		
		$enableColumnsExttable = '';
		if($useStartEndtime) {
			$enableColumnsExttable.= LF . PHPTAB . PHPTAB . PHPTAB . '\'starttime\' => \'starttime\',';
			$enableColumnsExttable.= LF . PHPTAB . PHPTAB . PHPTAB . '\'endtime\'   => \'endtime\',';
		}

		if($useFegroup) {
			$enableColumnsExttable.= LF . PHPTAB . PHPTAB . PHPTAB . '\'fe_group\'  => \'fe_group\',';
		}

		$tcaPhpCode .= PHPTAB . '),' . LF . LF;
		$tcaPhpCode .= PHPTAB . '\'types\' => array(' . LF;
		$tcaPhpCode .= PHPTAB . PHPTAB . '\'0\' => array(\'showitem\' => \'' . implode(',', $fieldList) . '\')' . LF;
		$tcaPhpCode .= PHPTAB . '),' . LF;
		$tcaPhpCode .= PHPTAB . '\'palettes\' => array(' . LF;
    	$tcaPhpCode .= PHPTAB . PHPTAB . '\'1\' => array(\'showitem\' => \'\')' . LF;
		$tcaPhpCode .= PHPTAB . ')' . LF;
		$tcaPhpCode .= ');' . LF;
        
        // Create TCA file
		$tcaFilecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ConfigurationTemplate/Tca.php');
		$tcaFilecontent = str_replace(
			array('{PHPCODE}'),
			array($tcaPhpCode),
			$tcaFilecontent
		);
		file_put_contents($tcaFilepath, $tcaFilecontent);

		echo 'Tca created.' . LF;

		// Update locallang_db file		
		if(!file_exists($locallangFilepath)) {
			if(!file_exists($locallangFilepath)) { mkdir(dirname($locallangFilepath), 0770, TRUE); }

			file_put_contents($locallangFilepath, str_replace(
				array('{EXTENSION}', '{DATE}'),
				array($this->extensionKey, date('c')),
				file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ConfigurationTemplate/locallang.xlf')
			));
		}

		$currentLocallangueContent = file_get_contents($locallangFilepath);
		$newLocallangueContent = preg_replace('/(.*)<\/body>(.*)/', '$1' . $labels . PHPTAB . PHPTAB . '</body>$2', $currentLocallangueContent);
		file_put_contents($locallangFilepath, $newLocallangueContent);

		// Update ext_tables.sql
		$sqlFilecontent = file_exists($sqlFilepath) ? file_get_contents($sqlFilepath) : '';
		
		$sqlUpdatecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/RootFileTemplate/ext_tables.sql');
		$sqlUpdatecontent = str_replace(
			array('{SQLTABLENAME}', '{SQLFIELDS}'),
			array($sqlTableName, $sqlFields),
			$sqlUpdatecontent
		);

		file_put_contents($sqlFilepath, $sqlFilecontent . LF . LF . $sqlUpdatecontent);

		echo 'ext_tables.sql updated.' . LF;

		// Update ext_tables.php
		if(file_exists($extTablesFilepath))
		{
			$extTableFileContent = file_get_contents($extTablesFilepath);

			$extTableUpdatecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/RootFileTemplate/ext_tables_withtca.php');
			$extTableUpdatecontent = str_replace(
				array('{SQLTABLENAME}', '{EXTENSION}', '{MODEL}', '{LISTFIELDS}', '{ENABLECOLUMNS}', '{SORTING}'),
				array($sqlTableName, $this->extensionKey, Utility::camelCase($this->model), implode(',', $fieldList), $enableColumnsExttable, $sortingExttable),
				$extTableUpdatecontent
			);

			file_put_contents($extTablesFilepath, $extTableFileContent . LF . LF . $extTableUpdatecontent);
		}
		else
		{
			$extTableUpdatecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/RootFileTemplate/ext_tables_new_withtca.php');
			$extTableUpdatecontent = str_replace(
				array('{SQLTABLENAME}', '{EXTENSION}', '{MODEL}', '{LISTFIELDS}', '{ENABLECOLUMNS}', '{SORTING}'),
				array($sqlTableName, $this->extensionKey, Utility::camelCase($this->model), implode(',', $fieldList), $enableColumnsExttable, $sortingExttable),
				$extTableUpdatecontent
			);

			file_put_contents($extTablesFilepath, $extTableUpdatecontent);
		}

		echo 'ext_tables.php updated.' . LF;
	}	 

	/**
	 * create model
	 * @param array $fields fields informations
	 * @param bool $useStartEndtime use start and endtime field
	 * @param bool $useFegroup use fe group access
	 * @param bool $useSorting use manuel sorting
	 */ 
	protected function createModel($fields, $useStartEndtime = FALSE, $useFegroup = FALSE, $useSorting = FALSE) {
		$modelDirectoryPath = EXTENSIONS_PATH . $this->extensionKey . '/Classes/Domain/Model/';
		if(!file_exists($modelDirectoryPath)) { mkdir($modelDirectoryPath, 0770, TRUE); }
		
		$modelFilepath = $modelDirectoryPath . Utility::camelCase($this->model) . '.php';

		if($useStartEndtime) {
			$fields['starttime'] = array('type' => 'int');
			$fields['endtime']   = array('type' => 'int');
		}

		if($useFegroup) {
			$fields['fe_group']  = array(
				'type'             => 'foreignkey',
				'is_multiple'      => TRUE,
				'associated_model' => '\\TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup'
			);
		}

		if($useSorting) {
			$fields['sorting']   = array('type' => 'int');
		}
		
		$modelPhpcode = '';
		foreach($fields as $fieldname => $fieldinfo) {			
			$modelPhpcode.= PHPTAB . '/**' . LF;
			if($fieldinfo['type'] == 'foreignkey') {
				if($fieldinfo['is_multiple']) {
					$modelPhpcode.= PHPTAB . ' * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<' . $fieldinfo['associated_model'] . '>' . LF;
				} else {
					$modelPhpcode.= PHPTAB . ' * @var ' . $fieldinfo['associated_model'] . LF;
				}
			} else {
				$modelPhpcode.= PHPTAB . ' * @var ' . $fieldinfo['type'] . LF;
			}
			$modelPhpcode.= PHPTAB . ' */' . LF;
			$modelPhpcode.= PHPTAB . 'protected $' . lcfirst(Utility::camelCase($fieldname)) . ';' . LF . LF;			
		}

		foreach($fields as $fieldname => $fieldinfo) {
			$modelPhpcode.= PHPTAB . '/**' . LF;
			$modelPhpcode.= PHPTAB . ' * return ' . lcfirst(Utility::camelCase($fieldname)) . ' value' . LF;
			$modelPhpcode.= PHPTAB . ' * ' . LF;
			if($fieldinfo['type'] == 'foreignkey') {
				if($fieldinfo['is_multiple']) {
					$modelPhpcode.= PHPTAB . ' * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<' . $fieldinfo['associated_model'] . '> the value' . LF;
				} else {
					$modelPhpcode.= PHPTAB . ' * @return ' . $fieldinfo['associated_model'] . ' the value' . LF;
				}
				
			} else {
				$modelPhpcode.= PHPTAB . ' * @return ' . $fieldinfo['type'] . ' the value' . LF;
			}
			
			$modelPhpcode.= PHPTAB . ' */' . LF;
			$modelPhpcode.= PHPTAB . 'public function get' . Utility::camelCase($fieldname) . '() {' . LF;
			$modelPhpcode.= PHPTAB . PHPTAB . 'return $this->' . lcfirst(Utility::camelCase($fieldname)) . ';' . LF;
			$modelPhpcode.= PHPTAB . '}' . LF . LF;

			$modelPhpcode.= PHPTAB . '/**' . LF;
			$modelPhpcode.= PHPTAB . ' * set ' . lcfirst(Utility::camelCase($fieldname)) . ' value' . LF;
			$modelPhpcode.= PHPTAB . ' * ' . LF;
			if($fieldinfo['type'] == 'foreignkey') {
				if($fieldinfo['is_multiple']) {
					$modelPhpcode.= PHPTAB . ' * @params \TYPO3\CMS\Extbase\Persistence\ObjectStorage<' . $fieldinfo['associated_model'] . '> $' . lcfirst(Utility::camelCase($fieldname)) . ' the value' . LF;
				} else {
					$modelPhpcode.= PHPTAB . ' * @params ' . $fieldinfo['associated_model'] . ' $' . lcfirst(Utility::camelCase($fieldname)) . ' the value' . LF;
				}
				
			} else {
				$modelPhpcode.= PHPTAB . ' * @params ' . $fieldinfo['type'] . ' $' . lcfirst(Utility::camelCase($fieldname)) . ' the value' . LF;
			}
			
			$modelPhpcode.= PHPTAB . ' * @return \\' . $this->vendor . '\\' . Utility::camelCase($this->extensionKey) . '\\Domain\\Model\\' . Utility::camelCase($this->model) . ' the current instance' . LF;
			$modelPhpcode.= PHPTAB . ' */' . LF;
			$modelPhpcode.= PHPTAB . 'public function set' . Utility::camelCase($fieldname) . '($' . lcfirst(Utility::camelCase($fieldname)) . ') {' . LF;
			$modelPhpcode.= PHPTAB . PHPTAB . '$this->' . lcfirst(Utility::camelCase($fieldname)) . ' = $' . lcfirst(Utility::camelCase($fieldname)) . ';' . LF;
			$modelPhpcode.= PHPTAB . PHPTAB . 'return $this;' . LF;
			$modelPhpcode.= PHPTAB . '}' . LF . LF;
		}

		$modelFilecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ClassTemplate/Model.php');
		$modelFilecontent = str_replace(
			array('{VENDOR}', '{EXTENSION}', '{CLASSNAME}', '{PHPCODE}'),
			array($this->vendor, Utility::camelCase($this->extensionKey), Utility::camelCase($this->model), $modelPhpcode),
			$modelFilecontent
		);
		file_put_contents($modelFilepath, $modelFilecontent);
		echo 'Model create.' . LF;
	}

	/**
	 * create repository
	 */ 
	protected function createRepository() {
		$repositoryDirectoryPath = EXTENSIONS_PATH . $this->extensionKey . '/Classes/Domain/Repository/';
		if(!file_exists($repositoryDirectoryPath)) { mkdir($repositoryDirectoryPath, 0770, TRUE); }

		$repositoryFilepath = $repositoryDirectoryPath . Utility::camelCase($this->model) . 'Repository.php';

		$repositoryFilecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ClassTemplate/Repository.php');
		$repositoryFilecontent = str_replace(
			array('{VENDOR}', '{EXTENSION}', '{CLASSNAME}'),
			array($this->vendor, Utility::camelCase($this->extensionKey), Utility::camelCase($this->model) . 'Repository'),
			$repositoryFilecontent
		);
		file_put_contents($repositoryFilepath, $repositoryFilecontent);
		echo 'Repository create.' . LF;
	}
}
