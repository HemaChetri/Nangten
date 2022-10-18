<?php
namespace Acl\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin,
    Laminas\Session\Container as SessionContainer,
    Laminas\Permissions\Acl\Acl,
    Laminas\Permissions\Acl\Role\GenericRole as Role,
    Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Laminas\Db\Adapter\Adapter;    
use Laminas\Authentication\AuthenticationService;
use Interop\Container\ContainerInterface;

class AclPlugin extends AbstractPlugin 
{
    protected $sesscontainer ;
	protected $dbAdapter;
	protected $_container;
    
	public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }
	
    public function doAuthorization($e)
    {
    	$auth = new AuthenticationService();
        if ($auth->hasIdentity()) {
			$user_id = $auth->getIdentity()->id;
			$user_role = $auth->getIdentity()->role;
		}else{
			$user_id = 0;
			$user_role = 1;	
		}
		
		/** Get Database Adapter **/
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
    	
		/** Set ACL **/
        $acl = new Acl();
        $acl->deny(); /**on by default **/
        //$acl->allow(); /** this will allow every route by default so then you have to explicitly deny all routes that you want to protect.**/		
		
		# ROLES ############################################
        $roleQuery = 'SELECT * FROM `sys_roles` order by `id`';
        $statement = $this->dbAdapter->query($roleQuery);
        $results = $statement->execute(); 
		foreach($results as $result):
			$acl->addRole(new Role($result['id']));
        endforeach;
		
		$user_role = (!isset($user_role)) ? 1 : $user_role;

		$acl->addRole(new Role('SessionUserRole'), $user_role);
        # end ROLES ########################################
		
		# RESOURCES ########################################
	    $resourceQuery = 'select * from `sys_modules` order by id';
	    $resourcestatement = $this->dbAdapter->query($resourceQuery);
	    $resrouceResults = $resourcestatement->execute();  
	    foreach ($resrouceResults as $resrouceResult):
			$acl->addResource($resrouceResult['module']);
	    endforeach;
        # end RESOURCES #####################################
		
		# PERMISSIONS #######################################
	    $permissionQuery = 'select a.role as role, m.module as resource, a.controller, a.action from sys_acl a join sys_modules m on a.resource = m.id;';
	    $permissionstatement = $this->dbAdapter->query($permissionQuery);
	    $permissionResults = $permissionstatement->execute();
	    foreach ($permissionResults as $permissionResult ):
	        $controller_action = $permissionResult['controller'].":".$permissionResult['action'];
	        $acl->allow($permissionResult['role'], $permissionResult['resource'], $controller_action);
	    endforeach; 
		
		# end PERMISSIONS ###################################
		
		/** Get Currently Accessed Resources **/
        $controller = $e->getTarget();
        $controllerClass = get_class($controller);
        $moduleName = strtolower(substr($controllerClass, 0, strpos($controllerClass, '\\')));
        $routeMatch = $e->getRouteMatch();
        $actionName = strtolower($routeMatch->getParam('action', 'not-found'));	/** get the action name **/
        $controllerName = $routeMatch->getParam('controller', 'not-found');	/** get the controller name **/
		$controllerName = explode("\\", $controllerName);
        $controllerName = strtolower(array_pop($controllerName));
        $controllerName = substr($controllerName, 0, -10);
		$routeName = $routeMatch->getMatchedRouteName();
		$routeName = (strpos($routeName, '/') !== false)?substr($routeName, 0, strpos($routeName, "/")):$routeName;
		
		/** Find the Highest Role **/
		$hrQuery ="SELECT MAX(`id`) as `h_role` FROM `sys_roles`";
		$hr_stmt = $this->dbAdapter->query($hrQuery);
        $hrole = $hr_stmt->execute(); 
        foreach($hrole as $hrow);
		$highest_role = $hrow['h_role'];
		
		/** Get Count of ACL Mapped With Role & Role Mapped with Module**/
		if($highest_role==$user_role){
			$count = '1';
			$usermoduleQuery = "select module as mapped_module from sys_modules";
		}else{
			$usermoduleQuery = "select m.module as mapped_module from sys_modules m left join sys_role_module rm on m.id = rm.module where rm.role in ($user_role) or m.general = 1";
			if(substr($actionName, 0, 3)=='get'){
				$count = '1';
			}else{
				$resQuery = "SELECT `id`,`system` FROM `sys_acl` WHERE `route`='".$routeName."' AND `action`='".$actionName."' AND `resource` IN (SELECT `resource` FROM `sys_acl` WHERE `route`='".$routeName."')";
				$res_stmt = $this->dbAdapter->query($resQuery);
				$acl_resource = $res_stmt->execute();
				foreach($acl_resource as $acl_row);
				$acl_id = $acl_row['id'];
				if($acl_row['system']=='1'){
					$count = '1';
				}else{
					$count_query = "SELECT COUNT(*) as `count` FROM `sys_role_acl` WHERE `acl`=".$acl_id." AND `role` IN ($user_role)";
					$count_stmt = $this->dbAdapter->query($count_query);
					$count_result = $count_stmt->execute(); 
					foreach($count_result as $count_row);
					$count = $count_row['count'];
				}
			}
		}
		$mapped_module=array();
		$usermodulestatement = $this->dbAdapter->query($usermoduleQuery);
	    $usermoduleResults = $usermodulestatement->execute();
	    foreach ($usermoduleResults as $key=>$usermoduleResults):
	         array_push($mapped_module, $usermoduleResults['mapped_module']);
        endforeach;
		
		# CHECK ACCESS ######################################
		if($auth->hasIdentity()):
	        if ( ! in_array($moduleName, $mapped_module)){  
	        	$response = $e -> getResponse();
	        	$response -> getHeaders() -> addHeaderLine('Location', $e -> getRequest() -> getBaseUrl() . '/404');
	        	$response -> setStatusCode(404);
	        }elseif(!$acl->isAllowed('SessionUserRole', $moduleName, $controllerName.':'.$actionName)){
				if($count < 1){
					$response = $e -> getResponse(); 
					$response -> getHeaders() -> addHeaderLine('Location', $e -> getRequest() -> getBaseUrl() . '/404');          
					$response -> setStatusCode(404);            
				}
			}
        endif;
		# end CHECK ACCESS ###################################
		return $acl;
		date_default_timezone_set('Asia/Thimphu');
    }
}