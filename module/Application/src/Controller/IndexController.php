<?php
/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Acl\Model as Acl;
use Awpb\Model as Awpb;
use Implementation\Model as Implementation;
use Administration\Model as Administration;
use Nangten\Model as Nangten;
class IndexController extends AbstractActionController
{
    private   $_container;
	protected $_table; 		// database table 
    protected $_user; 		// user detail
    protected $_login_id; 	// logined user id
    protected $_login_role; // logined user role
	protected $_login_location; // logined user location
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
	 * Zend Default TableGateway
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
		if(!isset($this->_login_location)){
			$this->_login_location = $this->_user->location; 
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
	}

    public function indexAction()
    {
        $this->init();
		
		switch($this->_login_role){
			case 2:
				case 3:
					case 4: return $this->redirect()->toRoute('application', array('action'=>'panel3'));
					break;
			case 5:
				case 6:
					case 7:
						case 8:
							case 9: return $this->redirect()->toRoute('application', array('action'=>'panel2'));
							break;
			case 99:
				case 100: return $this->redirect()->toRoute('application', array('action'=>'panel3'));
						break;
			default: return $this->redirect()->toRoute('application', array('action'=>'panel3'));
					break;
		}
    }
	/**
	 * Default Panel -- Public
	 */
	public function panel1Action()
	{
		$this->init();
		return new ViewModel(array(
			'title'             => 'Dashboard-Panel-1',
            'menus'             => $this->getDefinedTable(Acl\AclTable::class)->renderDashboard($this->_user,$this->_highest_role),
		));
	}
	/**
	 * Admin Panel -- Administrator & System Manager
	 */
	public function panel2Action()
	{
		$this->init();
		return new ViewModel(array(
			'title'             => 'Dashboard-Panel-2',
            'menus'             => $this->getDefinedTable(Acl\AclTable::class)->renderDashboard($this->_user,$this->_highest_role),
			'userObj'           => $this->getDefinedTable(Administration\UsersTable::class),
			'locationObj'      	=> $this->getDefinedTable(Administration\LocationTable::class),
		));
	}
	/**
	 * M&E Panel -- FAO-TA & PMU M&E Officer & PMU PD & Report Viewer
	 */
	public function panel3Action()
	{
		$this->init();
		return new ViewModel(array(
			'title'             => 'Dashboard-Panel-3',
			'login_location'    => $this->_login_location,
			'userObj'           => $this->getDefinedTable(Administration\UsersTable::class),
			'nangtenObj'        => $this->getDefinedTable(Nangten\NangtenTable::class),
			'lhakhangObj'        => $this->getDefinedTable(Administration\LhakhangTable::class),
			'dzoObj'        	=> $this->getDefinedTable(Administration\DistrictTable::class),
			'gewogObj'        	=> $this->getDefinedTable(Administration\BlockTable::class),
			'groupObj'        	=> $this->getDefinedTable(Nangten\GroupTable::class),
			'categoryObj'       => $this->getDefinedTable(Nangten\CategoryTable::class),
			'subcategoryObj'    => $this->getDefinedTable(Nangten\SubCategoryTable::class),
			
		));
	}
	/**
	 * User Panel -- Dzongkhag Focal & ARDC Focal & DAMC Focal
	 */
	public function panel4Action()
	{
		$this->init();
		return new ViewModel(array(
			'title'             => 'Dashboard-Panel-4',
			'login_location'    => $this->_login_location,
			'districtObj'       => $this->getDefinedTable(Administration\DistrictTable::class), 
			'locationObj'       => $this->getDefinedTable(Administration\LocationTable::class), 
			'implementationObj' => $this->getDefinedTable(Implementation\ImplementationTable::class), 
			'beneficiaryObj'    => $this->getDefinedTable(Implementation\BeneficiaryTable::class), 
			'farmergroupObj'    => $this->getDefinedTable(Implementation\FarmerGroupTable::class), 
			'schoollinkObj'     => $this->getDefinedTable(Implementation\SchoolLinkTable::class),
			'workplanObj'       => $this->getDefinedTable(Awpb\WorkPlanTable::class),
		));
	}
	/**
	 * Activity Log
	 */
	public function activitylogAction()
	{
		$this->init();
		
		$id = $this->params()->fromRoute('id');	
		$params = explode("-", $id);
		$process = $params['0'];
		$process_id = $params['1'];		
		$activitylogs = $this->getDefinedTable(Acl\ActivityLogTable::class)->get(array('process'=>$process, 'process_id'=>$process_id));		
		
		$viewModel =  new ViewModel(array(
				'title'        => 'Activity Logs',
				'activitylogs' => $activitylogs,
				'usersObj'     => $this->getDefinedTable(Administration\UsersTable::class),
				'roleObj'      => $this->getDefinedTable(Acl\RolesTable::class),
		));
		$viewModel->setTerminal('false');
		return $viewModel;
	}
	/**
	 * Documentation
	 * User Manual
	 */
	public function documentationAction()
	{
		$this->init();
		return new ViewModel(array(
			'title' => 'Documentation',
		));
	}
}