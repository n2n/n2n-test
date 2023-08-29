<?php

use n2n\core\cache\impl\FileN2nCache;
use n2n\core\N2N;
use n2n\core\TypeLoader;
use n2n\io\IoUtils;

ini_set('display_errors', 1);
error_reporting(E_ALL);

$pubPath = realpath(dirname(__FILE__));
$appPath = realpath($pubPath . '/../app');
$libPath = realpath($pubPath . '/../lib');
$testPath = realpath($pubPath . '/../test');
$varPath = realpath($pubPath . '/../var');

set_include_path(implode(PATH_SEPARATOR, array($appPath, $libPath, $testPath, get_include_path())));

define('N2N_STAGE', 'test');

require __DIR__ . '/../vendor/autoload.php';

TypeLoader::register(true,
		require __DIR__ . '/../vendor/composer/autoload_psr4.php',
		require __DIR__ . '/../vendor/composer/autoload_classmap.php');

N2N::initialize($pubPath, $varPath, new FileN2nCache());

$testSqlFsPath = N2N::getVarStore()->requestFileFsPath('bak', null, null, 'backup.sql', false, false, false);

$sql = IoUtils::getContents($testSqlFsPath);


$sql = preg_replace('/^(INSERT|VALUES|[\t ]*\().*/im', '', $sql);
$sql = preg_replace('/^([\t ]*\) ENGINE.*;)/im', ');', $sql);
$sql = preg_replace('/^([\t ]*(PRIMARY |FULLTEXT |UNIQUE )?KEY.*$)/im', '', $sql);
$sql = preg_replace('/^ALTER TABLE `([^`]+)` ADD UNIQUE INDEX `([^`]+)`/m', 'CREATE UNIQUE INDEX $1_$2 ON $1 ', $sql);
$sql = preg_replace('/^ALTER TABLE `([^`]+)` ADD (PRIMARY|KEY|INDEX|FULLTEXT).*/m', '', $sql);
$sql = preg_replace('/ENUM\([^)]+\)/i', 'VARCHAR(255)', $sql);
$sql = preg_replace('/INT(?:\(\d*\))? (?:UNSIGNED )?NOT NULL AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
$sql = preg_replace("/[\r\n]+/", "\n", $sql);
$sql = preg_replace("/, ?(\n^\);$)/m", "$1", $sql);
$sql = str_replace(['UNSIGNED ', 'unsigned '], '', $sql);
//file_put_contents('huii.sql', $sql);

N2N::getPdoPool()->getPdo()->exec($sql);
