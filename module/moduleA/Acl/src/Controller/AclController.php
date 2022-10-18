<?php
namespace Acl\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Interop\Container\ContainerInterface;
use Laminas\Mvc\MvcEvent;
use Acl\Model as Acl;

class AclController extends AbstractActionController
{
	private $_container;
	protected $_table; 		// database table 
    protected $_user; 		// user detail
    protected $_login_id; 	// logined user id
    protected $_login_role; // logined user role
    protected $_author; 	// logined user id
    protected $_created; 	// current date to be used as created dated
    protected $_modified; 	// current date to be used as modified date
    protected $_config; 	// configuration details
    protected $_dir; 		// default file directory
    protected $_id; 		// route parameter id, usally used by crude
    protected $_auth; 		// checking authentication
    protected $_highest_role;// highest user role
    protected $_lowest_role;// loweset user role
    
	public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }
	/**
	 * Laminas Default TableGateway
	 * Table name as the parameter
	 * returns obj
	 */
	public function getDefaultTable($table)
	{
		$this->_table = new TableGateway($table, $this->_container->get('Laminas\Db\Adapter\Adapter'));
		return $this->_table;
	}
	/**
	 * User defined Model
	 * Table name as the parameter
	 * returns obj
	 */
	  public function getDefinedTable($table)
    {
        $definedTable = $this->_container->get($table);
        return $definedTable;
    }
	
	/**
	* initial set up
	* general variables are defined here
	*/
	public function init()
	{
		$this->_auth = new AuthenticationService;
		if(!$this->_auth->hasIdentity()):
			$this->flashMessenger()->addMessage('error^ You dont have right to access this page!');
   	        $this->redirect()->toRoute('auth', array('action' => 'login'));
		endif;
		if(!isset($this->_config)) {
			$this->_config = $this->_container->get('Config');
		}
		if(!isset($this->_user)) {
		    $this->_user = $this->identity();
		}
		if(!isset($this->_login_id)){
			$this->_login_id = $this->_user->id;  
		}
		if(!isset($this->_login_role)){
			$this->_login_role = $this->_user->role;  
		}
		if(!isset($this->_highest_role)){
			$this->_highest_role = $this->getDefinedTable(Acl\RolesTable::class)->getMax($column='id');  
		}
		if(!isset($this->_lowest_role)){
			$this->_lowest_role = $this->getDefinedTable(Acl\RolesTable::class)->getMin($column='id'); 
		}
		if(!isset($this->_author)){
			$this->_author = $this->_user->id;  
		}
		
		$this->_id = $this->params()->fromRoute('id');
		
		$this->_created = date('Y-m-d H:i:s');
		$this->_modified = date('Y-m-d H:i:s');
		
		$this->_safedataObj = $this->safedata();
		$this->_connection = $this->_container->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
		
		//$this->_permissionObj =  $this->PermissionPlugin();
		//$this->_permissionObj->permission($this->getEvent());	
	}
	/**
	 * index Action of Acl Controller
	 */
    public function indexAction()
    {  
    	$this->init(); 
		
        return new ViewModel([
			'title' => 'Acl Menu',
		]);
	}
	/**
	 * role list Action
	 */
    public function rolelistAction()
    {  
    	$this->init(); 
		$roleTable = $this->getDefinedTable(Acl\RolesTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($roleTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'     => 'Role Management',
			'paginator' => $paginator,
			'page'      => $page,
		)); 
	}
	/**
	 * Add role list Action
	 */
    public function addrolelistAction()
    {  
    	$this->init(); 
		$page = $this->_id;
		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			
			$secondmax_id = $this->getDefinedTable(Acl\RolesTable::class)->getMaxLimitOffset('id');
			$next_id = $secondmax_id + 1;
			$max_id = $this->getDefinedTable(Acl\RolesTable::class)->getMax('id');
			if($next_id<$max_id):
				$data = array(
						'id'          => $next_id, 
						'role'	      => $form['role'],
						'description' => $form['description'],
						'author'      => $this->_author,
						'created'     => $this->_created,
						'modified'    => $this->_modified
				);
				$data = $this->_safedataObj->rteSafe($data);
				$this->_connection->beginTransaction();
				$result = $this->getDefinedTable(Acl\RolesTable::class)->save($data,'insert');
				if($result):
					$this->_connection->commit();
					$this->flashMessenger()->addMessage("success^ Successfully added new role.");
				else:
					$this->_connection->rollback();
					$this->flashMessenger()->addMessage("error^ Failed to add new role.");
				endif;
			else:
				$this->flashMessenger()->addMessage("warning^ Maximum limit of roles.");
			endif;
			return $this->redirect()->toRoute('acl/paginator',array('action'=>'rolelist','page'=>$this->_id, 'id'=>'0','role'=>'0','task'=>'0'));
		endif;	
		$ViewModel = new ViewModel(array(
				'title'        => 'Add Role',
				'page'         => $page,
				'highest_role' => $this->_highest_role,
		));
		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * Edit role list Action
	 */
    public function editrolelistAction()
    {  
    	$this->init(); 
		$id = $this->_id;
		$array_id = explode("_", $id);
		$role_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			$data = array(
					'id'          => $form['role_id'], 
					'role'	      => $form['role'],
					'description' => $form['description'],
					'author'      => $this->_author,
					'created'     => $this->_created,
					'modified'    => $this->_modified
			);
			$data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Acl\RolesTable::class)->save($data,'update');
			if($result):
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^ Successfully edited role.");
			else:
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^ Failed to edit role.");
			endif;
			return $this->redirect()->toRoute('acl/paginator',array('action'=>'rolelist','page'=>$this->_id, 'id'=>'0','role'=>'0','task'=>'0'));
		endif;
        $ViewModel = new ViewModel(array(
				'title'        => 'Edit Role',
				'page'         => $page,
				'role_id'      => $role_id,
				'highest_role' => $this->_highest_role,
				'rolesObj'     => $this->getDefinedTable(Acl\RolesTable::class),
		));
		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
     *  Map Roles to Modules
	 */
	public function mapmodulesAction()
	{
		$this->init();	
		
		$id = $this->_id;
		$array_id = explode("_", $id);
		$role_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		$role = $role_id;
		$module = $this->params()->fromRoute('role');
		$task = $this->params()->fromRoute('task');

		if(strlen($task) > 0 && ($role > 0 && $module > 0))
		{  
			$data = array(
				        'role'    => $role,
					    'module'  => $module,
					    'author'  => $this->_author,
					    'created' => $this->_created,
					    'modified'=> $this->_modified
			);
			
			switch ($task){
				case 'map'  : $this->getDefinedTable(Acl\RolemoduleTable::class)->save($data); break;
				case 'unmap': $this->getDefinedTable(Acl\RolemoduleTable::class)->remove($data); break;
			} 
			return $this->redirect()->toRoute('acl/paginator',array('action'=>'mapmodules','page'=>$page,'id'=>'0','role'=>'0','task'=>'0'));	
		}
		
        $roles = $this->getDefinedTable(Acl\RolesTable::class)->getAllExcept($this->_highest_role);
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($roles));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
		return new ViewModel(array(
			'title'         => 'Role Module Privileges',
			'roles'         => $paginator,
			'page'          => $page,
			'highest_role'  => $this->_highest_role,
			'rolemoduleObj' => $this->getDefinedTable(Acl\RolemoduleTable::class),
			'moduleObj'     => $this->getDefinedTable(Acl\ModuleTable::class),	
		));
	}	
	/**
	 * Role Permission Manager	
	 */
	public function permissionAction()
	{
		$this->init();
		if($this->getRequest()->isPost())
		{
			$form = $this->getRequest()->getpost();
			$data_module = $form['data_module']; 
			$data_process = $form['data_process'];
			$data_role = $form['data_role'];
			$data_action = $form['data_action'];
			$data_check = $form['data_check'];
			$acls = $this->getDefinedTable(Acl\AclTable::class)->get(array('resource'=>$data_module,'process'=>$data_process,'process_action'=>$data_action));
			if($data_check):
				foreach($acls as $acl):
					$data = array(
						'acl'     => $acl['id'],
						'role'    => $data_role,
						'author'  => $this->_author,
						'created' => $this->_created,
						'modified'=> $this->_modified
					);
					$result = $this->getDefinedTable(Acl\RoleaclTable::class)->save($data);
				endforeach;
			else:
				foreach($acls as $acl):
					$roleacl_id = $this->getDefinedTable(Acl\RoleaclTable::class)->getColumn(array('acl'=>$acl['id'],'role'=>$data_role),'id');
					$result = $this->getDefinedTable(Acl\RoleaclTable::class)->remove($roleacl_id);
				endforeach;
			endif;
			echo json_encode(array(
				'data_module' => $data_module,
				'data_process'=> $data_process,
				'data_role'=> $data_role,
				'data_action'=> $data_action,
				'data_check'=> $data_check,
				'result' => $result,
			));
			exit;
		}else{
			$id = $this->_id;
			$array_id = explode("_", $id);
			if(sizeof($array_id)==3):
				$resource = isset($array_id[0])?$array_id[0]:'-1';
				$process = isset($array_id[1])?$array_id[1]:'-1';
				$role = isset($array_id[2])?$array_id[2]:'-1';
			else:
				$resource = '-1';
				$process = '-1';
				$role = '-1';
			endif;
		}
		$data = array(
			'resource' => $resource,
			'process'  => $process,
			'role'     => $role,
		);
		return new ViewModel(array(
				'title'          => 'Role Permission Manager',
				'data'           => $data,
				'modules'        => $this->getDefinedTable(Acl\ModuleTable::class)->get(array('general'=>'0')),
				'processObj'     => $this->getDefinedTable(Acl\ProcessTable::class),
				'roleObj'        => $this->getDefinedTable(Acl\RolesTable::class),
				'roleaclObj'     => $this->getDefinedTable(Acl\RoleaclTable::class),
				'pactionObj'     => $this->getDefinedTable(Acl\ProcessActionTable::class),
				'aclObj'         => $this->getDefinedTable(Acl\AclTable::class),
				'roleprocessObj' => $this->getDefinedTable(Acl\RoleprocessTable::class),
		));
	}
	/**
	 * create permission Action
	 */
	public function createpermissionAction()
    {  
    	$this->init(); 

		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			$action = '1';
			
			if($form['module_id']!='-1' && $form['process_id']!='-1' && $form['role_id']!='-1'):
				$check1 = $this->getDefinedTable(Acl\AclTable::class)->isPresent(array('resource'=>$form['module_id'],'process'=>$form['process_id'],'role'=>$form['role_id']));
				$check2 = $this->getDefinedTable(Acl\RoleaclTable::class)->isPresent(array('a.resource'=>$form['module_id'],'a.process'=>$form['process_id'],'ra.role'=>$form['role_id']));
				if($check1==0 && $check2==0):
					$role_module = $this->getDefinedTable(Acl\RolemoduleTable::class)->get(array('module'=>$form['module_id'],'role'=>$form['role_id']));
					if(sizeof($role_module)==1):
						$acls = $this->getDefinedTable(Acl\AclTable::class)->get(array('resource'=>$form['module_id'],'process'=>$form['process_id'],'process_action'=>$action));
						if(sizeof($acls)>=1):
							$this->_connection->beginTransaction();
							foreach($acls as $acl):
								$data = array(
									'acl'     => $acl['id'],
									'role'    => $form['role_id'],
									'author'  => $this->_author,
									'created' => $this->_created,
									'modified'=> $this->_modified
								);
								$data = $this->_safedataObj->rteSafe($data);
								$result = $this->getDefinedTable(Acl\RoleaclTable::class)->save($data);
							endforeach;
							if($result):
								$this->_connection->commit();
								$this->flashMessenger()->addMessage("success^Successfully created new permission.");
							else:
								$this->_connection->rollback();
								$this->flashMessenger()->addMessage("error^Failed to create new permission.");
							endif;
						else:
							$this->flashMessenger()->addMessage("warning^No Read action while creating new permission.");
						endif;
					else:
						$this->flashMessenger()->addMessage("warning^Please map role with module in Role Module Privileges before creating new permissions.");
					endif;
				else:
					$this->flashMessenger()->addMessage("warning^Permission already exists.");
				endif;
			else:
				$this->flashMessenger()->addMessage("warning^Missing form inputs while creating new permission.");
			endif;
			return $this->redirect()->toRoute('acl',array('action'=>'permission','id'=>$this->_id));
		endif;	
		$id = $this->_id;
		$array_id = explode("_", $id);
		if(sizeof($array_id)==3):
			$resource = isset($array_id[0])?$array_id[0]:'-1';
			$process = isset($array_id[1])?$array_id[1]:'-1';
			$role = isset($array_id[2])?$array_id[2]:'-1';
		else:
			$resource = '-1';
			$process = '-1';
			$role = '-1';
		endif;
		$data = array(
			'resource' => $resource,
			'process'  => $process,
			'role'     => $role,
		);
		$ViewModel = new ViewModel(array(
				'title'      => 'Create Permission',
				'id'         => $this->_id,
				'data'       => $data,
				'modules'    => $this->getDefinedTable(Acl\ModuleTable::class)->get(array('general'=>'0')),
				'processObj' => $this->getDefinedTable(Acl\ProcessTable::class),
				'roleObj'    => $this->getDefinedTable(Acl\RolesTable::class),
				'statusObj'  => $this->getDefinedTable(Acl\StatusTable::class),
		));
		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * update access Action
	 */
	public function updateaccessAction()
    {  
    	$this->init(); 
		$access_data = $this->_id;
		$array_id = explode("_", $access_data);
		$resource = $array_id[0];
		$process = $array_id[1];
		$role = $array_id[2];
		$action = $array_id[3];
		$selected_process = $array_id[4];
		$selected_role = $array_id[5];
		$selected_data = $resource."_".$selected_process."_".$selected_role;
		
		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			
			if($form['process_id']!='-1' && $form['role_id']!='-1'):
				$check1 = $this->getDefinedTable(Acl\RoleprocessTable::class)->isPresent(array('process'=>$form['process_id'],'role'=>$form['role_id']));
				$processdtls = $this->getDefinedTable(Acl\ProcessTable::class)->get($form['process_id']);
				foreach($processdtls as $processdtl);
				$location = ($processdtl['location']=='1')?$form['location']:'0';
				$only_if_creator = ($processdtl['only_if_creator']=='1')?$form['only_if_creator']:'0';
				$status = ($processdtl['status']=='1')?$form['status']:'0';
				$permission_level = ($form['status']=='1')?implode(',',$form['permission_level']):'';
				if($check1==1):
					$role_process_id = $this->getDefinedTable(Acl\RoleprocessTable::class)->getColumn(array('process'=>$form['process_id'],'role'=>$form['role_id']),'id');
					$data = array(
						'id'               => $role_process_id,
						'role'             => $form['role_id'],
						'process'          => $form['process_id'],
						'permission_level' => $permission_level,
						'location'         => $location,
						'only_if_creator'  => $only_if_creator,
						'status'           => $status,
						'author'           => $this->_author,
						'modified'         => $this->_modified
					);
				else:
					$data = array(
						'role'             => $form['role_id'],
						'process'          => $form['process_id'],
						'permission_level' => $permission_level,
						'location'         => $location,
						'only_if_creator'  => $only_if_creator,
						'status'           => $status,
						'author'           => $this->_author,
						'modified'         => $this->_modified
					);
				endif;
				$data = $this->_safedataObj->rteSafe($data);
				$this->_connection->beginTransaction();
				$result = $this->getDefinedTable(Acl\RoleprocessTable::class)->save($data);
				if($result):
					$this->_connection->commit();
					$this->flashMessenger()->addMessage("success^Successfully updated permission access.");
				else:
					$this->_connection->rollback();
					$this->flashMessenger()->addMessage("error^Failed to update permission access.");
				endif;
			else:
				$this->flashMessenger()->addMessage("warning^Missing form inputs while transfering permission.");
			endif;
			return $this->redirect()->toRoute('acl',array('action'=>'permission','id'=>$this->_id));
		endif;
		$data = array(
			'resource' => $resource,
			'process'  => $process,
			'role'     => $role,
			'selected_data' => $selected_data,
		);
		$ViewModel = new ViewModel(array(
				'title'          => 'Update Access',
				'data'           => $data,
				'processObj'     => $this->getDefinedTable(Acl\ProcessTable::class),
				'roleObj'        => $this->getDefinedTable(Acl\RolesTable::class),
				'roleprocessObj' => $this->getDefinedTable(Acl\RoleprocessTable::class),
				'statusObj'      => $this->getDefinedTable(Acl\StatusTable::class),
		));
		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * transfer permission Action
	 */
	public function transferpermissionAction()
    {  
    	$this->init(); 
		
		$transfer_data = $this->_id;
		$array_id = explode("_", $transfer_data);
		$resource = $array_id[0];
		$process = $array_id[1];
		$role = $array_id[2];
		$action = $array_id[3];
		$selected_process = $array_id[4];
		$selected_role = $array_id[5];
		$selected_data = $resource."_".$selected_process."_".$selected_role;
		
		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			
			if($form['module_id']!='-1' && $form['process_id']!='-1' && $form['role_id']!='-1' && $form['transfer_to_role']!='-1'):
				$check1 = $this->getDefinedTable(Acl\AclTable::class)->isPresent(array('resource'=>$form['module_id'],'process'=>$form['process_id'],'role'=>$form['transfer_to_role']));
				$check2 = $this->getDefinedTable(Acl\RoleaclTable::class)->isPresent(array('a.resource'=>$form['module_id'],'a.process'=>$form['process_id'],'ra.role'=>$form['transfer_to_role']));
				if($check1==0 && $check2==0):
					$role_module = $this->getDefinedTable(Acl\RolemoduleTable::class)->get(array('module'=>$form['module_id'],'role'=>$form['transfer_to_role']));
					if(sizeof($role_module)==1):
						$acls = $this->getDefinedTable(Acl\AclTable::class)->get(array('resource'=>$form['module_id'],'process'=>$form['process_id'],'role'=>$form['role_id']));
						if(sizeof($acls)>=1):
							$this->_connection->beginTransaction();
							foreach($acls as $acl):
								$data = array(
									'id'      => $acl['id'],
									'role'    => $form['transfer_to_role'],
									'author'  => $this->_author,
									'modified'=> $this->_modified
								);
								$result = $this->getDefinedTable(Acl\AclTable::class)->save($data);
							endforeach;
							if($result):
								$check3 = $this->getDefinedTable(Acl\RoleprocessTable::class)->isPresent(array('process'=>$form['process_id'],'role'=>$form['role_id']));
								if($check3==1):
									$role_process_id = $this->getDefinedTable(Acl\RoleprocessTable::class)->getColumn(array('process'=>$form['process_id'],'role'=>$form['role_id']),'id');
									$role_process_data = array(
											'id'      => $role_process_id,
											'role'    => $form['transfer_to_role'],
											'author'  => $this->_author,
											'modified'=> $this->_modified
									);
									$this->getDefinedTable(Acl\RoleprocessTable::class)->save($role_process_data);
								endif;
								$this->_connection->commit();
								$this->flashMessenger()->addMessage("success^Successfully transfered permission.");
							else:
								$this->_connection->rollback();
								$this->flashMessenger()->addMessage("error^Failed to transfer permission.");
							endif;
						else:
							$this->flashMessenger()->addMessage("warning^No access control list found.");
						endif;
					else:
						$this->flashMessenger()->addMessage("warning^Please map transfer to role with module in Role Module Privileges before transfering permissions.");
					endif;
				else:
					$this->flashMessenger()->addMessage("warning^Permission already exists.");
				endif;
			else:
				$this->flashMessenger()->addMessage("warning^Missing form inputs while transfering permission.");
			endif;
			return $this->redirect()->toRoute('acl',array('action'=>'permission','id'=>$this->_id));
		endif;
		$data = array(
			'resource' => $resource,
			'process'  => $process,
			'role'     => $role,
			'selected_data' => $selected_data,
		);
		$ViewModel = new ViewModel(array(
				'title'      => 'Transfer Permission',
				'data'       => $data,
				'modules'    => $this->getDefinedTable(Acl\ModuleTable::class)->get(array('general'=>'0')),
				'processObj' => $this->getDefinedTable(Acl\ProcessTable::class),
				'roleObj'    => $this->getDefinedTable(Acl\RolesTable::class),
		));
		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
     * remove permission
	 */
	public function removepermissionAction()
	{
		$this->init();
		
		$remove_data = $this->_id;
		$array_id = explode("_", $remove_data);
		$resource = $array_id[0];
		$process = $array_id[1];
		$role = $array_id[2];
		$action = $array_id[3];
		$selected_process = $array_id[4];
		$selected_role = $array_id[5];
		$selected_data = $resource."_".$selected_process."_".$selected_role;
		
		$acls = $this->getDefinedTable(Acl\AclTable::class)->get(array('resource'=>$resource,'process'=>$process));
		foreach($acls as $acl):
			$roleacl_id = $this->getDefinedTable(Acl\RoleaclTable::class)->getColumn(array('acl'=>$acl['id'],'role'=>$role),'id');
			if($roleacl_id):
				$result = $this->getDefinedTable(Acl\RoleaclTable::class)->remove($roleacl_id);
			endif;
		endforeach;
		$check3 = $this->getDefinedTable(Acl\RoleprocessTable::class)->isPresent(array('process'=>$process,'role'=>$role));
		if($check3==1):
			$role_process_id = $this->getDefinedTable(Acl\RoleprocessTable::class)->getColumn(array('process'=>$process,'role'=>$role),'id');
			$this->getDefinedTable(Acl\RoleprocessTable::class)->remove($role_process_id);
		endif;
		$this->flashMessenger()->addMessage("success^Successfully removed permission.");
		return $this->redirect()->toRoute('acl',array('action'=>'permission','id'=>$selected_data));
	}
	/**
	 * Report Permission Manager	
	 */
	public function reportpermissionAction()
	{
		$this->init();
		
		if($this->getRequest()->isPost())
		{
			$form = $this->getRequest()->getpost();
			
			$data_report = $form['data_report'];
			$data_role = $form['data_role'];
			$data_check = $form['data_check'];
			if($data_check):
				$data = array(
					'acl'     => $data_report,
					'role'    => $data_role,
					'author'  => $this->_author,
					'created' => $this->_created,
					'modified'=> $this->_modified
				);
				$result = $this->getDefinedTable(Acl\RoleaclTable::class)->save($data);
			else:
				$roleacl_id = $this->getDefinedTable(Acl\RoleaclTable::class)->getColumn(array('acl'=>$data_report,'role'=>$data_role),'id');
				$result = $this->getDefinedTable(Acl\RoleaclTable::class)->remove($roleacl_id);
			endif;
			 
			echo json_encode(array(
				'data_report'=> $data_report,
				'data_role'=> $data_role,
				'data_check'=> $data_check,
				'result' => $result,
			));
			exit;
		}else{
			$id = $this->_id;
			$array_id = explode("_", $id);
			if(sizeof($array_id)==2):
				$resource = isset($array_id[0])?$array_id[0]:'-1';
				$report = isset($array_id[1])?$array_id[1]:'-1';
			else:
				$resource = '-1';
				$report = '-1';
			endif;
		}
		
		$data = array(
			'resource' => $resource,
			'report'  => $report,
		);
		
		return new ViewModel(array(
				'title'        => 'Report Role Permission',
				'data'         => $data,
				'highest_role' => $this->_highest_role,
				'modules'      => $this->getDefinedTable(Acl\ModuleTable::class)->get(array('general'=>'0')),
				'rolesObj'     => $this->getDefinedTable(Acl\RolesTable::class),
				'roleaclObj'   => $this->getDefinedTable(Acl\RoleaclTable::class),
				'aclObj'       => $this->getDefinedTable(Acl\AclTable::class),
		));
	}
}
