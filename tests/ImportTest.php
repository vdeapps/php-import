<?php

use PHPUnit\Framework\TestCase;
use vdeApps\Import\ImportCsv;

class ImportTest extends TestCase {
    
    protected $conn = false;
    
    public function testImport() {
        $this->createDb();
        
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
}