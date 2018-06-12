<?php
/**
 * Copyright (c) vdeApps 2018
 */

namespace vdeApps\Import;
use Doctrine\DBAL\Connection;

/**
 * Interface InterfaceImport
 * @package App\Import
 *
 */
interface ImportInterface
{
    const IMPORT_NODATA = 5;
    
    const IMPORT_FILENOTFOUND = 10;
    
    const IMPORT_READ_ERROR = 11;
    
    const IMPORT_BAD_FORMAT = 12;
    
    const IMPORT_OK = 0;
    
    /**
     * Object Base de données
     * InterfaceImport constructor.
     *
     * @param Connection $db
     */
    public function __construct($db);
    
    /**
     * Désignation des noms de colonnes
     * @param array $fields
     *
     * @return $this
     */
    public function setFields($fields = []);
    
    /**
     * Les valeurs
     * @param array $values
     *
     * @return $this
     */
    public function addRow($values = []);
    
    
    /**
     * Lire des données et les traiter par setFields et setValues
     * @return mixed
     */
    public function read();
}
