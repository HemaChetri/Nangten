<?php
/**
 * Plugin -- Permission Plugin
 * chophel@athang.com
 */
namespace Acl\Controller\Plugin;
 
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Db\Adapter\Adapter;    
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\Http\Response;
use Interop\Container\ContainerInterface;

class PermissionPlugin extends AbstractPlugin{
	protected $_container;
	
	public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }
	/**
	 * Permission Access
	 */
	public function permission(MvcEvent $e, $permission=NULL){
		$auth = new AuthenticationService();
		/** Get Database Adapter **/
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		if ($auth->hasIdentity()) {
			$login_id = $auth->getIdentity()->id;
		
			$login_role = $auth->getIdentity()->role;
			$login_role = (isset($login_role))?$login_role:0;
			$user_location = $auth->getIdentity()->location;
			$user_location_type = $auth->getIdentity()->location_type;
			
			$districtlist = array();
			switch($user_location_type):
				case 1:
					case 2:
						$districtQuery ="SELECT * FROM `adm_district` WHERE `status`='1'";
						break;
				default:
					$locationlist = array();
					$locationQuery ="SELECT `district` FROM `adm_location` WHERE `id`=".$user_location;

					$location_stmt = $this->dbAdapter->query($locationQuery);
					$locations = $location_stmt->execute(); 
					foreach($locations as $location):
						array_push($locationlist, $location['district']);
					endforeach;
					$locationlist = implode(',',$locationlist);
					$districtQuery ="SELECT * FROM `adm_district` WHERE `id` IN (".$locationlist.")";
					break;
			endswitch;
		
			$district_stmt = $this->dbAdapter->query($districtQuery);
			$districts = $district_stmt->execute(); 
			foreach($districts as $district):
				array_push($districtlist, $district['id']);
			endforeach;
			$admin_location = $districtlist;
		}else{
			$login_id = 0;
			$login_role = 1;
			$admin_location = array(0);	
		}
		
		/** Get Highest Role **/
		$hrQuery ="SELECT MAX(`id`) as `role` FROM `sys_roles`";
		$hr_stmt = $this->dbAdapter->query($hrQuery);
        $hrole = $hr_stmt->execute(); 
        foreach($hrole as $hrow);
		$highest_role = $hrow['role'];
		
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
		
		$routeParamID = $routeMatch->getParam('id');
		$routeParamID = explode('_', $routeParamID);
		$id = $routeParamID[0];
		
		$aclQuery = "SELECT * FROM `sys_acl` WHERE `route`='".$routeName."' AND `controller`='".$controllerName."' AND `action`='".$actionName."' AND `resource` IN (SELECT `id` FROM `sys_modules` WHERE `module`='".$moduleName."')";
		$acl_stmt = $this->dbAdapter->query($aclQuery);
		$acldetails = $acl_stmt->execute();
		foreach($acldetails as $arow);
		$acl_id = $arow['id'];
		if($arow['system']==0):
		$process_id = $arow['process'];
		$location_permit = '-1';
		$onlyifcreator_permit = '-1';
		$status_permit = '-1';
		if($highest_role != $login_role){
			if($process_id>0):
				$processQuery = "SELECT * FROM `sys_process` WHERE `id`='".$process_id."'";
				$process_stmt = $this->dbAdapter->query($processQuery);
				$processdetails = $process_stmt->execute();
				foreach($processdetails as $prow);
				$table_name = $prow['table_name'];
				$location_column_name = $prow['location_column'];
				$roleprocessQuery = "SELECT * FROM `sys_role_process` WHERE `process`='".$process_id."' AND `role` IN (".$login_role.")";
				$roleprocess_stmt = $this->dbAdapter->query($roleprocessQuery);
				$roleprocess = $roleprocess_stmt->execute();
				if($id != 0): /** start -- if id!=0 **/
					$idQuery = "SELECT * FROM `".$table_name."` WHERE `id`=".$id;
					$idStmt = $this->dbAdapter->query($idQuery);
					$records = $idStmt->execute();
					if(sizeof($records)>0):
						if(sizeof($roleprocess)>0):
							$location_column = array();
							$onlyifcreator_column = array();
							$permission_column = array();
							$status_column = array();
							foreach($roleprocess as $rprow):
								array_push($location_column,$rprow['location']);
								array_push($onlyifcreator_column,$rprow['only_if_creator']);
								array_push($status_column,$rprow['status']);
								array_push($permission_column,$rprow['permission_level']);
							endforeach;
							$location_permit = (in_array("0",$location_column))?'-1':$admin_location;
							$onlyifcreator_permit = (in_array("0",$onlyifcreator_column))?'-1':$login_id;
							$status_permit = (in_array("0",$status_column))?'-1':$permission_column;
							
							$column_array = array();
							if($location_permit != '-1'): array_push($column_array, $location_column_name);endif;
							if($onlyifcreator_permit != '-1'): array_push($column_array,'author');endif;
							if($status_permit != '-1'): array_push($column_array,'status');endif;
							$column = '';
							for($i=0;$i<sizeof($column_array);$i++):
								$column .= $column_array[$i];
								$column .= ($i != sizeof($column_array)-1)?", ":"";
							endfor;
							if(sizeof($column_array)>0):
								$Query = "SELECT `".$column."` FROM `".$table_name."` WHERE `id`=".$id;
								$Stmt = $this->dbAdapter->query($Query);
								$columndetails = $Stmt->execute();
								if(sizeof($columndetails)>0):
									foreach($columndetails as $crow);
									$check_array = array();
									if($location_permit != '-1'): $check = (in_array($crow[$location_column_name],$location_permit))?'1':'0'; array_push($check_array,$check);endif;
									if($onlyifcreator_permit != '-1'): $check = (in_array($crow['author'],$onlyifcreator_permit))?'1':'0'; array_push($check_array,$check);endif;
									if($status_permit != '-1'): $check = (in_array($crow['status'],$status_permit))?'1':'0'; array_push($check_array,$check);endif;
									$data = array(
										'location_permit'      => $location_permit,
										'onlyifcreator_permit' => $onlyifcreator_permit,
										'status_permit'        => $status_permit,
										'column_data'          => $crow,
									);
									if(in_array('0',$check_array)):
										$response = $e -> getResponse();
										$response -> getHeaders() -> addHeaderLine('Location', $e -> getRequest() -> getBaseUrl() . '/404');
										$response -> setStatusCode(404);
									endif;
								else:
									$permission = array(
											'location_permit' => $location_permit,
											'onlyifcreator_permit' => $onlyifcreator_permit,
											'status_permit' => $status_permit,
									);
									return $permission;
								endif;
							else:
								$permission = array(
										'location_permit' => $location_permit,
										'onlyifcreator_permit' => $onlyifcreator_permit,
										'status_permit' => $status_permit,
								);
								return $permission;
							endif;
						else:
							$permission = array(
									'location_permit' => $location_permit,
									'onlyifcreator_permit' => $onlyifcreator_permit,
									'status_permit' => $status_permit,
							);
							return $permission;
						endif;
					else:
						$response = $e -> getResponse();
						$response -> getHeaders() -> addHeaderLine('Location', $e -> getRequest() -> getBaseUrl() . '/404');
						$response -> setStatusCode(404);
					endif;
				else: /** end -- if id!=0 / start -- id == 0 **/
					if(sizeof($roleprocess)>0):
						$location_column = array();
						$onlyifcreator_column = array();
						$permission_column = array();
						$status_column = array();
						foreach($roleprocess as $rprow):
							array_push($location_column,$rprow['location']);
							array_push($onlyifcreator_column,$rprow['only_if_creator']);
							array_push($status_column,$rprow['status']);
							array_push($permission_column,$rprow['permission_level']);
						endforeach;
						$location_permit = (in_array("0",$location_column))?'-1':$admin_location;
						$onlyifcreator_permit = (in_array("0",$onlyifcreator_column))?'-1':$login_id;
						$status_permit = (in_array("0",$status_column))?'-1':$permission_column;
						$permission = array(
								'location_permit' => $location_permit,
								'onlyifcreator_permit' => $onlyifcreator_permit,
								'status_permit' => $status_permit,
						);
						return $permission;
					else:
						$permission = array(
								'location_permit' => $location_permit,
								'onlyifcreator_permit' => $onlyifcreator_permit,
								'status_permit' => $status_permit,
						);
						return $permission;
					endif;
				endif;/** end -- if id==0 **/
			else:
				$permission = array(
						'location_permit' => $location_permit,
						'onlyifcreator_permit' => $onlyifcreator_permit,
						'status_permit' => $status_permit,
				);
				return $permission;
			endif;
		}else{ /** start -- System Administrator Access **/
			if($process_id>0):
				$processQuery = "SELECT * FROM `sys_process` WHERE `id`='".$process_id."'";
				$process_stmt = $this->dbAdapter->query($processQuery);
				$processdetails = $process_stmt->execute();
				foreach($processdetails as $prow);
				$table_name = $prow['table_name'];
				if($id != 0):
					$idQuery = "SELECT * FROM `".$table_name."` WHERE `id`=".$id;
					$idStmt = $this->dbAdapter->query($idQuery);
					$records = $idStmt->execute();
					if(sizeof($records)<=0):
						$response = $e -> getResponse();
						$response -> getHeaders() -> addHeaderLine('Location', $e -> getRequest() -> getBaseUrl() . '/404');
						$response -> setStatusCode(404);
					endif;
				else:
					$permission = array(
							'location_permit' => $location_permit,
							'onlyifcreator_permit' => $onlyifcreator_permit,
							'status_permit' => $status_permit,
					);
					return $permission;
				endif;
			else:
				$permission = array(
						'location_permit' => $location_permit,
						'onlyifcreator_permit' => $onlyifcreator_permit,
						'status_permit' => $status_permit,
				);
				return $permission;
			endif;
		} /** end -- System Administrator Access **/
		endif;
	}
	/**
	 * Permitted Location Type
	 */
	public function getlocationtype(){
		$auth = new AuthenticationService();
		if ($auth->hasIdentity()) {
			$user_id = $auth->getIdentity()->id;
			$login_role = $auth->getIdentity()->role;
			$login_role = (isset($login_role))?$login_role:0;
			$user_location = $auth->getIdentity()->location;
			$user_location_type = $auth->getIdentity()->location_type;
		}else{
			$user_id = 0;
			$login_role = 1;
			$user_location = 0;
			$user_location_type = 0;
		}
		//get Database Adapter
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		$locationtypelist = array();
		switch($login_role):
			case 100:
				case 99:
					case 9:
						$locationQuery ="SELECT * FROM `adm_location_type` WHERE `status`='1'";
						break;
			default:
				$locationQuery ="SELECT * FROM `adm_location_type` WHERE `id`=".$user_location_type;
				break;
		endswitch;
		$locationtype_stmt = $this->dbAdapter->query($locationQuery);
		$locationtypes = $locationtype_stmt->execute(); 
		foreach($locationtypes as $locationtype):
			array_push($locationtypelist, $locationtype);
		endforeach;
		return $locationtypelist;
	}
	/**
	 * Permitted Location
	 */
	public function getlocation($location_type_data = 0){
		$auth = new AuthenticationService();
		if ($auth->hasIdentity()) {
			$user_id = $auth->getIdentity()->id;
			$user_location = $auth->getIdentity()->location;
			$user_location_type = $auth->getIdentity()->location_type;
		}else{
			$user_id = 0;
			$user_location = 0;
			$user_location_type = 0;
		}
		//get Database Adapter
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		
		$locationlist = array();
		if($location_type_data != 0):
			$locationQuery = "SELECT * FROM `adm_location` WHERE  `location_type` = ".$location_type_data;
		else:
			switch($user_location_type):
				case 1:
					case 2:
						$locationQuery ="SELECT * FROM `adm_location` WHERE `status`='1'";
						break;
				default:
					$locationQuery ="SELECT * FROM `adm_location` WHERE `id`=".$user_location;
					break;
			endswitch;
		endif;
		$location_stmt = $this->dbAdapter->query($locationQuery);
		$locations = $location_stmt->execute(); 
		foreach($locations as $location):
			array_push($locationlist, $location);
		endforeach;
        return $locationlist;
	}
	/**
	 * Permitted District
	 */
	public function getdistrict($district_data = 0){
		$auth = new AuthenticationService();
    	if ($auth->hasIdentity()) {
			$user_id = $auth->getIdentity()->id;
			$user_location = $auth->getIdentity()->location;
			$user_location_type = $auth->getIdentity()->location_type;
		}else{
			$user_id = 0;
			$user_location = 0;
			$user_location_type = 0;
		}
		//get Database Adapter
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		
		$districtlist = array();
		if($district_data != 0):
			$districtQuery = "SELECT * FROM `adm_district` WHERE  `id` = ".$district_data;
		else:
			switch($user_location_type):
				case 1:
					case 2:
						$districtQuery ="SELECT * FROM `adm_district` WHERE `status`='1'";
						break;
				default:
					$locationlist = array();
					$locationQuery ="SELECT `district` FROM `adm_location` WHERE `id`=".$user_location;

					$location_stmt = $this->dbAdapter->query($locationQuery);
					$locations = $location_stmt->execute(); 
					foreach($locations as $location):
						array_push($locationlist, $location['district']);
					endforeach;
					$locationlist = implode(',',$locationlist);
					$districtQuery ="SELECT * FROM `adm_district` WHERE `id` IN (".$locationlist.")";
					break;
			endswitch;
		endif;
		$district_stmt = $this->dbAdapter->query($districtQuery);
		$districts = $district_stmt->execute(); 
		foreach($districts as $district):
			array_push($districtlist, $district);
		endforeach;
        return $districtlist;
	}
	/**
	 * Permitted Block
	 */
	public function getblock($block_data = 0){
		$auth = new AuthenticationService();
		if ($auth->hasIdentity()) {
			$user_id = $auth->getIdentity()->id;
			$user_location = $auth->getIdentity()->location;
			$user_location_type = $auth->getIdentity()->location_type;
		}else{
			$user_id = 0;
			$user_location = 0;
			$user_location_type = 0;
		}
		//get Database Adapter
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		
		$blocklist = array();
		if($block_data != 0):
			$blockQuery = "SELECT * FROM `adm_block` WHERE  `id` = ".$block_data;
		else:
			switch($user_location_type):
				case 1:
					case 2:
						$blockQuery ="SELECT * FROM `adm_block` WHERE `status`='1'";
						break;
				default:
					$locationlist = array();
					$locationQuery ="SELECT `district` FROM `adm_location` WHERE `id`=".$user_location;

					$location_stmt = $this->dbAdapter->query($locationQuery);
					$locations = $location_stmt->execute(); 
					foreach($locations as $location):
						array_push($locationlist, $location['district']);
					endforeach;
					$locationlist = implode(',',$locationlist);
					$blockQuery ="SELECT * FROM `adm_block` WHERE `status`='1' AND `DzongkhagId` IN (".$locationlist.")";
					break;
			endswitch;
		endif;
		$block_stmt = $this->dbAdapter->query($blockQuery);
		$blocks = $block_stmt->execute(); 
		foreach($blocks as $block):
			array_push($blocklist, $block);
		endforeach;
        return $blocklist;
	}
	/**
	 * Permitted Role
	 */
	public function getrole(){
		$auth = new AuthenticationService();
		if ($auth->hasIdentity()) {
			$user_id = $auth->getIdentity()->id;
    		$user_role = $auth->getIdentity()->role;
		}else{
			$user_id = 0;
    		$user_role = 1;
		}
		//get Database Adapter
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		$rolelist = array();
		switch($user_role):
			case 100:
				$roleQuery ="SELECT * FROM `sys_roles` WHERE `id` NOT IN ('1')";
				break;
			case 99:
				$roleQuery ="SELECT * FROM `sys_roles` WHERE `id` NOT IN ('100','1')";
				break;
			case 9:
				$roleQuery ="SELECT * FROM `sys_roles` WHERE `id` NOT IN ('100','99','1')";
				break;
			default:
				$roleQuery ="SELECT * FROM `sys_roles` WHERE `id` NOT IN ('1') AND `id` IN (".$user_role.")";
				break;
		endswitch;
		$role_stmt = $this->dbAdapter->query($roleQuery);
		$roles = $role_stmt->execute(); 
		foreach($roles as $role):
			array_push($rolelist, $role);
		endforeach;
		return $rolelist;
	}
}