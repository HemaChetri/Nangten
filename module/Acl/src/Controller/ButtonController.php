<?php
namespace Acl\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Interop\Container\ContainerInterface;
use Laminas\Mvc\MvcEvent;
use Acl\Model as Acl;

class ButtonController extends AbstractActionController
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
	 * index Action of ButtonController
	 */
    public function indexAction()
    {  
    	$this->init();
    	return new ViewModel([
			'title' => 'Acl Menu',
		]);
	}
	/* 
	 * Menu manager controller 
	 */
	public function menuAction()
	{
		$this->init();	
		
		$resource = (isset($this->_id))? $this->_id:'3';
		$aclTable = $this->getDefinedTable(Acl\AclTable::class)->get(array('resource'=>$resource));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($aclTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'     => 'Menu Manager',
			'paginator' => $paginator,
			'resource'  => $resource,
			'page'      => $page,
			'modules'   => $this->getDefaultTable('sys_modules')->select(),
		)); 
	}
	/*
	 * Edit Menu controller
	 */
	public function editmenuAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$menu_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			if($form['btn']==1):
				$data = array(
						'id' 	         => $form['acl_id'],
						'menu'	         => $form['menu'],
						'display'	     => $form['display'],
						'description'	 => $form['description'],
						'author'         => $this->_author,
						'modified'       => $this->_modified
				);
			else:
				$data = array(
						'id' 	         => $form['acl_id'],
						'menu'	         => $form['menu'],
						'display'	     => $form['display'],
						'icon'           => $form['icon'],
						'description'	 => $form['description'],
						'author'         => $this->_author,
						'modified'       => $this->_modified
				);
			endif;
			$data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Acl\AclTable::class)->save($data);
			if($result):
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^Successfully edited Menu.");
			else:
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^Failed to edit Menu.");
			endif;
			return $this->redirect()->toRoute('button/paginator',array('action'=>'menu','page'=>$this->_id,'id'=>$form['resource'],'role'=>'0','task'=>'0'));
		endif;	
		
		$ViewModel = new ViewModel(array(
				'title'     => 'Edit Menu',
				'page'      => $page,
				'acl'       => $this->getDefinedTable(Acl\AclTable::class)->get($menu_id),
				'icongroupObj' => $this->getDefinedTable(Acl\IcongroupTable::class),
				'iconObj'      => $this->getDefinedTable(Acl\IconTable::class),
		));
		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * Get the icons via icon group
	 */
	public function geticongroupchangeAction()
	{	
		$form = $this->getRequest()->getPost();
		$group = $form['group'];
		$icons = $this->getDefinedTable(Acl\IconTable::class)->getColumnValue('i.icon_group',$group);
		
		$icon = "<option value='0' selected>None</option>";
		foreach($icons as $row1):
			$icon.="<option value='".$row1['icon']."'>".$row1['icon']."</option>";
		endforeach;
		
		echo json_encode(array(
				'icon' => $icon,
		));
		exit;
	}
	/**
	 * button manager Action
	 */
    public function buttonAction()
    {  
    	$this->init(); 
		$resource = (isset($this->_id))? $this->_id:'3';
		$aclTable = $this->getDefinedTable(Acl\AclTable::class)->get(array('resource'=>$resource));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($aclTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'     => 'Button Manager',
			'paginator' => $paginator,
			'resource'  => $resource,
			'page'      => $page,
			'modules'   => $this->getDefaultTable('sys_modules')->select(),
			'aclObj'         => $this->getDefinedTable(Acl\AclTable::class),
			'btnpositionObj' => $this->getDefinedTable(Acl\ButtonPositionTable::class),
		)); 
	}
	/**
     * edit button
	 */
	public function editbuttonAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$button_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			$button = "";
			foreach($form['class'] as $btn_class):
				$button .= " ".$btn_class;
			endforeach;
			
			if($form['btn']==1):
				$data = array(
						'id' 	         => $form['acl_id'],
						'parent_page'	 => $form['parent_page'],
						'button_position'=> $form['button_position'],
						'icon'           => $form['icon'],
						'class'          => $button,
						'btn'	         => $form['btn'],
						'btn_type'	     => $form['btn_type'],
						'btn_label'	     => $form['btn_label'],
						'author'         => $this->_author,
						'modified'       => $this->_modified
				);
			else:
				$data = array(
						'id' 	         => $form['acl_id'],
						'parent_page'	 => $form['parent_page'],
						'button_position'=> $form['button_position'],
						'class'          => $button,
						'btn'	         => $form['btn'],
						'btn_type'	     => $form['btn_type'],
						'btn_label'	     => $form['btn_label'],
						'author'         => $this->_author,
						'modified'       => $this->_modified
				);
			endif;
			$data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Acl\AclTable::class)->save($data);
			if($result):
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^Successfully edited Button.");
			else:
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^Failed to edit Button.");
			endif;
			return $this->redirect()->toRoute('button/paginator',array('action'=>'button','page'=>$this->_id, 'id'=>$form['resource'],'role'=>'0','task'=>'0'));
		endif;	
		
		$ViewModel = new ViewModel(array(
				'title'     => 'Edit Button',
				'page'      => $page,
				'acl'       => $this->getDefinedTable(Acl\AclTable::class)->get($button_id),
				'aclObj'       => $this->getDefinedTable(Acl\AclTable::class),
				'btnpositions' => $this->getDefinedTable(Acl\ButtonPositionTable::class)->getAll(),
				'elements'     => $this->getDefinedTable(Acl\ButtonTable::class)->getAll(),
				'icongroupObj' => $this->getDefinedTable(Acl\IcongroupTable::class),
				'iconObj'      => $this->getDefinedTable(Acl\IconTable::class),
		)); 
		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * * Athang
	 * aclprocess Action
	 */
	public function aclprocessAction()
    {  
    	$this->init(); 
		
		$resource = (isset($this->_id))? $this->_id:'3';
		$processTable = $this->getDefinedTable(Acl\ProcessTable::class)->get(array('resource'=>$resource));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($processTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'     => 'Functional Process List',
			'paginator' => $paginator,
			'resource'  => $resource,
			'page'      => $page,
			'modules'   => $this->getDefaultTable('sys_modules')->select(),
			'statusObj'  => $this->getDefinedTable(Acl\StatusTable::class),
		)); 
	}
	/**
	 * * Athang
	 * manage acl process Action
	 */
	public function manageaclprocessAction()
    {  
    	$this->init(); 
		$id = $this->_id;
		$array_id = explode("_", $id);
		$process_id = $array_id[0];
		$page = $array_id[1];
		
		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			$permission_level = implode(',',$form['permission_level']);
			$data = array(
					'id' 	          => $form['process_id'],
					'process'	      => $form['process'],
					'description'	  => $form['description'],
					'permission_level'=> $permission_level,
					'location'	      => $form['location'],
					'only_if_creator' => $form['only_if_creator'],
					'status'          => $form['status'],
					'author'          => $this->_author,
					'created'         => $this->_created,
					'modified'        => $this->_modified
			);
			$data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Acl\ProcessTable::class)->save($data);
			if($result):
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^Successfully edited process.");
			else:
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^Failed to edit process.");
			endif;
			return $this->redirect()->toRoute('button/paginator',array('action'=>'aclprocess','page'=>$this->_id,'id'=>$form['resource'],'role'=>'0','task'=>'0'));
		endif;	
		
		$ViewModel = new ViewModel(array(
				'title'      => 'Manage Functional Process',
				'page'       => $page,
				'aclprocess' => $this->getDefinedTable(Acl\ProcessTable::class)->get($process_id),
				'status'     => $this->getDefinedTable(Acl\StatusTable::class)->getAll(),
		));
		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * * Athang
	 * Get permission level via process
	 */
	public function getpermissionlevelAction()
	{	
		$form = $this->getRequest()->getPost();
		$process = $form['process'];
		$level = "<option value='0' selected>All Levels</option>";
		if($process!='0'):
			$process_permissions = $this->getDefinedTable(Acl\ProcessTable::class)->getColumn($process,'permission_level');
			$permission_array = explode(",", $process_permissions);
			if(sizeof($permission_array)>0):
				for($i=0;$i<sizeof($permission_array);$i++):
					$level.="<option value='".$permission_array[$i]."'>".$this->getDefinedTable(Acl\StatusTable::class)->getColumn($permission_array[$i],'status')."</option>";
				endfor;
			endif;
		endif;
		
		echo json_encode(array(
				'level' => $level,
		));
		exit;
	}
	/**
	 * * Athang
	 * aclAction
	 */
	public function aclAction()
    {  
    	$this->init(); 
		
		$resource = (isset($this->_id))? $this->_id:'3';
		$aclTable = $this->getDefinedTable(Acl\AclTable::class)->get(array('resource'=>$resource));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($aclTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'     => 'Acl Manager',
			'paginator' => $paginator,
			'resource'  => $resource,
			'page'      => $page,
			'modules'   => $this->getDefaultTable('sys_modules')->select(),
			'roleObj'   => $this->getDefinedTable(Acl\RolesTable::class),
			'processObj' => $this->getDefinedTable(Acl\ProcessTable::class),
			'pactionObj' => $this->getDefinedTable(Acl\ProcessActionTable::class),
			'statusObj'  => $this->getDefinedTable(Acl\StatusTable::class),
		)); 
	}
	/**
	 * * Athang
	 * manage acl Action
	 */
	public function manageaclAction()
    {  
    	$this->init(); 
		$id = $this->_id;
		$array_id = explode("_", $id);
		$acl_id = $array_id[0];
		$page = $array_id[1];
		
		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			$process = ($form['system']==1 || $form['process_action']=='10')?'0':$form['process'];
			$process_action = ($form['system']==1)?'0':$form['process_action'];
			$data = array(
					'id' 	         => $form['acl_id'],
					'role'	         => $form['role'],
					'process'	     => $process,
					'process_action' => $process_action,
					'permission_level' => $form['permission_level'],
					'dashboard'	     => $form['dashboard'],
					'system'         => $form['system'],
					'author'         => $this->_author,
					'modified'       => $this->_modified
			);
			$data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Acl\AclTable::class)->save($data);
			if($result):
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^Successfully edited ACL Privilege.");
			else:
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^Failed to edit ACL Privilege.");
			endif;
			return $this->redirect()->toRoute('button/paginator',array('action'=>'acl','page'=>$this->_id,'id'=>$form['resource'],'role'=>'0','task'=>'0'));
		endif;	
		
		$ViewModel = new ViewModel(array(
				'title'     => 'Manage Acl',
				'page'      => $page,
				'roles'     => $this->getDefinedTable(Acl\RolesTable::class)->getAll(),
				'acl'       => $this->getDefinedTable(Acl\AclTable::class)->get($acl_id),
				'processObj'=> $this->getDefinedTable(Acl\ProcessTable::class),
				'pactions'  => $this->getDefinedTable(Acl\ProcessActionTable::class)->getAll(),
				'statusObj' => $this->getDefinedTable(Acl\StatusTable::class),
		));
		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
}