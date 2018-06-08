<?php
/**
 * Copyright (c) vdeApps 2018
 */

namespace vdeApps\Import;

use Doctrine\DBAL\Connection;

class ImportAbstractCsv extends ImportAbstract
{
    
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
            
            //            if ($nbTab == 15) {
            //                break;
            //            }
        }
        
        /** @var ImportInterfaceAbstract $this */
        return $this;
    }
}
