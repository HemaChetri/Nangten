<?php
namespace Acl;

use Laminas\ModuleManager\ModuleManager;/*** added for module specific layouts.*/
/*** added for Acl  */
use Laminas\Mvc\MvcEvent,
    Laminas\ModuleManager\Feature\AutoloaderProviderInterface,
    Laminas\ModuleManager\Feature\ConfigProviderInterface;
/*** end: added for Acl */
use Laminas\View\Model\ViewModel;

class Module 
{			
	/*** added for Acl  */
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $eventManager->attach('route', array($this, 'loadConfiguration'), 2);
        
        /*** Custom Error Reporting */
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function(MvcEvent $e){
			$error = $e->getError();			
			if (empty($error) || $error != "UNAUTHORIZED") {
				return;
			}
			$result = $e->getResult();			
			if ($result instanceof StdResponse) {
				return;
			}
			$baseModel = new ViewModel();
			$baseModel->setTemplate('layout/layout');
			$model = new ViewModel();
			$model->setTemplate('error/404');
			$baseModel->addChild($model);
			$baseModel->setTerminal(true);
			$e->setViewModel($baseModel);
			$response = $e->getResponse();
			$response->setStatusCode(404);
			$e->setResponse($response);
			$e->setResult($base_model);
			
			return false;
		}, -999);
    }
	
    public function loadConfiguration(MvcEvent $e)
    {
		$application   = $e->getApplication();
		$sm            = $application->getServiceManager();
		$sharedManager = $application->getEventManager()->getSharedManager();
		
		$router = $sm->get('router');
		$request = $sm->get('request');
		
		$matchedRoute = $router->match($request);
		if (null !== $matchedRoute) { 
           $sharedManager->attach('Laminas\Mvc\Controller\AbstractActionController','dispatch', 
            function($e) use ($sm) {
				$sm->get('ControllerPluginManager')->get('AclPlugin')->doAuthorization($e); //pass to the plugin...    
	       },2
           );
        }
    }
	/*** end: added for Acl */
	
	public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}