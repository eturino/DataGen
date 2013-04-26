<?php

class EtuDev_DataGen_RowClassesGenerator
{

    protected $prefix = 'EtuDev_Data_';

    protected $prefix_superclasses = 'EtuDev_Data_';

    protected $folder = '../Data';

    protected $table_prefix_changes = array();

    protected $schemas = array();

    protected $db_config = array();

    protected $use_traits = false;

    /**
     * execute!
     * @return EtuDev_DataGen_RowClassesGenerator
     */
    public function run()
    {
        foreach ($this->schemas as $schema => $schema_classname) {
            $this->runSchema($schema, $schema_classname);
        }
        return $this;
    }

    /**
     * @param string $db_host
     * @param string $db_user
     * @param string $db_pass
     * @param string $db_name one of the schemas
     *
     * @return EtuDev_DataGen_RowClassesGenerator
     */
    public function setDbConfig($db_host, $db_user, $db_pass, $db_name)
    {
        // de momento sólo mysql
        $db_type = 'mysql';

        $this->db_config = array('host' => $db_host, 'name' => $db_name, 'user' => $db_user, 'pass' => $db_pass, 'type' => $db_type);
        return $this;
    }

    /**
     * @param array $schemasAndClasses schema => schema_classname
     *
     * @return EtuDev_DataGen_RowClassesGenerator
     */
    public function addSchemas($schemasAndClasses)
    {
        $this->schemas = $schemasAndClasses;
        return $this;
    }

    /**
     * change prefix from X to Y (or remove if Y is empty) from tablenames to the classes
     *
     * @param string $table_prefix
     * @param string $new_table_prefix
     *
     * @return EtuDev_DataGen_RowClassesGenerator
     */
    public function addTablePrefixChange($table_prefix, $new_table_prefix)
    {
        $this->table_prefix_changes[$table_prefix] = $new_table_prefix;
        return $this;
    }

    /**
     * the prefix of the classes to be generated; 'EtuDev_Data_' by default
     *
     * @param string $prefix
     *
     * @return EtuDev_DataGen_RowClassesGenerator
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * the folder where we're going to generate the files; './Data' by default
     *
     * @param string $folder
     *
     * @return EtuDev_DataGen_RowClassesGenerator
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
        return $this;
    }

    /**
     * @param string $schema
     * @param string $schema_classname
     *
     * @return EtuDev_DataGen_RowClassesGenerator
     */
    protected function runSchema($schema, $schema_classname)
    {
        echo ">>SCHEMA $schema \n";

        $all = $this->getTablesFromSchema($schema);

        foreach ($all as $t) {
            $this->processTable($t, $schema, $schema_classname);
        }
        return $this;
    }

