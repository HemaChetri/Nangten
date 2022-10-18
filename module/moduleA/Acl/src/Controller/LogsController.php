<?php
namespace Acl\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Interop\Container\ContainerInterface;
use Laminas\Mvc\MvcEvent;
use Administration\Model as Administration;
use Acl\Model as Acl;

class LogsController extends AbstractActionController
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
			$this->redirect()->toRoute('auth', array('action' => 'logout'));
		endif;	
		$this->_config = $this->_container->get('Config');
		$this->_user = $this->identity();
		$this->_login_id = $this->_user->id;
		$this->_login_role = $this->_user->role;
		$this->_author = $this->_user->id;		
		$this->_id = $this->params()->fromRoute('id');
		$this->_created = date('Y-m-d H:i:s');
		$this->_modified = date('Y-m-d H:i:s');		  
	}
	/**
	 * Log Controller index Actioin
	 */
	public function indexAction()
	{
		$this->init();
		return new ViewModel([
			'title' => 'Acl Menu',
		]);
	}
	/**
	 * login history Action
	 */
    public function loginlogAction()
    {  
    	$this->init(); 
		$resource = (isset($this->_id))? $this->_id:$this->_login_id;
		$loginTable = $this->getDefinedTable(Acl\LoginsTable::class)->get(array('author'=>$resource));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($loginTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'     => 'Login History',
			'paginator' => $paginator,
			'resource'  => $resource,
			'page'      => $page,
			'userObj'   => $this->getDefinedTable(Administration\UsersTable::class),
		)); 
	}
	/**
	 *  audit trails or activity logs action
	 */
	public function auditAction()
	{
	    $this->init(); 
		$resource = (isset($this->_id))? $this->_id:$this->_login_id;
		$activitylogTable = $this->getDefinedTable(Acl\ActivityLogTable::class)->get(array('author'=>$resource));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($activitylogTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'      => 'Audit Logs',
			'paginator'  => $paginator,
			'resource'   => $resource,
			'page'       => $page,
			'userObj'    => $this->getDefinedTable(Administration\UsersTable::class),
			'roleObj'    => $this->getDefinedTable(Acl\RolesTable::class),
			'processObj' => $this->getDefinedTable(Acl\ProcessTable::class),
		)); 
	}
}
