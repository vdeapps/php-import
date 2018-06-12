<?php
/**
 * Copyright (c) vdeApps 2018
 */

namespace vdeApps\Import;

use Doctrine\DBAL\Connection;
use vdeApps\phpCore\Helper;

class ImportCsv extends ImportAbstract
{
    private $enclosedBy = '"';
    
    /**
     * @return string
     */
    public function getEnclosedBy() {
        return $this->enclosedBy;
    }
    
    /**
     * @param string $enclosedBy
     */
    public function setEnclosedBy($enclosedBy) {
        $this->enclosedBy = $enclosedBy;
    }
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
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }
    
    /**
     * @param string $delimiter
     *
     * @return ImportAbstractCsv
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        
        return $this;
    }
    
    /**
     * Lire des données et les traiter par setFields et setValues
     * @return ImportInterfaceAbstract
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
    
        
        
        /** @var ImportInterfaceAbstract $this */
        return $this;
    }
}
