<?php

use PHPUnit\Framework\TestCase;
use vdeApps\Import\ImportCsv;

class ImportTest extends TestCase {
    
    protected $conn = false;
    
    public function testImport() {
        $this->createDb();
        
        $this->importFile1();
    }
    
    public function createDb() {
        $user = 'vdeapps';
        $pass = 'vdeapps';
        $path = '';
        $memory = true;
        
        $config = new \Doctrine\DBAL\Configuration();
        $conn = false;
        try {
            $connectionParams = [
                'driver' => 'pdo_sqlite',
                'user'   => $user,
                'pass'   => $pass,
                'path'   => $path,
                'memory' => $memory,
            ];
            $this->conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        }
        catch (\Exception $ex) {
            $this->conn = false;
            throw new Exception("Failed to create connection", 10);
        }
        
        return $this->conn;
    }
    
    
    public function importFile1() {
        $localFilename = __DIR__.'/files/file1.csv';
        $tablename = 'file1';
        $imp = new ImportCsv($this->conn);
        
        $imp
            ->fromFile($localFilename)
            //                ->setLimit(10)
            // Destination table
            ->setTable($tablename)
            //Ignore la premiere ligne
            ->setIgnoreFirstLine(false)
            // Prend la première ligne comme entête de colonnes
            ->setHeaderLikeFirstLine(true)
            // Colonnes personnalisées
            //                            ->setFields($customFields)
            // Ajout de champs supplémentaires
            //                ->addFields(['calc_iduser', 'calc_ident'])
            // Ajout de n colonnes
            ->addFields(10)
            // Ajout d'un plugins
            ->addPlugins([ImportCsv::class, 'pluginsNullValue'])
            // Ajout d'un plugins
            //                ->addPlugins(function ($rowData) {
            //                    $rowData['calcIduser'] = 'from plugins:' . $rowData['pkChantier'];
            //                    $rowData['calcIdent'] = 'from plugins:' . $rowData['uri'];
            //
            //                    return $rowData;
            //                })
            // required: Lecture/vérification
            ->read()
            // Exec import
            ->import();
    
    
        print_r( $imp->getRows() );
        
    }
}