<?php

/**
 * Local Configuration Override
 *
 * This configuration override file is for overriding environment-specific and
 * security-sensitive configuration information. Copy this file without the
 * .dist extension at the end and populate values as needed.
 *
 * NOTE: This file is ignored from Git by default with the .gitignore included
 * in laminas-mvc-skeleton. This is a good practice, as it prevents sensitive
 * credentials from accidentally being committed into version control.
 */
use Laminas\DB\Adapter;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Doctirine\DBAL\Driver\PDOMysqlDriver as PDOMysqlDriver;

return [
    'db'=>[
		'driver'=>'Pdo',
        'dsn'=>'mysql:dbname=doc_nangtenv2.0;hostname=127.0.0.1',
		'driver-options'=>[
			PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES\'UTF8\'',
		],
        'username'=> 'root',
        'password' =>'',
	],
];