<?php
/**
 * Copyright (c) vdeApps 2018
 */

namespace vdeApps\Import;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use vdeApps\phpCore\Helper;

/**
 * Class AbstractImport
 * @package App\Import
 *
 *          Ecrit les données dans une table d'import
 *
 *          db: objet db
 *          tablename: nom de la table d'import
 *
 *          headerLikeFirstLine : création des colonnes comme la première ligne du tableau, sinon colonnes par lettres
 *
 *          limit: Nombre de ligne à importer à la fois 0..n
 *
 * @see     http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-representation.html
 *
 */
/*
$imp = (new ImportCsv($this->db))
                            ->fromFile($localFilename)
                            ->setLimit(10)
                            // Destination table
                            ->setTable('import_csv')
                            //Ignore la premiere ligne
                            ->setIgnoreFirstLine(false)
                            // Prend la première ligne comme entête de colonnes
                            ->setHeaderLikeFirstLine(true)
                            // Colonnes personnalisées
//                            ->setFields($customFields)
                            // Ajout de champs supplémentaires
                            ->addFields(['calc_iduser', 'calc_ident'])
                            // Ajout de n colonnes
//                            ->addFields(12)
                            // Ajout d'un plugins
                            ->addPlugins([ImportCsv::class, 'pluginsNullValue'])
                            // Ajout d'un plugins
                            ->addPlugins(function ($rowData) {
                                $rowData['calcIduser'] = 'hack';
                                return $rowData;
                            })
                            // required: Lecture/vérification
                            ->read()
                            // Exec import
                            ->import();
 */

class ImportAbstract implements ImportInterface
{
    /** @var null|Connection $db */
    private $db = null;
    
    /** @var null|Table */
    private $table = null;
    private $headerLikeFirstLine = false;
    private $limit = 0;
    /** @var null|resource */
    private $resource = null;
    private $fromfile = null;
    private $fromdata = null;
    private $dropTableBeforeImport = false;
    private $dropLinesBeforeImport = true;
    
    private $ignoreFirstLine = false;
    /** @var null|array $fields */
    private $fields = null;
    private $nbFields = null;
    
    private $addFields = null;
    /** @var null|array $values */
    private $rows = null;
    private $currentRow = 1;
    private $defaultType = Type::TEXT;
    
    /** @var null|array $plugins */
    private $plugins = null;
    
    private $charset = 'utf8_general_ci';
    
    /**
     * Object Base de données
     * InterfaceImport constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct($db)
    {
        $this->setDb($db);
    }
    
    /**
     * @param null $db
     *
     * @return ImportAbstract
     */
    public function setDb($db)
    {
        $this->db = $db;
        
        return $this;
    }
    
    /**
     * plugins: Set null values to empty value
     *
     * @param $rowData
     *
     * @return array
     */
    public static function pluginsNullValue($rowData)
    {
        return array_map(function ($v)
        {
            return ($v == '') ? null : $v;
        }, $rowData);
    }
    
    /**
     * Ajout n champs
     *
     * @param array|integer $mixed : Tableau ou nb de colonnes à ajouter
     *
     * @return ImportAbstract
     * @throws \Exception
     */
    public function addFields($mixed)
    {
        if (!is_numeric($mixed) && !is_array($mixed)) {
            throw new \Exception(__FUNCTION__ . " doit être un entier ou un tableau", self::IMPORT_BAD_FORMAT);
            return $this;
        }
        
        $this->addFields = $mixed;
        return $this;
    }
    
    public function __destruct()
    {
        $ressource = $this->getResource();
        if (is_resource($ressource)) {
            fclose($ressource);
            $this->setResource(null);
        }
    }
    
    /**
     * @return null|resource
     */
    public function getResource()
    {
        return $this->resource;
    }
    
    /*
     * Données lues
     */
    
