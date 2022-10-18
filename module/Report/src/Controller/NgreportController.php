<?php
namespace Report\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Interop\Container\ContainerInterface;
use Acl\Model As Acl;
use Administration\Model As Administration;
use Nangten\Model As Nangten;

class NgreportController extends AbstractActionController
{
	private $_container;
	protected $_table; 		// database table
	protected $_user; 		// user detail
	protected $_login_id; 	// logined user id
	protected $_sub_roles; 	// logined user sub role
	protected $_login_role; // logined user role
	protected $_login_location; // logined user location
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
		
		$this->_safedataObj = $this->safedata();
		$this->_connection = $this->_container->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
		
		$this->_permissionObj =  $this->PermissionPlugin();
		$this->_permission = $this->_permissionObj->permission($this->getEvent());
	}
	/**
	 *  Nangten Report action
	 */
	public function nangtenreportAction()
	{  
    	$this->init(); 
		$id = $this->_id;
			$array_id = explode("_", $id);
			$dzongkhag = $array_id[0];
			$gewog = (sizeof($array_id)>1)?$array_id[1]:'';
			$group = (sizeof($array_id)>2)?$array_id[2]:'';
			$material = (sizeof($array_id)>3)?$array_id[3]:'';
			$category= (sizeof($array_id)>4)?$array_id[4]:'';
			$subcategory = (sizeof($array_id)>5)?$array_id[5]:'';
			if($this->getRequest()->isPost())
			{
				$form      			= $this->getRequest()->getPost();
				$dzongkhag  		= $form['dzongkhag'];
				$gewog        		= $form['gewog'];
				$group        		= $form['group'];
				$material        	= $form['material'];
				$category        	= $form['category'];
				$subcategory        = $form['subcategory'];
				
			}else{
				$dzongkhag          = $dzongkhag;
				$gewog          	= $gewog;
				$group           	= $group;
				$material           = $material;
				$category           = $category;
				$subcategory  	    = $subcategory;
			}
			$data = array(
				'dzongkhag'  		=> $dzongkhag,
				'gewog'  			=> $gewog,
				'group'  		    => $group,
				'material'  		=> $material,
				'category'  		=> $category,
				'subcategory'       => $subcategory,
			);
			$lhakhangsectionTable = $this->getDefinedTable(Nangten\NangtenTable::class)->getAllReport($data);
			$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($lhakhangsectionTable));
				
			$page = 1;
			if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
			$paginator->setCurrentPageNumber((int)$page);
			$paginator->setItemCountPerPage(20);
			$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'     		=> 'Nangten Report',
			'paginator'         => $paginator,
			'page'              => $page,
			'data'      	    => $data,
			'dzongkhag'			=> $this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>1)),
			'gewog'				=> $this->getDefinedTable(Administration\BlockTable::class)->get(array('DzongkhagId'=>$data['dzongkhag'])),
			'group'				=> $this->getDefinedTable(Nangten\GroupTable::class)->get(array('status'=>1)),
			'category'			=> $this->getDefinedTable(Nangten\CategoryTable::class)->get(array('group'=>$data['group'])),
			'subcategory'		=> $this->getDefinedTable(Nangten\SubCategoryTable::class)->get(array('category'=>$data['category'])),
			'material'			=> $this->getDefinedTable(Nangten\MaterialTable::class)->getAll(),
		)); 
	}

	/**
	 * Get Category
	 */
	public function getcategoryAction()
	{
		$form = $this->getRequest()->getPost();
		$groupId = $form['groupId'];
		$categoryValue = "<option value=''></option>";
		$materialValue = "<option value=''></option>";
	
		$cateory = $this->getDefinedTable(Nangten\CategoryTable::class)->get(['group'=>$groupId]);
			foreach($cateory as $cateorys):
				$categoryValue .= "<option value='".$cateorys['id']."'>".$cateorys['category']."</option>";
			endforeach;	
			$material = $this->getDefinedTable(Nangten\MaterialTable::class)->get(['GroupId'=>$groupId]);
			foreach($material as $materials):
				$materialValue .= "<option value='".$materials['id']."'>".$materials['MaterialNameDz']."</option>";
			endforeach;	
		echo json_encode(array(
				'categoryValue' => $categoryValue,
				'materialValue' => $materialValue,
		));
		exit;
	}
	/**
	 * Get Sub Category
	 */
	public function getsubcategoryAction()
	{
		$form = $this->getRequest()->getPost();
		$categoryId = $form['categoryId'];
		$subcategoryValue = "<option value=''></option>";
	
		$details = $this->getDefinedTable(Nangten\SubCategoryTable::class)->get(['category'=>$categoryId]);
			foreach($details as $detail):
				$subcategoryValue .= "<option value='".$detail['id']."'>".$detail['sub_category']."</option>";
			endforeach;	
		echo json_encode(array(
				'subcategoryValue' => $subcategoryValue,
		));
		exit;
	}

	/**
	 * Get Gewog
	 */
	public function getgewogAction()
	{
		$form = $this->getRequest()->getPost();
		$dzongkhagId = $form['dzongkhagId'];
		$gewogValue = "<option value=''></option>";
	
		$details = $this->getDefinedTable(Administration\BlockTable::class)->get(['DzongkhagId'=>$dzongkhagId]);
			foreach($details as $detail):
				$gewogValue .= "<option value='".$detail['id']."'>".$detail['GewogNameDz']."</option>";
			endforeach;	
		echo json_encode(array(
				'gewogValue' => $gewogValue,
		));
		exit;
	}
}