<?php

use PHPUnit\Framework\TestCase;
use vdeApps\Import\ImportCsv;

class ImportTest extends TestCase {
    
    protected $conn = false;
    
    public function testImport() {
        $this->createDb();
        
//        $this->importFromFile();
        
        $this->importFromData();
    }
    
    public function createDb() {
        $user = 'vdeapps';
        $pass = 'vdeapps';
        $path = __DIR__.'/files/database.db';
        $memory = false;
        
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
    
    
    public function importFromFile() {
        $localFilename = __DIR__ . '/files/file1.csv';
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
        
        $this->assertEquals(10, count($imp->getRows()));
    }
    
    public function importFromData() {
        
        $data = "col1;col2;col3;col4;col5;col6;col7;col8;col9;col10;col11;col12;col13;col14;col15;col16;col17;col18;col19;col20
rowA1;rowA2;rowA3;rowA4;rowA5;rowA6;rowA7;rowA8;rowA9;rowA10;rowA11;rowA12;rowA13;rowA14;rowA15;rowA16;rowA17;rowA18;rowA19;rowA20
rowB2;rowB3;rowB4;rowB5;rowB6;rowB7;rowB8;rowB9;rowB10;rowB11;rowB12;rowB13;rowB14;rowB15;rowB16;rowB17;rowB18;rowB19;rowB20;rowB21
";
        
        $tablename = 'importFromData';
        $imp = new ImportCsv($this->conn);
        
        $imp
            ->fromData($data)
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
            ->addFields(['myfield1', 'myfield2'])
            // Ajout de n colonnes
//            ->addFields(10)
            // Ajout d'un plugins
            ->addPlugins([ImportCsv::class, 'pluginsNullValue'])
            // Ajout d'un plugins
                            ->addPlugins(function ($rowData) {
                                $rowData['myfield1'] = 'from plugins:myField1';
                                $rowData['myfield2'] = 'from plugins:myField2';
    
                                $rowData['col1'] = 'rewrite col1';
            
                                return $rowData;
                            })
            // required: Lecture/vérification
            ->read()
            // Exec import
            ->import();
    
    
        $rows = $imp->getRows();
        
        $this->assertEquals(2, count($rows));
    
        $cols = $imp->getRows(0);
    
        $this->assertEquals(20, count($cols));
        
        
    }
}