    /**
     * Resource contenant les données
     *
     * @param null|resource $resource
     *
     * @return ImportAbstract
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        
        return $this;
    }
    
    /*
     * Entête des colonnes ['name'=>'colname', 'type'=>'LONGTEXT']
     */
    
    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }
    
    /**
     * Nombre de ligne à traiter à la fois
     *
     * @param int $limit (optional) default=0 pas de limite
     *
     * @return ImportAbstract
     */
    public function setLimit($limit = 0)
    {
        $this->limit = $limit;
        
        return $this;
    }
    
    /**
     * Lecture d'un fichier
     *
     * @param $localFilename
     *
     * @return $this
     * @throws \Exception
     */
    public function fromFile($fromfile)
    {
        if (!file_exists($fromfile)) {
            throw new \Exception("Fichier d'import non trouvé: $fromfile", self::IMPORT_FILENOTFOUND);
        }
        
        $this->fromfile = $fromfile;
        
        $resource = fopen($fromfile, "r");
        if ($resource === false) {
            throw new \Exception("Erreur de lecture de $fromfile", self::IMPORT_READ_ERROR);
        }
        
        $this->setResource($resource);
        
        return $this;
    }
    
    /**
     * Return filename
     * @return null
     */
    public function getFilename()
    {
        return $this->fromfile;
    }
    
    /**
     * Lecture depuis une chaine
     *
     * @param $string
     *
     * @return $this
     */
    public function fromData($data)
    {
        $this->fromdata = $data;
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $data);
        rewind($resource);
        
