<?php
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Acl\Model\AclTable;
use Laminas\ServiceManager\ServiceLocatorInterface;

class BreadcrumbHelper extends AbstractHelper //implements ServiceLocatorAwareInterface 
{	
		
	protected $aclTable;
	private $_container;
	 
	public function __construct(AclTable $aclTable, $container)
	{
		$this->aclTable = $aclTable;
		$this->_container = $container;
	}
	
	public function __invoke($module=NULL)
	{  
		$routeMatch = $this->_container->get('Application')->getMvcEvent()->getRouteMatch();
		$routeName = $routeMatch->getMatchedRouteName();
		$arr = explode('/', $routeName);
		$routeName = $arr[0];
		$routeAction = $routeMatch->getParam('action');	
		$routeParamID = $routeMatch->getParam('id');
		$routeResource = $this->aclTable->getColumn(array('route'=>$routeName),'resource');
		$aclIndex = $this->aclTable->getColumn(array('route'=>$routeName, 'resource' => $routeResource, 'action'=>$routeAction),'tabindex');
		$breadcrumb = array();
		for($i = 1; $i <= strlen($aclIndex); $i++):
			foreach($this->aclTable->get(array('tabindex'=>substr($aclIndex,0,$i))) as $menuitem);
			array_push($breadcrumb, $menuitem);
		endfor;
		return $breadcrumb;
	} 	
}
