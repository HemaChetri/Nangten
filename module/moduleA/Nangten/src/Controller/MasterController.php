<?php
namespace Nangten\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Interop\Container\ContainerInterface;
use Acl\Model As Acl;
use Nangten\Model As Nangten;
use Administration\Model As Administration;
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
	}
	/**
	 *  index action
	 */
	public function indexAction()
	{
		$this->init();	
		return new ViewModel();
	}
	/**
	 *  Group action
	 */
	public function groupAction()
	{
		$this->init();
		$GroupTable = $this->getDefinedTable(Nangten\GroupTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($GroupTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'         => 'Category',
			'paginator'     => $paginator,
			'page'          => $page,
			
		)); 
	}
	/**
	 * Add Group action
	 */
	public function addgroupAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getpost();	
            $data = array(  
				'GroupNameEn'   	=> $form['GroupNameEn'],
				'GroupNameDz'   	=> $form['GroupNameDz'],
				'author'            => $this->_author,
				'created'           => $this->_created,
				'modified'          => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Nangten\GroupTable::class)->save($data);
            if($result):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully added new group");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to add new group");
            endif;
			return $this->redirect()->toRoute('ngmaster/paginator', array('action'=>'group','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title'         => 'Add group',
				'page'          => $page,
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *  Category action
	 */
	public function categoryAction()
	{
		$this->init();
		$componentTable = $this->getDefinedTable(Nangten\CategoryTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($componentTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'         => 'Category',
			'paginator'     => $paginator,
			'page'          => $page,
			'groupObj'		=>$this->getDefinedTable(Nangten\GroupTable::class),
		)); 
	}
	/** 
	 * Generate Application No. (Program Code)
	 */
	public function generateApplicationNo($data)
	{
		$service_code = $data['service_code'];
		$prefix_no = $service_code;
		$column = $data['column'];
		$tableObj = $data['tableObj'];
		
		$results = $tableObj->getApplicationNo($prefix_no,$column);
		$array_list = array();
		foreach($results as $result): 
			array_push($array_list, substr($result[$column], 1)); 
		endforeach;
		
		$next_serial = (sizeof($array_list) > 0)?max($array_list) + 1:1;
		switch(strlen($next_serial)){
			case 1: $next_tr_serial = "".$next_serial; break;
			default: $next_tr_serial = $next_serial; break; 
		}
		return $application_no = $prefix_no.$next_tr_serial;
	}
	/**
	 * Add Category action
	 */
	public function addcategoryAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getpost();	
			$app_no_data = array(
				'service_code'  => 'S',
				'column'        => 'code',
				'tableObj'      => $this->getDefinedTable(Nangten\CategoryTable::class),
 			);
			$code = $this->generateApplicationNo($app_no_data);
            $data = array(  
				'category'         => $form['category'],
				'code'             => $code,
				'group'        	   => $form['group'],
				'author'           => $this->_author,
				'created'          => $this->_created,
				'modified'         => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Nangten\CategoryTable::class)->save($data);
            if($result):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully added new category");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to add new category");
            endif;
			return $this->redirect()->toRoute('ngmaster/paginator', array('action'=>'category','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title'         => 'Add Category',
				'page'          => $page,
				'group'		    =>$this->getDefinedTable(Nangten\GroupTable::class)->get(array('status'=>1)),
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * Edit Category action
	 */
	public function editcategoryAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$category_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$app_no_data = array(
				'service_code'  => 'S',
				'column'        => 'code',
				'tableObj'      => $this->getDefinedTable(Nangten\CategoryTable::class),
 			);
			$code = $this->generateApplicationNo($app_no_data);
            $data = array(  
				'id'               => $form['category_id'],
				'category'         => $form['category'],
				'code'             => $code,
				'group'        	   => $form['group'],
				'status'           => $form['status'],
				'author'           => $this->_author,
				'modified'         => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Nangten\CategoryTable::class)->save($data);
            if($result > 0):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully edited Category");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to edit Category");
            endif;
			return $this->redirect()->toRoute('ngmaster/paginator', array('action'=>'category','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title'        => 'Edit Category',
				'page'         => $page,
				'category_id' => $category_id,
				'categoryObj' => $this->getDefinedTable(Nangten\CategoryTable::class),
				'group'		  =>$this->getDefinedTable(Nangten\GroupTable::class)->get(array('status'=>1)),
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *  Subcategory action
	 */
	public function subcategoryAction()
	{  
    	$this->init(); 
		$subcategory = $this->getDefinedTable(Nangten\SubCategoryTable::class)->getAll();
		$paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($subcategory));

		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'     => 'Sub-category',
			'paginator' => $paginator,
			'page'      => $page,
			'catObj' => $this->getDefinedTable(Nangten\CategoryTable::class),
		)); 
	}
	/** 
	 *  Add subcategory  Action
	 */
	public function addsubcategoryAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getpost();
			$data = [
			    'sub_category'	    => $form['sub_category'], 
			    'category'			=> $form['category'], 
				'author'            => $this->_author,
				'created'           => $this->_created,
				'modified'          => $this->_modified
			];
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Nangten\SubCategoryTable::class)->save($data);
			if($result){
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^ Successfully added new Sub-category.");
			}else {
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^ Failed to add new Sub-category.");
			}
			return $this->redirect()->toRoute('ngmaster/paginator', array('action' =>'subcategory', 'page'=>$this->_id, 'id'=>'0'));	
		}		
		$ViewModel = new ViewModel([
			'title'        => 'Add Sub-category',
			'page'         => $page,
			'categoryObj'  => $this->getDefinedTable(Nangten\CategoryTable::class),
			'category'     => $this->getDefinedTable(Nangten\CategoryTable::class)->getAll(),
			'group'		   =>$this->getDefinedTable(Nangten\GroupTable::class)->get(array('status'=>1)),
		]);	
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/** 
	 *  Edit subcategory Action
	 */
	public function editsubcategoryAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$subcategory_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getpost();	
			$data = [
				'id'			    => $form['subcategory_id'], 
				'sub_category'	    => $form['sub_category'], 
			    'category'			=> $form['category'], 
				'status'            => $form['status'],
				'author'            => $this->_author,
				'modified'          => $this->_modified
			];
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Nangten\SubCategoryTable::class)->save($data);
			if($result){
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^ Successfully edited Subcategory.");
			}else {
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^ Failed to edit Subcategory.");
			}
			return $this->redirect()->toRoute('ngmaster/paginator', array('action' =>'subcategory', 'page'=>$this->_id, 'id'=>'0'));	
		}		
		$ViewModel = new ViewModel([
			   'title'      	=> 'Edit UoM',
			   'page'       	=> $page,
			   'category'       => $this->getDefinedTable(Nangten\CategoryTable::class)->getAll(),
			   'group'		    =>$this->getDefinedTable(Nangten\GroupTable::class)->get(array('status'=>1)),
			   'subcategory' 	=> $this->getDefinedTable(Nangten\SubCategoryTable::class)->get($this->_id),
		]);		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *  Material action
	 */
	public function materialAction()
	{  
    	$this->init(); 
		$material = $this->getDefinedTable(Nangten\MaterialTable::class)->getAll();
		$paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($material));

		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'     => 'Material',
			'paginator' => $paginator,
			'page'      => $page,
			'groupObj' => $this->getDefinedTable(Nangten\GroupTable::class),
		)); 
	}
	/** 
	 *  Add material  Action
	 */
	public function addmaterialAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getpost();
			$data = [
			    'MaterialNameEn'	=> $form['MaterialNameEn'], 
				'MaterialNameDz'	=> $form['MaterialNameDz'], 
			    'GroupId'			=> $form['group'], 
				'author'            => $this->_author,
				'created'           => $this->_created,
				'modified'          => $this->_modified
			];
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Nangten\MaterialTable::class)->save($data);
			if($result){
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^ Successfully added new material.");
			}else {
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^ Failed to add new material.");
			}
			return $this->redirect()->toRoute('ngmaster/paginator', array('action' =>'material', 'page'=>$this->_id, 'id'=>'0'));	
		}		
		$ViewModel = new ViewModel([
			'title'        => 'Add Matrial',
			'page'         => $page,
			'categoryObj'  => $this->getDefinedTable(Nangten\CategoryTable::class),
			'category'     => $this->getDefinedTable(Nangten\CategoryTable::class)->getAll(),
			'group'		   =>$this->getDefinedTable(Nangten\GroupTable::class)->get(array('status'=>1)),
		]);	
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/** 
	 *  Edit material Action
	 */
	public function editmaterialAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$subcategory_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getpost();	
			$data = [
				'id'			    => $form['material_id'], 
				'MaterialNameEn'	=> $form['MaterialNameEn'], 
				'MaterialNameDz'	=> $form['MaterialNameDz'], 
			    'GroupId'		    => $form['group'],
				'status'			=> $form['status'],  
				'author'            => $this->_author,
				'created'           => $this->_created,
				'modified'          => $this->_modified
			];
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Nangten\MaterialTable::class)->save($data);
			if($result){
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^ Successfully edited material.");
			}else {
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^ Failed to edit material.");
			}
			return $this->redirect()->toRoute('ngmaster/paginator', array('action' =>'material', 'page'=>$this->_id, 'id'=>'0'));	
		}		
		$ViewModel = new ViewModel([
			   'title'      	=> 'Edit Material',
			   'page'       	=> $page,
			   'material'       => $this->getDefinedTable(Nangten\MaterialTable::class)->getAll(),
			   'group'		    =>$this->getDefinedTable(Nangten\GroupTable::class)->get(array('status'=>1)),
		]);		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * Get Category
	 */
	public function getcategoryAction()
	{
		$form = $this->getRequest()->getPost();
		$groupId = $form['groupId'];
		$categoryValue = "<option value=''></option>";
	
		$details = $this->getDefinedTable(Nangten\CategoryTable::class)->get(['group'=>$groupId]);
			foreach($details as $detail):
				$categoryValue .= "<option value='".$detail['id']."'>".$detail['category']."</option>";
			endforeach;	
		echo json_encode(array(
				'categoryValue' => $categoryValue,
		));
		exit;
	}
}