//        $resource = fopen('data://text/plain,' . $data, 'r+');
        $this->setResource($resource);
        
        return $this;
    }
    
    public function getData()
    {
        return $this->fromdata;
    }
    
    /**
     * Les valeurs
     *
     * @param array $values
     *
     * @return $this
     * @throws \Exception
     */
    public function addRow($values = [])
    {
        /*
         * Test si fields est déjà rempli
         * et si oui on regarde si le nombre de values correspond au nombre de fields
         */
        if (!is_null($this->getFields())) {
            if ($this->nbFields < count($values)) {
                throw new \Exception("Ligne " . $this->currentRow . ": Mauvais nombre de colonnes, attendu: " . $this->nbFields, self::IMPORT_BAD_FORMAT);
            }
        }
        
        $this->currentRow++;
        $this->rows[] = $values;
        
        return $this;
    }
    
    /**
     * @return array|null
     */
    public function getFields()
    {
        return $this->fields;
    }
    
    /**
     * Désignation des noms de colonnes
     *
     * @param array $fields [colname=>'', type=>'LONGTEXT')
     *
     * @return $this
     */
    public function setFields($fields = [])
    {
        $this->fields = $fields;
        $this->nbFields = count($this->fields);
        
        return $this;
    }
    
    /**
     * Effectue l'import des données
     * @return $this
     * @throws \Exception
     */
    public function import()
    {
        
        /*
         * Dispatch des données et vérification
         */
        $this->build();
    
        $this->createTable();
        
        $this->insertData();
        
        return $this;
    }
    
    /**
     * Lecture/Vérification des données values
     * Lire des données et les traiter par setFields et setValues
     * @return mixed
     * @throws \Exception
     */
    public function build()
    {
        $tbError = [];
        
        $this->currentRow = 1;
        
        /*
         * Supprime la premiere ligne
         */
        if ($this->isIgnoreFirstLine()) {
            $this->currentRow++;
            array_shift($this->rows);
        }
        
        /*
         * Si aucun champs n'a été défini
         */
        if (is_null($this->getFields())) {
            $this->currentRow++;
            
            // On prend la première ligne comme nom de colonnes ?
            if ($this->isheaderLikeFirstLine()) {
                if (is_null($this->rows) || count($this->rows) == 0) {
                    throw new \Exception("Pas de données à traiter", self::IMPORT_NODATA);
                }
                $firstLine = array_shift($this->rows);
                $this->setFields($firstLine);
            } else {
                /*
                 * Sinon on génère automatiquement les noms de colonnes
                 */
                $firstLine = $this->getRows(0);
                $nbFields = count($firstLine);
                $tbFields = [];
                for ($i = 1; $i <= $nbFields; $i++) {
                    $tbFields[] = self::number2lettre($i);
                }
                $this->setFields($tbFields);
            }
        }
        
        /**
         * Ajout des colonnes supplémentaires
         */
        if (($addFields = $this->getAddFields())) {
            $tbNewFields = [];
            if (is_array($addFields)) {
                $tbNewFields = array_merge($this->getFields(), $addFields);
            } elseif (is_numeric($addFields)) {
                // Ajout automatique de colonnes
                for ($i = $this->nbFields + 1; $i <= $this->nbFields + $addFields; $i++) {
                    $tbNewFields[] = self::number2lettre($i);
                }
                
                $tbNewFields = array_merge($this->getFields(), $tbNewFields);
            }
            $this->setFields($tbNewFields);
        }
        
        // Transformation des noms de colonnes
        $this->setFields(
            array_map(function ($val)
            {
                $val = str_replace([' ', '/'], ['_', '_'], $val);
                
                return Helper::camelCase($val);
            }, $this->getFields())
        );
        
        /*
         * Sinon on garde ceux qui sont définis et on test
         */
        $nbRows = count($this->getRows());
        for ($i = 0; $i < $nbRows; $i++, ++$this->currentRow) {
            if ($this->nbFields < count($this->getRows($i))) {
                $tbError[] = "Ligne " . $this->currentRow . ": Mauvais nombre de colonnes, attendu: " . $this->nbFields;
            }
        }
        
        if (count($tbError)) {
            throw new \Exception(implode("\n", $tbError), self::IMPORT_BAD_FORMAT);
        }
        
        /*
         * Ajout des colonnes dans l'objet TABLE
         */
        for ($i = 0; $i < $this->nbFields; $i++) {
            $this->table->addColumn($this->fields[$i], $this->defaultType, ["notnull" => false, "collation" => $this->getCharset()]);
        }
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isIgnoreFirstLine()
    {
        return $this->ignoreFirstLine;
    }
    
    /**
     * Test si on doit ignorer la première ligne
     *
     * @param bool $ignoreFirstLine (optional) default=false
     *
     * @return ImportAbstract
     */
    public function setIgnoreFirstLine($ignoreFirstLine = false)
    {
        $this->ignoreFirstLine = $ignoreFirstLine;
        
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isheaderLikeFirstLine()
    {
        return $this->headerLikeFirstLine;
    }
    
/**
     * @param bool $headerLikeFirstLine (optional) default=false
     *
     * @return ImportAbstract
     */
    public function setHeaderLikeFirstLine($headerLikeFirstLine = false)
    {
        $this->headerLikeFirstLine = $headerLikeFirstLine;
        
        return $this;
    }
    
    /**
     * Retourne toutes les lignes sous forme de tableau
     *
     * @param null|interger $indice (optional) Retourne la ligne d'indice $indice
     *
     * @return array|null
     * @throws \Exception
     */
    public function getRows($indice = null)
    {
        $result = false;
        if (is_null($indice)) {
            $result = $this->rows;
        } elseif (is_numeric($indice) and $indice >= 0) {
            if ($indice <= count($this->rows) - 1) {
                $result = $this->rows[$indice];
            }
        } else {
            throw new \Exception("Impossible de lire la première ligne", self::IMPORT_NODATA);
        }
        
        return $result;
    }
    
        /**
     * Retourne le code lettre d'après un indice
     * ex: 1->A 2->B 26->Z 27->AA ...
     *
     * @param int $icol
     *
     * @return $string lettres
     */
    public static function number2lettre($icol = 0)
    {
        $letter = '';
        
        $aCode = 96; //A - 1
        if ($icol <= 0 || $icol > 16384) {    //Trop petit ou trop grand
            $letter = '';
        } elseif ($icol > 702) {
            $letter = chr(((($icol - 1 - 26 - 676) / 676) % 676) + $aCode + 1);
            $letter .= chr(((($icol - 1 - 26) / 26) % 26) + $aCode + 1);
            $letter .= chr((($icol - 1) % 26) + $aCode);
        } elseif ($icol > 26) {
            $letter = chr(((($icol - 1 - 26) / 26) % 26) + $aCode + 1);
            $letter .= chr((($icol - 1) % 26) + $aCode + 1);
        } else {
            $letter = chr($icol + $aCode);
        }
        
        return strtolower($letter);
    } // No limit
    
    /**
     * @return null
     */
    public function getAddFields()
    {
        return $this->addFields;
    }
    
    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }
    
    /**
     * @param string $charset
     *
     * @return ImportAbstract
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
        
        return $this;
    }
    
    /**
     * Creation de la table
     * @return $this
     * @throws \Exception
     */
    private function createTable()
    {
        $tableExists = $this->getDb()->getSchemaManager()->tablesExist([$this->table->getName()]);
        
        /*
         * Drop and Create
         */
        try {
            if ($tableExists) {
                $this->getDb()->getSchemaManager()->dropAndCreateTable($this->table);
            } else {
                $this->getDb()->getSchemaManager()->createTable($this->table);
            }
        } catch (\Exception $ex) {
            throw new \Exception("Erreur de création de table: " . $ex->getMessage(), self::IMPORT_BAD_FORMAT);
        }
        
        return $this;
    }
    
    /**
     * @return Connection|null
     */
    private function getDb()
    {
        return $this->db;
    }
    
    /**
     * Insert des données
     * @return $this
     * @throws \Exception
     */
    private function insertData()
    {
        /*
         * Parcours de données à insérer
         */
        $nbRows = count($this->rows);
        $nbColumnsToInsert = count($this->getRows(0));
        
        $blankFields = ($this->nbFields - $nbColumnsToInsert) ? array_fill(0, $this->nbFields - $nbColumnsToInsert, null) : [];
        
        
        // On coupe le tableau pour ne garder que les champs nécessaires
        $fieldsToInsert = $this->getFields(); //array_slice($this->getFields(), 0, $nbColumnsToInsert);
        
        $nbPlugins = count($this->getPlugins());
        
        for ($i = 0; $i < $nbRows; $i++) {
            /*
             * Valeurs lues
             */
            $row = $this->getRows($i);
            
            /*
             * Ajout des champs supplémentaires
             */
            $row = array_merge_recursive($row, $blankFields);
            
            /*
             * Creation du tableau associatif pour les plugins
             */
            $values = array_combine($fieldsToInsert, $row);
            
            /*
             * Application des plugins
             */
            for ($ip = 0; $ip < $nbPlugins; $ip++) {
                $callable = $this->getPlugins($ip);
                if (is_callable($callable)) {
                    $values = call_user_func($callable, $values);
                }
            }
            
            try {
                $this->db->insert($this->getTable()->getName(), $values);
            } catch (\Exception $ex) {
                throw new \Exception("Erreur d'insertion dans la base, merci de vérifier l'affectation des valeurs dans les colonnes", self::IMPORT_BAD_FORMAT);
            }
        }
        
        return $this;
    }
    
    /**
     * Retourne la liste des plugins à appliquer
     *
     * @param null $indice
     *
     * @return null|array
     * @throws \Exception
     */
    public function getPlugins($indice = null)
    {
        if (is_numeric($indice)) {
            if ($indice > count($this->plugins) - 1) {
                throw new \Exception("Plugins d'indice $indice introuvable", self::IMPORT_NODATA);
            }
            
            return $this->plugins[$indice];
        }
        
        return $this->plugins;
    }
    
    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }
    
    /**
     * @param string $strTable
     *
     * @return ImportAbstract
     * @throws \Exception
     */
    public function setTable($strTable)
    {
        
        if (!is_string($strTable)) {
            throw new \Exception("Table de destination non définie", self::IMPORT_UNKNOWN_TABLE);
        }
        
        $infoTable = explode('.', $strTable);
        
        $schema = null;
        if (count($infoTable) == 2) {
            $schema = (new SchemaConfig())->setName($infoTable[0]);
            array_shift($infoTable);
        }
        
        $this->table = new Table($infoTable[0]);
        $this->table->addOption("collate", $this->getCharset());
        if ($schema) {
            $this->table->setSchemaConfig($schema);
        }
        
        return $this;
    }
    
    /**
     * Ajoute un plugins: callable
     *
     * @param callable $fct fct($row)
     *
     * @return $this
     * @throws \Exception
     */
    public function addPlugins(callable $fct)
    {
        
        if (!is_callable($fct)) {
            throw new \Exception("$fct: Le plugins n'est pas conforme");
        }
        $this->plugins[] = $fct;
        
        return $this;
    }
    
    /**
     * Lire des données et les traiter par setFields et setValues
     * @return mixed
     * @throws \Exception
     */
    public function read()
    {
        throw new \Exception("You must implement the read function", 10);
    }
}
