<?php
namespace Administration\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Interop\Container\ContainerInterface;
use Administration\Model as Administration;
use Acl\Model as Acl;

class MasterController extends AbstractActionController
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
	protected $_safedataObj; // safedata controller plugin
    
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
	 * index Action of MasterController
	 */
    public function indexAction()
    {  
    	$this->init(); 
		
        return new ViewModel([
			'title' => 'Administration Menu',
		]);
	}
	/**
	 *  district action
	 */
	public function districtAction()
	{
		$this->init();
		$districtTable = $this->getDefinedTable(Administration\DistrictTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($districtTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Dzongkhag',
			'paginator'        => $paginator,
			'page'             => $page,
		)); 
	}
	/**
	 * adddistrict action
	 */
	public function adddistrictAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$max_code = $this->getDefinedTable(Administration\DistrictTable::class)->getMax('DzongkhagCode');
			$max_code = $max_code+1;
			$num_length = strlen((string)$max_code);
			if($num_length == 1){$max_code = '0'.$max_code;}
            $data = array(  
				'DzongkhagCode'      => $max_code,
				'DzongkhagNameEn'    => $form['DzongkhagNameEn'],
				'DzongkhagNameDz'    => $form['DzongkhagNameDz'],
				'status'             => $form['status'],
				'author'             => $this->_author,
				'created'            => $this->_created,
				'modified'           => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Administration\DistrictTable::class)->save($data);
            if($result):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully added new dzongkhag");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to add new dzongkhag");
            endif;
			return $this->redirect()->toRoute('setmaster/paginator', array('action'=>'district','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title' => 'Add Dzongkhag',
				'page'  => $page,
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * editdistrict action
	 */
	public function editdistrictAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$district_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
            $data = array(  
				'id'                 => $form['district_id'],
				'DzongkhagNameEn'    => $form['DzongkhagNameEn'],
				'DzongkhagNameDz'    => $form['DzongkhagNameDz'],
				'status'             => $form['status'],
				'author'             => $this->_author,
				'modified'           => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Administration\DistrictTable::class)->save($data);
            if($result > 0):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully edited dzongkhag");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to edit dzongkhag");
            endif;
			return $this->redirect()->toRoute('setmaster/paginator', array('action'=>'district','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title'    => 'Edit Dzongkhag',
				'page'     => $page,
				'districts'=> $this->getDefinedTable(Administration\DistrictTable::class)->get($district_id),
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**  
	 * Block action
	 */
	public function blockAction()
	{	
		$this->init();
		$district_id = (isset($this->_id))? $this->_id:'1';
		$blockTable = $this->getDefinedTable(Administration\BlockTable::class)->getColumnValue('DzongkhagId',$district_id);
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($blockTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'         => 'Gewog',
			'paginator'     => $paginator,
			'district_id'   => $district_id,
			'page'          => $page,
			'districtObj'   => $this->getDefinedTable(Administration\DistrictTable::class),
		)); 
	} 
	/**
	 *  Add Block action
	 */
	public function addblockAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$max_code = $this->getDefinedTable(Administration\BlockTable::class)->getMax('GewogCode');
			$max_code = $max_code+1;
			$num_length = strlen((string)$max_code);
			switch($num_length):
				case 1:
					$max_code = '00'.$max_code;
					break;
				case 2:
					$max_code = '0'.$max_code;
					break;
				default:
					$max_code = $max_code;
					break;
			endswitch;
			$data = array(	
					'DzongkhagId'  => $form['DzongkhagId'],
					'GewogCode'    => $max_code,
					'GewogNameEn'  => $form['GewogNameEn'],
					'GewogNameDz'  => $form['GewogNameDz'],
					'status'       => $form['status'],
					'author'       => $this->_author,
					'created'      => $this->_created,
					'modified'     => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\BlockTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new gewog.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new gewog.");
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'block','page'=>$this->_id, 'id'=>$form['DzongkhagId']));
						 
		}
		$ViewModel = new ViewModel(array(
				'title'      => 'Add Gewog',
				'page'       => $page,
				'districts'  => $this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>'1')),	
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**
	 *  Edit Block action
	 */
	public function editblockAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$block_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(	
				'id'           => $form['block_id'],
				'DzongkhagId'  => $form['DzongkhagId'],
				'GewogNameEn'  => $form['GewogNameEn'],
				'GewogNameDz'  => $form['GewogNameDz'],
				'status'       => $form['status'],
				'author'       => $this->_author,
				'modified'     => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\BlockTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited gewog.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edited gewog.");
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'block','page'=>$this->_id, 'id'=>$form['DzongkhagId']));
		}
		$ViewModel = new ViewModel(array(
				'title'      => 'Edit Gewog',
				'page'       => $page,
				'districts'  => $this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>'1')),	
				'blocks'     => $this->getDefinedTable(Administration\BlockTable::class)->get($block_id),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**
	 *  location type action
	 */
	public function locationtypeAction()
	{
		$this->init();
		$locationtypeTable = $this->getDefinedTable(Administration\LocationTypeTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($locationtypeTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Location Type',
			'paginator'        => $paginator,
			'page'             => $page,
		)); 
	}
	/**
	 * add location type
	 */
    public function addlocationtypeAction()
    {
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$data = array(
				'location_type'  => $form['location_type'],
				'description'    => $form['description'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'created'        => $this->_created,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\LocationTypeTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new Location type."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new Location type.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator', array('action' => 'locationtype', 'page'=>$this->_id, 'id'=>'0'));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Add New Location Type',
			'page'         => $page,
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *  editlocationtype Action
	 **/
	public function editlocationtypeAction()
	{
	    $this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$locationtype_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(
				'id'             => $form['locationtype_id'],
				'location_type'  => $form['location_type'],
				'description'    => $form['description'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\LocationTypeTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited Location type."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit Location type.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'locationtype','page'=>$this->_id, 'id'=>'0'));
		}
		$ViewModel = new ViewModel(array(
				'title'         => 'Edit Location Type',
				'page'          => $page,
				'locationtypes' => $this->getDefinedTable(Administration\LocationTypeTable::class)->get($locationtype_id),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**  
	 * Location action
	 */
	public function locationAction()
	{	
		$this->init();
		$locationtype_id = (isset($this->_id))? $this->_id:'-1';
		$locationTable = $this->getDefinedTable(Administration\LocationTable::class)->getColumnValue('location_type',$locationtype_id);
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($locationTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'           => 'Location',
			'paginator'       => $paginator,
			'locationtype_id' => $locationtype_id,
			'page'            => $page,
			'locationtypeObj' => $this->getDefinedTable(Administration\LocationTypeTable::class),
			'districtObj'     => $this->getDefinedTable(Administration\DistrictTable::class),
			'locationObj'     => $this->getDefinedTable(Administration\LocationTable::class),
		)); 
	} 
	/**
	 *  addlocation action
	 */
	public function addlocationAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$max_code = $this->getDefinedTable(Administration\LocationTable::class)->getMax('location_code');
			$max_code = $max_code+1;
			$num_length = strlen((string)$max_code);
			if($num_length == 1){$max_code = '0'.$max_code;}
			$data = array(
					'location'       => $form['location'],
					'location_code'  => $max_code,
					'location_type'  => $form['location_type'],
					'district'       => $form['district'],
					'coordinates'    => $form['coordinates'],
					'status'         => $form['status'],
					'author'         => $this->_author,
					'created'        => $this->_created,
					'modified'       => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\LocationTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new Location.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new Location.");
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'location','page'=>$this->_id, 'id'=>$form['location_type']));
		}
		$ViewModel = new ViewModel(array(
				'title'         => 'Add Location',
				'page'          => $page,
				'locationtypes' => $this->getDefinedTable(Administration\LocationTypeTable::class)->get(array('status'=>'1')),	
				'districts'     => $this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>'1')),	
				'ardcs'         => $this->getDefinedTable(Administration\LocationTable::class)->getlocation(array('location_type'=>'2','status'=>'1')),	
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**
	 *  editlocation action
	 */
	public function editlocationAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$location_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(	
					'id'             => $form['location_id'],
					'location'       => $form['location'],
					'location_type'  => $form['location_type'],
					'district'       => $form['district'],
					'coordinates'    => $form['coordinates'],
					'status'         => $form['status'],
					'author'         => $this->_author,
					'modified'       => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\LocationTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited Location.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit Location.");
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'location','page'=>$this->_id, 'id'=>$form['location_type']));
		}
		$ViewModel = new ViewModel(array(
				'title'         => 'Edit Location',
				'page'          => $page,
				'locationtypes' => $this->getDefinedTable(Administration\LocationTypeTable::class)->get(array('status'=>'1')),	
				'districts'     => $this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>'1')),	
				'ardcs'         => $this->getDefinedTable(Administration\LocationTable::class)->getlocation(array('location_type'=>'2','status'=>'1')),	
				'locations'		=> $this->getDefinedTable(Administration\LocationTable::class)->get($location_id),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
}