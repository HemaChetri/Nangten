<?php
namespace Acl\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Interop\Container\ContainerInterface;

class IndexController extends AbstractActionController
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

		if(!isset($this->_author)){
			$this->_author = $this->_user->id;
		}

		$this->_id = $this->params()->fromRoute('id');

		$this->_created = date('Y-m-d H:i:s');
		$this->_modified = date('Y-m-d H:i:s');

		$fileManagerDir = $this->_config['file_manager']['dir'];

		if(!is_dir($fileManagerDir)) {
			mkdir($fileManagerDir, 0777);
		}

		$this->_dir =realpath($fileManagerDir);
	}

	/**
	 *  index action
	 */
	public function indexAction()
	{
		$this->init();
		
		return new ViewModel([
			'title' => 'Acl Menu',
		]);
	}
}
