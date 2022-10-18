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

class LhakhangController extends AbstractActionController
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
	 *  Lhakhang action
	 */
	public function lhakhangAction()
	{
		$this->init();
		$lhakhangTable = $this->getDefinedTable(Administration\LhakhangTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($lhakhangTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Lhakhang',
			'paginator'        => $paginator,
			'page'             => $page,
			'gewogObj' 		   => $this->getDefinedTable(Administration\BlockTable::class),
		)); 
	}
	/**
	 * addlhakhang action
	 */
	public function addlhakhangAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			 $data = array(  
				'GewogId'            => $form['GewogId'],
				'LhakhangNameEn'     => $form['LhakhangNameEn'],
				'LhakhangNameDz'     => $form['LhakhangNameDz'],
				'LhakhangOwnerDz'    => $form['LhakhangOwnerDz'],
				'LhakhangOwnerEn'    => $form['LhakhangOwnerEn'],
				'status'             => $form['status'],
				'author'             => $this->_author,
				'created'            => $this->_created,
				'modified'           => $this->_modified,
            );
			$data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Administration\LhakhangTable::class)->save($data);
            if($result):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully added new Lhakhang");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to add new Lhakhang");
            endif;
			return $this->redirect()->toRoute('lhakhang/paginator', array('action'=>'lhakhang','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title' => 'Add Lhakhang',
				'page'  => $page,
				'gewog' =>$this->getDefinedTable(Administration\BlockTable::class)->getAll()
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * editdistrict action
	 */
	public function editlhakhangAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$lhakhang_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
            $data = array(  
				'id'                 => $form['lhakhang_id'],
				'GewogId'            => $form['GewogId'],
				'LhakhangNameEn'     => $form['LhakhangNameEn'],
				'LhakhangNameDz'     => $form['LhakhangNameDz'],
				'LhakhangOwnerDz'    => $form['LhakhangOwnerDz'],
				'LhakhangOwnerEn'    => $form['LhakhangOwnerEn'],
				'status'             => $form['status'],
				'author'             => $this->_author,
				'created'            => $this->_created,
				'modified'           => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Administration\LhakhangTable::class)->save($data);
            if($result > 0):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully edited Lhakhang");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to edit Lhakhang");
            endif;
			return $this->redirect()->toRoute('lhakhang/paginator', array('action'=>'lhakhang','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title'    => 'Edit Lhakhang',
				'page'     => $page,
				'gewogs'    =>$this->getDefinedTable(Administration\BlockTable::class)->getAll(),
				'lhakhang' => $this->getDefinedTable(Administration\LhakhangTable::class)->get($lhakhang_id),
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *  Lhakhang Section action
	 */
	public function lhakhangsectionAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$lhakhang = (sizeof($array_id)>1)?$array_id[1]:'';
		$gewog = $array_id[0];
		if($this->getRequest()->isPost())
		{
			$form      		 = $this->getRequest()->getPost();
			$gewog  		 = $form['gewog'];
			$lhakhang        = $form['lhakhang'];
		}else{
			$gewog          = '-1';
			$lhakhang  	    = '-1';
		}
		$data = array(
			'gewog'  		    => $gewog,
			'lhakhang'          => $lhakhang,
		);
		$lhakhangsectionTable = $this->getDefinedTable(Administration\LhakhangSectionTable::class)->getReport($data);
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($lhakhangsectionTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Lhakhang Section',
			'paginator'        => $paginator,
			'page'             => $page,
			'data'      	   => $data,
			'lhakhangObj'      =>$this->getDefinedTable(Administration\LhakhangTable::class),
			'gewogObj'         =>$this->getDefinedTable(Administration\BlockTable::class),
			'lhakhang'         =>$this->getDefinedTable(Administration\LhakhangTable::class)->get(['GewogId'=>$data['gewog']]),
			'gewog'            =>$this->getDefinedTable(Administration\BlockTable::class)->get(array('status'=>1)),
		)); 
	}
	/**
	 * Add  Lhakhang Section type
	 */
    public function addlhakhangsectionAction()
    {
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$data = array(
				'GewogId'        => $form['GewogId'],
				'LhakhangId'     => $form['LhakhangId'],
				'SectionNameEn'  => $form['SectionNameEn'],
				'SectionNameDz'  => $form['SectionNameDz'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'created'        => $this->_created,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\LhakhangSectionTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new Lhakhang Section"); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new Lhakhang Section.");	 	             
			endif;
			return $this->redirect()->toRoute('lhakhang/paginator',array('action'=>'lhakhangsection','page'=>$this->_id, 'id'=>$form['GewogId'].'_'.$form['LhakhangId']));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Add New Lhakhang Section',
			'page'         => $page,
			'gewog'        =>$this->getDefinedTable(Administration\BlockTable::class)->get(array('status'=>1)),
			'lhakhang'     =>$this->getDefinedTable(Administration\LhakhangTable::class)->getAll(),
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *   Edit Lhakhang Section Action
	 **/
	public function editlhakhangsectionAction()
	{
	    $this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$lhakhangsection_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(
				'id'             => $form['lhakhangsection_id'],
				'GewogId'        => $form['GewogId'],
				'LhakhangId'     => $form['LhakhangId'],
				'SectionNameEn'  => $form['SectionNameEn'],
				'SectionNameDz'  => $form['SectionNameDz'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\LhakhangSectionTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited Lhakhang Section"); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit Lhakhang Section.");	 	             
			endif;
			return $this->redirect()->toRoute('lhakhang/paginator',array('action'=>'lhakhangsection','page'=>$this->_id, 'id'=>$form['GewogId'].'_'.$form['LhakhangId']));
		}
		$ViewModel = new ViewModel(array(
				'title'         	=> 'Edit Lhakhang Section',
				'page'          	=> $page,
				'lhakhangsection' 	=> $this->getDefinedTable(Administration\LhakhangSectionTable::class)->get($lhakhangsection_id),
				'gewog'        		=>$this->getDefinedTable(Administration\BlockTable::class)->get(array('status'=>1)),
				'lhakhang'     		=>$this->getDefinedTable(Administration\LhakhangTable::class)->getAll(),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	} 

  /**
	 * Get Lhakhang
	 */
	public function getlhakhangAction()
	{
		$form = $this->getRequest()->getPost();
		$gewogId = $form['gewogId'];
		$lhakhangValue = "<option value=''></option>";
	
		$details = $this->getDefinedTable(Administration\LhakhangTable::class)->get(['GewogId'=>$gewogId]);
			foreach($details as $detail):
				$lhakhangValue .= "<option value='".$detail['id']."'>".$detail['LhakhangNameDz']."</option>";
			endforeach;	
		echo json_encode(array(
				'lhakhangValue' => $lhakhangValue,
		));
		exit;
	}
}