    /**
     * execute and create all the stuff for the table
     *
     * @param string $tableName
     * @param string $schema
     * @param string $schema_classname
     */
    protected function processTable($tableName, $schema, $schema_classname)
    {

        $superRowClassName         = $this->prefix_superclasses . 'Row';
        $superPseudoArrayClassName = $this->prefix_superclasses . 'PseudoArray';
        $superTableWithIdClassName = $this->prefix_superclasses . 'TableWithId';
        $superTableWithIdTraitName = $this->prefix_superclasses . 'Traits_TableWithId';
        $superTablelassName        = $this->prefix_superclasses . 'Table';


        $usedTableName = $this->getUsedTableName($tableName);

        $camelTableName = $this->toCamelCase($usedTableName, true);

        $parenTabletClass = $this->prefix . $schema_classname . '_Table';

        $classNameObjClass   = $this->prefix . $schema_classname . '_Gen_Obj_' . $camelTableName;
        $classNameRowClass   = $this->prefix . $schema_classname . '_Gen_Row_' . $camelTableName;
        $classNamePAClass    = $this->prefix . $schema_classname . '_Gen_PA_' . $camelTableName;
        $classNameTableClass = $this->prefix . $schema_classname . '_Gen_Table_' . $camelTableName;

        $classNameObjFinalClass   = $this->prefix . $schema_classname . '_Obj_' . $camelTableName;
        $classNameRowFinalClass   = $this->prefix . $schema_classname . '_Row_' . $camelTableName;
        $classNamePAFinalClass    = $this->prefix . $schema_classname . '_PA_' . $camelTableName;
        $classNameTableFinalClass = $this->prefix . $schema_classname . '_Table_' . $camelTableName;

        $fileNameObjClass    = $camelTableName;
        $fileNameRowClass    = $camelTableName;
        $fileNamePAClass     = $camelTableName;
        $fileNameTableClass  = $camelTableName;
        $fileNameParentTable = $schema_classname;

        //folders

        if (!is_dir($this->folder)) {
            exec("mkdir " . $this->folder);
            echo "se ha creado el directorio " . $this->folder . "\n";
        }

        if (!is_dir($this->folder . DIRECTORY_SEPARATOR . $schema_classname)) {
            exec("mkdir " . $this->folder . DIRECTORY_SEPARATOR . $schema_classname);
            echo "se ha creado el directorio " . $this->folder . $schema_classname . "\n";
        }

        $parenTabletFolder = $this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/';

        if (!is_dir($this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/Gen/')) {
            exec("mkdir " . $this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/Gen/');
            echo "se ha creado el directorio " . $this->folder . $schema_classname . '/Gen/' . "\n";
        }


        $rowFinalFolder = $this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/Row/';

        if (!is_dir($rowFinalFolder)) {
            exec("mkdir " . $rowFinalFolder);
            echo "se ha creado el directorio " . $rowFinalFolder . "\n";
        }

        $objFinalFolder = $this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/Obj/';

        if (!is_dir($objFinalFolder)) {
            exec("mkdir " . $objFinalFolder);
            echo "se ha creado el directorio " . $objFinalFolder . "\n";
        }

        $paFinalFolder = $this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/PA/';

        if (!is_dir($paFinalFolder)) {
            exec("mkdir " . $paFinalFolder);
            echo "se ha creado el directorio " . $paFinalFolder . "\n";
        }

        $tableFinalFolder = $this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/Table/';

        if (!is_dir($tableFinalFolder)) {
            exec("mkdir " . $tableFinalFolder);
            echo "se ha creado el directorio " . $tableFinalFolder . "\n";
        }


        $rowFolder = $this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/Gen/Row/';

        if (!is_dir($rowFolder)) {
            exec("mkdir " . $rowFolder);
            echo "se ha creado el directorio " . $rowFolder . "\n";
        }

        $objFolder = $this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/Gen/Obj/';

        if (!is_dir($objFolder)) {
            exec("mkdir " . $objFolder);
            echo "se ha creado el directorio " . $objFolder . "\n";
        }

        $paFolder = $this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/Gen/PA/';

        if (!is_dir($paFolder)) {
            exec("mkdir " . $paFolder);
            echo "se ha creado el directorio " . $paFolder . "\n";
        }

        $tableFolder = $this->folder . DIRECTORY_SEPARATOR . $schema_classname . '/Gen/Table/';

        if (!is_dir($tableFolder)) {
            exec("mkdir " . $tableFolder);
            echo "se ha creado el directorio " . $tableFolder . "\n";
        }


        //table

        $getbyidmethod = <<<MET

	/**
	 * @param int \$id
	 * @return $classNameRowFinalClass
	 */
	public function getRowById(\$id){
		return \$this->fetchRow(array('id = ?' => \$id));
	}

	/**
	 * @param int \$id
	 * @return $classNameRowFinalClass
	 */
	public function getById(\$id){
		return \$this->getRowById(\$id);
	}

	public function delete(\$id) {
		if (is_numeric(\$id)) {
			return \$this->delete(array('id = ?' => \$id));
		}

		return parent::delete(\$id);
	}

MET;

        $tableInfo = $this->getTableInfo($schema, $tableName);

        $types = array();

        $hasId = false;

        $longestColumnName = 0;
        $longestTypeName   = 0;
        $columns           = array();
        foreach ($tableInfo as $columnInfo) {
            $colname = $columnInfo['COLUMN_NAME'];

            if (strtolower($colname) == 'id') {
                $hasId = true;
            }

            if (strlen($colname) > $longestColumnName) {
                $longestColumnName = strlen($colname);
            }

            $dataType = $columnInfo['DATA_TYPE'];

            $comment = $columnInfo['COLUMN_COMMENT'];

            $isNullable  = true;
            $default     = null;
            $defaultData = null;

            if ($columnInfo['IS_NULLABLE'] == 'NO') {
                $isNullable = false;
            }

            if ($columnInfo['COLUMN_DEFAULT']) {
                $default = $columnInfo['COLUMN_DEFAULT'];
            }

            @$types[$dataType]++;

            switch ($dataType) {

                case 'text' :
                case 'tinytext' :
                case 'mediumtext' :
                case 'longtext' :
                case 'varchar' :
                case 'char' :
                    $coltype = 'string';
                    if (!$isNullable && is_null($default)) {
                        $default = '';
                    }
                    break;

                case 'enum' :

                    if ($columnInfo['COLUMN_TYPE'] == "enum('','0','1')") {
                        $coltype = 'tristate|bool|int|string';
                    } elseif ($columnInfo['COLUMN_TYPE'] == "enum('','0','1','2')") {
                        $coltype = 'tetrastate|int|string';
                    } else {
                        $coltype = 'string';
                    }

                    if (!$isNullable && is_null($default)) {
                        $default = '';
                    }

                    $comment = $columnInfo['COLUMN_TYPE'] . ' ' . $comment;
                    break;

                case 'date' :
                case 'datetime' :
                case 'timestamp' :

                    if (!$isNullable && is_null($default)) {

                        if ($colname == 'created_date' || $colname == 'updated_date') {
                            $default = 'CURRENT_TIMESTAMP';
                        } else {
                            $default = '0000-00-00';
                        }

                    }

                    if (in_array(strtoupper($default), array('NOW', 'NOW()', 'CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP()'))) {
                        $defaultData = "date('Y-m-d H:i:s')";
                    } elseif (in_array(strtoupper($default), array('CURDATE', 'CURDATE()', 'CURRENT_DATE', 'CURRENT_DATE()'))) {
                        $defaultData = "date('Y-m-d')";
                    } elseif (in_array(strtoupper($default), array('CURTIME', 'CURTIME()', 'CURRENT_TIME', 'CURRENT_TIME()'))) {
                        $defaultData = "date('H:i:s')";
                    }

                    $coltype = 'date';
                    break;

                case 'float' :
                case 'double' :
                case 'decimal' :
                    $coltype = 'float';
                    if (!$isNullable && is_null($default)) {
                        $default = 0.0;
                    }

                    break;

                case 'int' :
                case 'tinyint' :
                case 'smallint' :
                case 'mediumint' :

                    if ($columnInfo['COLUMN_TYPE'] == 'tinyint(1)' || $columnInfo['COLUMN_TYPE'] == 'int(1)') {
                        $coltype = 'bool';
                        if (!$isNullable && is_null($default)) {
                            $default = false;
                        }
                    } else {
                        $coltype = 'int';
                        if (!$isNullable && is_null($default) && $columnInfo['EXTRA'] != 'auto_increment') {
                            $default = 0;
                        }
                    }

                    break;

                default :
                    if (!$isNullable && is_null($default)) {
                        $default = '';
                    }
                    $coltype = 'mixed';
                    break;
            }


            if (strlen($coltype) > $longestTypeName) {
                $longestTypeName = strlen($coltype);
            }

            $defValue = 'NULL';
            if (!is_null($default)) {
                if (is_bool($default)) {
                    $defValue = ($default ? 'true' : 'false');
                } elseif (is_int($default) || is_float($default)) {
                    $defValue = $default;
                } else {
                    $defValue = "'" . $default . "'";
                }
                $comment = 'default:' . $defValue . ', ' . $comment;
            }

            if (!$isNullable) {
                $comment = 'NOT NULL, ' . $comment;
            }

            $columns[$colname] = array(
                'name'            => $colname,
                'type'            => $coltype,
                'comment'         => trim($comment),
                'is_nullable'     => $isNullable,
                'default'         => $default,
                'info'            => $columnInfo,
                'default_printed' => $defaultData ? : $defValue
            );
        }


        //Row Class

        $rowblocks            = '';
        $tableblocks          = '';
        $defaultsblocks       = '';
        $defaultsDocblockData = '';
        $colblocks            = '';
        foreach ($columns as $c) {
            $rowblocks .= ' * @property ' . $c['type'] . ' $' . $c['name'] . ' ' . $c['comment'] . "\n";
            $tableblocks .= ' * column ' . $c['type'] . ' $' . $c['name'] . ' ' . $c['comment'] . "\n";

            $spacesNeeded = $longestColumnName - strlen($c['name']) + 1;

            $colblocks .= '		$infos[\'' . $c['name'] . '\']';
            for ($si = 0; $si < $spacesNeeded; $si++) {
                $colblocks .= ' ';
            }
            $colblocks .= '= array(\'type\' => \'' . $c['type'] . '\',';

            $spacesNeededType = $longestTypeName - strlen($c['type']) + 1;
            for ($si = 0; $si < $spacesNeededType; $si++) {
                $colblocks .= ' ';
            }

            if ($c['is_nullable']) {
                $colblocks .= '\'nullable\' => true, ';
            } else {
                $colblocks .= '\'nullable\' => false, ';

                $defValue = $c['default_printed'];


                if (!is_null($c['default'])) {
                    $defaultsblocks .= '		$def["' . $c['name'] . '"] = ';
                    $defaultsblocks .= $defValue . "; \n";
                    $defaultsDocblockData .= '	 * @uses $' . $c['name'] . ' default value: ' . $defValue . " \n";
                }

            }

            if (is_null($c['default'])) {
                $colblocks .= "\t" . '\'default\' => null,';
            } else {
                $colblocks .= "\t" . '\'default\' => ' . $c['default_printed'] . ',';
            }

            $colblocks .= "\t" . '\'name\' => \'' . $c['name'] . '\');' . "\n";
        }
        $rowblocks   = ' ' . trim($rowblocks);
        $tableblocks = ' ' . trim($tableblocks);

        if ($defaultsblocks) {
            $defaultsDocblockData = '	 ' . trim($defaultsDocblockData);
            $defaultsblocks       = <<<DEFAULTS

	/**
	 * Default data
	 *
	 * @return array
	 *
$defaultsDocblockData
	 */
	public function _getDefaultData(){
		\$def = parent::_getDefaultData();
$defaultsblocks
		return \$def;
	}

DEFAULTS;

        }

        //OBJ Data


        $objData = <<<TEXTO
/**
 * interface Object of table $tableName
 *
 * @table $tableName
 *
$rowblocks
 *
 */
interface $classNameObjClass {

}

TEXTO;

        echo "$classNameObjClass \n";

        $docFileName = $objFolder . $fileNameObjClass . '.php';

        $options = 'w';

        $fh = fopen($docFileName, $options) or die("can't open file");
        fwrite($fh, "<?php \n" . $objData);
        fclose($fh);

        //ROW Data


        $rowData = <<<TEXTO
/**
 * class Row of table $tableName
 *
 * @table $tableName
 *
$rowblocks
 *
 * @method $classNameTableFinalClass getTable()
 */
class $classNameRowClass extends $superRowClassName implements $classNameObjFinalClass {

	protected \$_tableClass = '$classNameTableFinalClass';

	const TABLE_NAME = '$tableName';
	const TABLE_CLASS_NAME = '$classNameTableFinalClass';

$defaultsblocks

}

TEXTO;

        echo "$classNameRowClass \n";

        $docFileName = $rowFolder . $fileNameRowClass . '.php';

        $options = 'w';

        $fh = fopen($docFileName, $options) or die("can't open file");
        fwrite($fh, "<?php \n" . $rowData);
        fclose($fh);

        //PA Data

        $paData = <<<TEXTO
/**
 * class PseudoArray Row of table $tableName
 *
 * @table $tableName
 *
$rowblocks
 *
 */
class $classNamePAClass extends $superPseudoArrayClassName implements $classNameObjFinalClass {

	const TABLE_NAME = '$tableName';
	const TABLE_CLASS_NAME = '$classNameTableFinalClass';

$defaultsblocks

}

TEXTO;

        echo "$classNamePAClass \n";

        $docFileName = $paFolder . $fileNamePAClass . '.php';

        $options = 'w';

        $fh = fopen($docFileName, $options) or die("can't open file");
        fwrite($fh, "<?php \n" . $paData);
        fclose($fh);

        //Table Class
        $methodExtra = '';
        $docmetExtra = '';

        $tableInterfaces = array();
        $tableTraits     = array();
        if ($hasId) {
            $tableInterfaces[] = $superTableWithIdClassName;
            if ($this->isUseTraits()) {
                $tableTraits[] = $superTableWithIdTraitName;
                $docmetExtra   = " * @method $classNameRowFinalClass getRowById(\$id)" . "\n" . " * @method $classNameRowFinalClass getById(\$id)";
            } else {
                $methodExtra = $getbyidmethod;
            }
        }

        if ($tableInterfaces) {
            $tableInterfaces = ' implements ' . implode(', ', $tableInterfaces);
        } else {
            $tableInterfaces = '';
        }

        if ($tableTraits) {
            $tableTraits = ' use ' . implode(', ', $tableTraits) . ';';
        } else {
            $tableTraits = '';
        }


        $tableData = <<<TEXTO
/**
 * class for table $tableName
 *
 * @table $tableName
 *
$tableblocks
 *
 * @method static $classNameTableFinalClass getInstance()
 *
 * @method $classNameRowFinalClass createRow()
 * @method $classNameRowFinalClass fetchNew()
 * @method $classNameRowFinalClass fetchRow()
$docmetExtra
 */
class $classNameTableClass extends $parenTabletClass $tableInterfaces {

	$tableTraits

	protected \$_name = '$tableName';
	protected \$_rowClass = '$classNameRowFinalClass';

	/**
	 * @static
	 * @return array
	 */
	static public function loadColumnsInfoArray(){
		\$infos = array();

$colblocks
		return \$infos;
	}

$methodExtra

}

TEXTO;

        echo "$classNameTableClass \n";
        //		$docName = str_replace(".php", ".html", basename($file));

        $docFileName = $tableFolder . $fileNameTableClass . '.php';

        $options = 'w';

        $fh = fopen($docFileName, $options) or die("can't open file");
        fwrite($fh, "<?php \n" . $tableData);
        fclose($fh);

        //test for final (only existence)

        //parent table
        $docFileName = $parenTabletFolder . 'Table.php';
        if (!file_exists($docFileName)) {
            echo "$parenTabletClass \n";
            $options = 'w';
            $fh = fopen($docFileName, $options) or die("can't open file");
            //Table Class

            $tableData = <<<TEXTO
/**
 * define diferentes adapters para cada schema
 *
 */
abstract class $parenTabletClass extends $superTablelassName {

	const ZDB_ADAPTER_KEY = 'ZF_DB_Adapters_$schema_classname';
	static protected \$tableSchema;

}

TEXTO;
            fwrite($fh, "<?php \n" . $tableData);
            fclose($fh);
        }


        //table
        $docFileName = $tableFinalFolder . $fileNameTableClass . '.php';
        if (!file_exists($docFileName)) {
            echo "$classNameTableFinalClass \n";
            $options = 'w';
            $fh = fopen($docFileName, $options) or die("can't open file");
            //Table Class

            $tableData = <<<TEXTO
/**
 * class to use and extend for table $tableName
 *
 */
class $classNameTableFinalClass extends $classNameTableClass {

}

TEXTO;
            fwrite($fh, "<?php \n" . $tableData);
            fclose($fh);
        }


        //obj
        $docFileName = $objFinalFolder . $fileNameObjClass . '.php';
        if (!file_exists($docFileName)) {
            echo "$classNameObjFinalClass \n";
            $options = 'w';
            $fh = fopen($docFileName, $options) or die("can't open file");
            //Table Class

            $objData = <<<TEXTO
/**
 * interface to use and extend for Object (Row/PA) of table $tableName
 *
 */
interface $classNameObjFinalClass extends $classNameObjClass {

}

TEXTO;
            fwrite($fh, "<?php \n" . $objData);
            fclose($fh);
        }

        //row
        $docFileName = $rowFinalFolder . $fileNameRowClass . '.php';
        if (!file_exists($docFileName)) {
            echo "$classNameRowFinalClass \n";
            $options = 'w';
            $fh = fopen($docFileName, $options) or die("can't open file");
            //Table Class

            $rowData = <<<TEXTO
/**
 * class to use and extend for Row of table $tableName
 *
 */
class $classNameRowFinalClass extends $classNameRowClass {

}

TEXTO;
            fwrite($fh, "<?php \n" . $rowData);
            fclose($fh);
        }

        //pa
        $docFileName = $paFinalFolder . $fileNamePAClass . '.php';
        if (!file_exists($docFileName)) {
            echo "$classNamePAFinalClass \n";
            $options = 'w';
            $fh = fopen($docFileName, $options) or die("can't open file");
            //Table Class

            $paData = <<<TEXTO
/**
 * class to use and extend for PA of Row of table $tableName
 *
 */
class $classNamePAFinalClass extends $classNamePAClass {

}

TEXTO;
            fwrite($fh, "<?php \n" . $paData);
            fclose($fh);
        }

    }

    protected function getUsedTableName($tableName)
    {

        $usedTableName = $tableName;

        foreach ($this->table_prefix_changes as $k => $n) {
            if ($this->beginsWith($tableName, $k)) {
                $usedTableName = str_ireplace($k, $n, $tableName);
            }
        }

        return $usedTableName;
    }

    protected function getTablesFromSchema($schema)
    {
        $sql = "SELECT distinct TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE lower(TABLE_SCHEMA) = lower(?)";

        $all = $this->dbGetCol($sql, array($schema));

        return $all;
    }

    protected function getTableInfo($schema, $tableName)
    {
        $sqlInfo = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE lower(TABLE_SCHEMA) = lower(?) AND lower(TABLE_NAME) = lower(?)";

        return $this->dbGetAll($sqlInfo, array($schema, $tableName));
    }


    protected function beginsWith($string, $needle)
    {
        if ($needle) {
            if (!is_array($needle)) {
                $needle = array($needle);
            }

            foreach ($needle as $n) {
                $s = $this->substr($string, 0, $this->strlen($n));
                if ($s === (string) $n) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    protected function substr($string, $start, $length = null)
    {
        if ($length) {
            $str = mb_substr($string, $start, $length, 'UTF-8');
        } else {
            $str = mb_substr($string, $start, mb_strlen($string, 'UTF-8') + 1, 'UTF-8');
        }

        return $str;
    }

    protected function strlen($str)
    {
        return mb_strlen($str, 'utf-8');
    }

    protected function toCamelCase($str, $capitaliseFirstChar = false)
    {
        if ($capitaliseFirstChar) {
            $str[0] = strtoupper($str[0]);
        }

        return preg_replace('/_([a-z])/e', "strtoupper('\\1')", $str);
    }

    protected $connected = false;

    /**
     * @var PDO
     */
    protected $connection = null;

    protected function dbConnect()
    {
        if (!$this->db_config) {
            throw new Exception('no db_config available');
        }
        $host = $this->db_config['host'];
        $user = $this->db_config['user'];
        $pass = $this->db_config['pass'];
        $name = $this->db_config['name'];
        $type = $this->db_config['type'];

        try {
            $this->connection = new PDO("$type:host=$host;dbname=$name", $user, $pass);

            if (defined('DB_CHARSET')) {
                if (DB_CHARSET != '') {
                    $collation_query = "SET NAMES '" . DB_CHARSET . "'";
                    if (DB_COLLATE != '') {
                        $collation_query .= " COLLATE '" . DB_COLLATE . "'";
                    }
                    $this->connection->exec($collation_query);
                }
            }

        } catch (Exception $ex) {
            var_dump($ex);
            return false;
        }

        return true;
    }


    protected function dbGetAll($sql, $variables = array())
    {
        /** @var $statement PDOStatement */
        $statement = $this->dbQuery($sql, $variables);
        if (!$statement) {
            return null;
        }
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        if ($statement instanceof PDOStatement) {
            $statement->closeCursor();
        }
        $statement = null;
        unset($statement);
        return $res;
    }


    protected function dbGetCol($sql, $variables = array(), $column = 0)
    {
        /** @var $statement PDOStatement */
        $statement = $this->dbQuery($sql, $variables);

        $res = $statement->fetchAll(PDO::FETCH_COLUMN, $column);

        if ($statement instanceof PDOStatement) {
            $statement->closeCursor();
        }
        $statement = null;
        unset($statement);
        return $res;
    }

    protected function dbQuery($sql, $variables = array())
    {
        if (!$this->connection) {
            $this->dbConnect();
        }
        if (!is_array($variables)) {
            $variables = array($variables);
        }
        $variables = array_values($variables);
        $statement = $this->connection->prepare($sql);

        if ($variables) {
            $i = 1;
            foreach ($variables as $key => $var) {
                $typeVal = null;

                if (is_null($var)) {
                    $typeVal = PDO::PARAM_NULL;
                } elseif (is_bool($var)) {
                    $var     = $var ? 1 : 0;
                    $typeVal = PDO::PARAM_BOOL;
                } elseif (is_int($var)) {
                    $typeVal = PDO::PARAM_INT;
                } elseif (is_double($var)) {
//					$var = str_replace(',', '.', $var);
                }

                if ($typeVal) {
                    $statement->bindValue($i, $var, $typeVal);
                } else {
                    $statement->bindValue($i, $var);
                }
                $i++;
            }
        }
        $result = $statement->execute();

        if (!$result && $variables) {
            //fallback para usar la forma antigua en lugar del prepared statement
            $statement->closeCursor();
            $statement = null;
            unset($statement);

            $temp_sql  = $sql;
            $sql_parts = explode('?', $temp_sql);
            foreach ($variables as $key => $var) {
                switch (gettype($var)) {
                    case 'string' :
                        $var = "'" . $this->dbClean($var) . "'";
                        break;
                    case 'double' :
                        $var = str_replace(',', '.', $var);
                        break;
                    case 'boolean' :
                        $var = $var ? 1 : 0;
                        break;
                    default :
                        if ($var === null) {
                            $var = 'NULL';
                        }
                }
                $sql_parts[$key] .= $var;
            }
            $temp_sql  = implode('', $sql_parts);
            $statement = $this->connection->prepare($temp_sql);
            $result    = $statement->execute();
        }

        return $result ? $statement : false;
    }

    protected function dbClean($str)
    {
        //por compatibilidad
        $search  = array("\x00", "\n", "\r", "\\", "'", "\"", "\x1a");
        $replace = array("\\x00", "\\n", "\\r", "\\\\", "\'", "\\\"", "\\\x1a");

        return str_replace($search, $replace, $str);
    }

    public function setPrefixSuperclasses($prefix_superclasses)
    {
        $this->prefix_superclasses = $prefix_superclasses;
        return $this;
    }

    public function getPrefixSuperclasses()
    {
        return $this->prefix_superclasses;
    }

    public function useTraits($bool = true)
    {
        return $this->use_traits = (bool) $bool;
    }

    public function isUseTraits()
    {
        return $this->use_traits;
    }

}
