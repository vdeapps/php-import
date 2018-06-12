<?php
/**
 * Copyright (c) vdeApps 2018
 */

namespace vdeApps\Import;

use Doctrine\DBAL\Connection;

class ImportCsv extends ImportAbstract
{
    private $enclosedBy = '"';
    private $delimiter = ';';
    
    /**
     * ImportCvs constructor.
     *
     * @param Connection $db
     */
    public function __construct($db)
    {
        parent::__construct($db);
        $this->setDelimiter(';')
             ->setEnclosedBy('"');
    }
    
    /**
     * Lire des données et les traiter par setFields et setValues
     * @return ImportCsv|mixed
     * @throws \Exception
     */
    public function read()
    {
        $resource = $this->getResource();
        
        $nbTab = 0;
        while (false !== ($row = fgetcsv($resource, 0, $this->getDelimiter(), $this->getEnclosedBy()))) {
            $this->addRow($row);
            $nbTab++;
        }
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }
    
    /**
     * @param string $delimiter
     *
     * @return ImportCsv
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getEnclosedBy()
    {
        return $this->enclosedBy;
    }
    
    /**
     * @param string $enclosedBy
     */
    public function setEnclosedBy($enclosedBy)
    {
        $this->enclosedBy = $enclosedBy;
    }
}
