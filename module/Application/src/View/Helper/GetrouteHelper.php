<?php
namespace Application\View\Helper;

use Laminas\ServiceManager\ServiceLocatorInterface;
 
use Laminas\ServiceManager\ServiceLocatorAwareInterface;
use Laminas\View\Helper\AbstractHelper;
 
use Interop\Container\ContainerInterface;

class GetrouteHelper extends AbstractHelper //implements ServiceLocatorAwareInterface 
{ 
	 private $serviceLocator;
	 private $_container;
	 
	public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }

	 /**
	 * Set service locator
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 */
	// public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
	//   $this->serviceLocator = $serviceLocator;
	// }
	 
	 /**
	 * Get service locator
	 *
	 * @return ServiceLocatorInterface
	 */
	// public function getServiceLocator() {
	//   return $this->serviceLocator;
	// }
	 

	 public function __invoke() {
	 	$routeMatch = $this->_container->get('Application')->getMvcEvent()->getRouteMatch();
		
	 	return $routeMatch;
	 }
}
