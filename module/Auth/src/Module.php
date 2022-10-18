<?php
/*
  * chophel@athang.com @2021
 */
namespace Auth;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\SessionManager;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Session\SaveHandler\DbTableGatewayOptions;
use Laminas\Session\SaveHandler\DbTableGateway;
use Laminas\Db\Adapter\Adapter;

class Module
{
    const VERSION = '3.0.3-dev';
    private $auth;

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e)
    {
        //$this->bootstrapSession($mvcEvent);
        $config = $e->getApplication()->getServiceManager()->get('config');
        // get the database section
        $dbAdapter = new Adapter($config['db']);
    
        // crate the TableGateway object specifying the table name
        $sessionTableGateway = new TableGateway('session', $dbAdapter);
        // create your saveHandler object
        $saveHandler = new DbTableGateway($sessionTableGateway, new DbTableGatewayOptions());
				
        // pass the saveHandler to the sessionManager and start the session
        $sessionManager = $e->getApplication()->getServiceManager()->get(SessionManager::class);;
        
        $sessionManager->setSaveHandler($saveHandler);
    }
}
