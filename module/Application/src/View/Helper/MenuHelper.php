<?php
/**
 * Helper -- MenuHelper
 * chophel@athang.com
 * 2020
 */
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Acl\Model\AclTable;
use Laminas\Authentication\AuthenticationService;

class MenuHelper extends AbstractHelper
{	
	private $_user;
	private $_roleId;
	private $_userId;
	
	private $_menuTitle = array();
	private $_menuHeading = array();
	private $_menuItems = array();
	private $_submenuItems = array();
	
	protected $aclTable;
	protected $authenservice ;
	 
	public function __construct(AclTable $aclTable)
	{
		$this->aclTable = $aclTable;
	}	
	
	private function getAuthenServices()
	{
		$this->authenservice = new AuthenticationService();
	   	return $this->authenservice;
	}
	
	
	public function __invoke($module=NULL)
	{  
		unset($this->_menuTitle);
		$lowestRole = '1';
		$highestRole = $this->aclTable->getHighestRole();
		$auth = $this->getAuthenServices(); 
		
		$this->_roleId = $auth->hasIdentity() ? $auth->getIdentity()->role : $lowestRole;
		$user_roles = explode(',',$this->_roleId);
		$user_roles = (sizeof($user_roles)<1) ? array($lowestRole) : $user_roles;
		
		$this->_userId = $auth->getIdentity()->id;
		$this->_user = $auth->getIdentity();
		if($module == NULL):
			foreach ($this->aclTable->getModules($user_roles,$highestRole) as $module):
			    $user_module[] = $module['module']; 
			endforeach;
		else:
			$user_module = (is_array($module))? $module:array($module);
		endif;
		//echo "<pre>";print_r($user_module); exit;
		$menus = $this->aclTable->renderMenu($this->_user, $user_module,$highestRole);
		if(sizeof($menus)>1):
			foreach ($menus as $key => $menu):
		        extract ($menu);	     
				switch (strlen($tabindex))
				{
					case 1:
						$this->_menuHeading[] = array(
								'menu'		 => $menu,
								'module'     => $module,
								'controller' => $controller,
								'route'      => $route,
								'action'	 => $action,
								'dashboard'  => $dashboard,
								'description'=> $description,
								'icon'       => $icon,
								'parent'	 => 0,
								'menuItems'  => true
						);						
						$index = sizeof($this->_menuHeading) - 1;				
						$this->_menuTitle[] = $this->_menuHeading[$index];
						unset($this->_menuItems);
					break;				        
					case 2:
						$index = sizeof($this->_menuTitle) - 1;
						$this->_menuItems[] = array(
									        'menu'		  => $menu,
											'module'      => $module,
											'controller'  => $controller,
										    'route'       => $route,
											'action'	  => $action,
											'dashboard'   => $dashboard,
											'description' => $description,
								            'icon'        => $icon,
											'parent'	  => substr($tabindex,1,1),
											'submenuItems'=> true
						);
						
						$this->_menuTitle[$index]['menuItems'] = $this->_menuItems;
					    unset($this->_submenuItems);
					break;					
				    case 3:
				        $index = sizeof($this->_menuTitle) - 1;
				        $index_menu = sizeof($this->_menuItems) - 1;	
						$this->_submenuItems[] = array(
											'menu'		  => $menu,
											'action'	  => $action,
											'description'  => $description,
						);
						$this->_menuItems[$index_menu]['submenuItems'] = $this->_submenuItems;
						$this->_menuTitle[$index]['menuItems'] = $this->_menuItems;	
						$this->_menuTitle[$index]['parent']  = substr($tabindex,1,1);
					break;
					case 4:
				        $index = sizeof($this->_menuTitle) - 1;
				        $index_menu = sizeof($this->_menuItems) - 1;	
						$this->_submenuItems[] = array(
											'menu'		  => $menu,
											'action'	  => $action,
											'description'  => $description,
						);
						$this->_menuItems[$index_menu]['submenuItems'] = $this->_submenuItems;
						$this->_menuTitle[$index]['menuItems'] = $this->_menuItems;	
						$this->_menuTitle[$index]['parent']  = substr($tabindex,1,1);
					break;
				} 
			endforeach;
			unset($this->_submenuItems);
			unset($this->_menuItems);
			unset($this->_menuHeading);
 		
			return $this->_menuTitle; 
		endif;
	} 	
}
