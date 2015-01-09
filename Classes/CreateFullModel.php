<?php
/****

TODO
Gerer ext_tables.php au moins basiquement

****/
namespace Ameos\AmeosKickstart;

use Ameos\AmeosKickstart\Help;
use Ameos\AmeosKickstart\Utility;

class CreateFullModel {

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
					echo 'Type of the ' . $index . ' : ';
					$fieldtype = fgets($fileResource, 1024);
					$fieldtype = trim($fieldtype);

				} while(trim($fieldtype) == '');				
			}

			if(!$stop) {
				$fields[$fieldname]['type'] = $fieldtype;
				switch ($fieldtype) {
					case 'string':
						echo 'Tca type of the "' . $fieldname . '" field (input / text / rte / select / radio) '. LF;
						echo 'If select or radio, only base will be set. : '. LF;
						$tcaType = fgets($fileResource, 1024);
						$fields[$fieldname]['tca'] = trim($tcaType);
						break;
					case 'int':
						echo 'Tca type of the "' . $fieldname . '" field (input / select / radio / date / datetime / boolean ) '. LF;
						echo 'If select or radio, only base will be set. : '. LF;
						$tcaType = fgets($fileResource, 1024);
						$fields[$fieldname]['tca'] = trim($tcaType);
						break;
					default:
						break;
				}
				
				$index++;
			}

		} while(!$stop);
		
		fclose($fileResource);		

		$this->createRepository();
		$this->createModel($fields);
		$this->createTca($fields);
		
		echo LF .'Scritp finished.' . LF;

		if($this->errors != ''){
			echo LF .'--------------------' . LF . LF;
			echo 'Some errors occured : ' . LF . LF;
			echo $this->errors;
			echo LF . LF .'--------------------' . LF . LF;
		}
		if($this->warning != ''){
			echo LF .'--------------------' . LF . LF;
			echo 'You should verify these info : ' . LF . LF;
			echo $this->warning;
			echo LF . LF .'--------------------' . LF . LF;
		}

		die();
	}

	private function createTca($fields){
		$extensionPath = EXTENSIONS_PATH . $this->extensionKey . '/';
		
		
		$tcaDirectoryPath = $extensionPath . 'Configuration/Tca/';
		if(!file_exists($tcaDirectoryPath)) { mkdir($tcaDirectoryPath, 0770, TRUE); }
		
		$tcaFilepath = $tcaDirectoryPath . $this->model . '.php';
		$sqlFilepath = $extensionPath . 'ext_tables.sql';
		$extTablesFilepath = $extensionPath . 'ext_tables.php';

		$sqlTableName = 'tx_'.str_replace('_', '', $this->extensionKey).'_domain_model_'.strtolower($this->model);

		
		$tcaPhpCode = '';
		$sqlFields = '';

		$tcaPhpCode .= '$TCA[\'' . $sqlTableName . '\'] = array(' . LF;
		$tcaPhpCode .= PHPTAB . '\'ctrl\' => $TCA[\''.$sqlTableName.'\'][\'ctrl\'],' . LF;
    	$tcaPhpCode .= PHPTAB . '\'interface\' => array(' . LF;
        $tcaPhpCode .= PHPTAB . PHPTAB . '\'showRecordFieldList\' => \'\'' . LF;
    	$tcaPhpCode .= PHPTAB . '),' . LF;
    	$tcaPhpCode .= PHPTAB . '\'feInterface\' => $TCA[\''.$sqlTableName.'\'][\'feInterface\'],' . LF;
    	$tcaPhpCode .= PHPTAB . '\'columns\' => array(' . LF;

		$fieldList = '';
		foreach ($fields as $fieldname => $fieldinfo) {
			// On met à jour la liste des items
			$fieldList .= $fieldname.',';

			$tcaPhpCode .= PHPTAB . PHPTAB . '\''.$fieldname.'\' => array(' . LF;
			$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . '\'exclude\' => 1,' . LF;
			$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . '\'label\' =>  \'LLL:EXT:'.$this->extensionKey.'/Resources/Private/Language/locallang_db.xml:'.$sqlTableName.'.'.$fieldname.'\',' . LF;
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
				default:
					$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'type\' => \'select\',' . LF;
					// group of record is of the form : Tx_Extbase_Persistence_ObjectStorage<Model>
					// The tca must allow multiple items
					// TODO : if objectstorage is namespaced 
					if(substr( $fieldinfo['type'], 0, 36 ) === 'Tx_Extbase_Persistence_ObjectStorage' || substr( $fieldinfo['type'], 0, 44 ) === '\\TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage')
					{
						$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'maxitems\' => 10,' . LF;
						$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'size\' => 10,' . LF;
						
						// From now on we need only content ( = Model ) and not container
						preg_match('~<(.*?)>~', $fieldinfo['type'], $output);
						print_r($output);
						echo LF.LF.LF.LF;
						$fieldinfo['type'] = $output[1]; 
					}
					else{
						$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'maxitems\' => 1,' . LF;
						$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'size\' => 1,' . LF;
					}

					if(substr( $fieldinfo['type'], 0, 1 ) === '\\')
					{
						// Exploding namespace to find correct info
						$path = explode('\\', $fieldinfo['type']);
						
						$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'foreign_table\' => \''.'tx_'.strtolower($path[2]).'_domain_model_'.strtolower($path[5]).'\',' . LF;				
						$sqlFields  .= PHPTAB . $fieldname . ' int(11) DEFAULT \'0\' NOT NULL,' . LF;
					}
					// If no namespace, we suppose it's a Model from the current extension
					else
					{
						$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . PHPTAB . '\'foreign_table\' => \''.'tx_'.str_replace('_', '', $this->extensionKey).'_domain_model_'.strtolower($fieldinfo['type']).'\',' . LF;
						
						$sqlFields  .= PHPTAB .$fieldname . ' int(11) DEFAULT \'0\' NOT NULL,' . LF;
						
						$this->warning .= 'Type "'. $fieldinfo['type'] .'" assumed to be model of extension "'. $this->extensionKey .'"' . LF;
					}
					break;
			}
			$tcaPhpCode .= PHPTAB . PHPTAB . PHPTAB . ')' . LF;
			$tcaPhpCode .= PHPTAB . PHPTAB . '),' . LF;
		}
		$tcaPhpCode .= PHPTAB . '),' . LF . LF;
		$tcaPhpCode .= PHPTAB . '\'types\' => array(' . LF;
		$tcaPhpCode .= PHPTAB . PHPTAB . '\'0\' => array(\'showitem\' => \''.$fieldList.'\')' . LF;
		$tcaPhpCode .= PHPTAB . '),' . LF;
		$tcaPhpCode .= PHPTAB . '\'palettes\' => array(' . LF;
    	$tcaPhpCode .= PHPTAB . PHPTAB . '\'1\' => array(\'showitem\' => \'\')' . LF;
		$tcaPhpCode .= PHPTAB . ')' . LF;
		$tcaPhpCode .= ');' . LF;
        
        // Create TCA file
		$tcaFilecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ClassTemplate/Tca.php');
		$tcaFilecontent = str_replace(
			array('{PHPCODE}'),
			array($tcaPhpCode),
			$tcaFilecontent
		);
		file_put_contents($tcaFilepath, $tcaFilecontent);

		echo 'Tca created.' . LF;

		// Update ext_tables.sql 
		$sqlFilecontent = file_get_contents($sqlFilepath);

		$sqlUpdatecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ClassTemplate/Sql.sql');
		$sqlUpdatecontent = str_replace(
			array('{SQLTABLENAME}','{SQLFIELDS}'),
			array($sqlTableName,$sqlFields),
			$sqlUpdatecontent
		);

		file_put_contents($sqlFilepath, $sqlFilecontent . LF . LF . $sqlUpdatecontent);

		echo 'ext_tables.sql updated.' . LF;

		// Update ext_tables.php
		if(file_exists($extTablesFilepath))
		{
			$extTableFileContent = file_get_contents($extTablesFilepath);

			$extTableUpdatecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ClassTemplate/Exttables.php');
			$extTableUpdatecontent = str_replace(
				array('{SQLTABLENAME}','{EXTENSION}','{MODEL}','{LISTFIELDS}'),
				array($sqlTableName,$this->extensionKey,$this->model,$fieldList),
				$extTableUpdatecontent
			);

			file_put_contents($extTablesFilepath, $extTableFileContent . LF . LF . $extTableUpdatecontent);
		}
		else
		{
			$extTableUpdatecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ClassTemplate/NewExttables.php');
			$extTableUpdatecontent = str_replace(
				array('{SQLTABLENAME}','{EXTENSION}','{MODEL}','{LISTFIELDS}'),
				array($sqlTableName,$this->extensionKey,$this->model,$fieldList),
				$extTableUpdatecontent
			);

			file_put_contents($extTablesFilepath, $extTableUpdatecontent);
		}

		echo 'ext_tables.php updated.' . LF;


	}	 

	private function createModel($fields){
		$modelDirectoryPath = EXTENSIONS_PATH . $this->extensionKey . '/Classes/Domain/Model/';
		if(!file_exists($modelDirectoryPath)) { mkdir($modelDirectoryPath, 0770, TRUE); }
		
		$modelFilepath = $modelDirectoryPath . $this->model . '.php';

		$modelPhpcode = '';
		foreach($fields as $fieldname => $fieldinfo) {
			
			$modelPhpcode.= PHPTAB . '/**' . LF;
			$modelPhpcode.= PHPTAB . ' * @var ' . $fieldinfo['type'] . LF;
			$modelPhpcode.= PHPTAB . ' */' . LF;
			$modelPhpcode.= PHPTAB . 'protected $' . lcfirst(Utility::camelCase($fieldname)) . ';' . LF . LF;			
		}

		foreach($fields as $fieldname => $fieldinfo) {
			$modelPhpcode.= PHPTAB . '/**' . LF;
			$modelPhpcode.= PHPTAB . ' * return ' . lcfirst(Utility::camelCase($fieldname)) . ' value' . LF;
			$modelPhpcode.= PHPTAB . ' * ' . LF;
			$modelPhpcode.= PHPTAB . ' * @return ' . $fieldinfo['type'] . ' the value' . LF;
			$modelPhpcode.= PHPTAB . ' */' . LF;
			$modelPhpcode.= PHPTAB . 'public function get' . Utility::camelCase($fieldname) . '() {' . LF;
			$modelPhpcode.= PHPTAB . PHPTAB . 'return $this->' . lcfirst(Utility::camelCase($fieldname)) . ';' . LF;
			$modelPhpcode.= PHPTAB . '}' . LF . LF;

			$modelPhpcode.= PHPTAB . '/**' . LF;
			$modelPhpcode.= PHPTAB . ' * set ' . lcfirst(Utility::camelCase($fieldname)) . ' value' . LF;
			$modelPhpcode.= PHPTAB . ' * ' . LF;
			$modelPhpcode.= PHPTAB . ' * @params ' . $fieldinfo['type'] . ' $' . lcfirst(Utility::camelCase($fieldname)) . ' the value' . LF;
			$modelPhpcode.= PHPTAB . ' * @return \\' . $this->vendor . '\\' . Utility::camelCase($this->extensionKey) . '\\Domain\\Model\\' . $this->model . ' the current instance' . LF;
			$modelPhpcode.= PHPTAB . ' */' . LF;
			$modelPhpcode.= PHPTAB . 'public function set' . Utility::camelCase($fieldname) . '($' . lcfirst(Utility::camelCase($fieldname)) . ') {' . LF;
			$modelPhpcode.= PHPTAB . PHPTAB . '$this->' . lcfirst(Utility::camelCase($fieldname)) . ' = $' . lcfirst(Utility::camelCase($fieldname)) . ';' . LF;
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
	}

	private function createRepository(){
		$repositoryDirectoryPath = EXTENSIONS_PATH . $this->extensionKey . '/Classes/Domain/Repository/';
		if(!file_exists($repositoryDirectoryPath)) { mkdir($repositoryDirectoryPath, 0770, TRUE); }

		$repositoryFilepath = $repositoryDirectoryPath . $this->model . 'Repository.php';

		$repositoryFilecontent = file_get_contents(EXTENSIONS_PATH . 'ameos_kickstarter/Resources/Private/ClassTemplate/Repository.php');
		$repositoryFilecontent = str_replace(
			array('{VENDOR}', '{EXTENSION}', '{CLASSNAME}'),
			array($this->vendor, Utility::camelCase($this->extensionKey), $this->model . 'Repository'),
			$repositoryFilecontent
		);
		file_put_contents($repositoryFilepath, $repositoryFilecontent);
		echo 'Repository create.' . LF;
	}	 

	 
}
