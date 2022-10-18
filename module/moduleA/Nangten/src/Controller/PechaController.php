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

class PechaController  extends AbstractActionController
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
	protected $_permission; // permission plugin
	
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

		$this->_permissionObj =  $this->PermissionPlugin();
		$this->_permission = $this->_permissionObj->permission($this->getEvent());
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
	 *  Pechha action
	 */
	public function pechaAction()
	{  
    	$this->init(); 
		$id = $this->_id;
			$array_id = explode("_", $id);
			$dzongkhag = $array_id[0];
			$category= (sizeof($array_id)>1)?$array_id[1]:'-1';
			$subcategory = (sizeof($array_id)>2)?$array_id[2]:'-1';
			if($this->getRequest()->isPost())
			{
				$form      			= $this->getRequest()->getPost();
				$dzongkhag  		= $form['dzongkhag'];
				$category        	= $form['category'];
				$subcategory        = $form['subcategory'];
			}else{
				$dzongkhag          = '-1';
				$category           = $category;
				$subcategory  	    = $subcategory;
			}
			$data = array(
				'dzongkhag'  		=> $dzongkhag,
				'category'  		=> $category,
				'subcategory'       => $subcategory,
			);
			$lhakhangsectionTable = $this->getDefinedTable(Nangten\NangtenTable::class)->getReport($data,array('TenGroupId'=>10));
			$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($lhakhangsectionTable));
				
			$page = 1;
			if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
			$paginator->setCurrentPageNumber((int)$page);
			$paginator->setItemCountPerPage(20);
			$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'     		=> 'Pechha',
			'paginator'         => $paginator,
			'page'              => $page,
			'data'      	    => $data,
			'dzongkhag'			=>$this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>1)),
			'category'			=>$this->getDefinedTable(Nangten\CategoryTable::class)->get(array('group'=>10)),
			'subcategory'		=>$this->getDefinedTable(Nangten\SubCategoryTable::class)->get(array('category'=>$data['category'])),
		)); 
	}
	/** 
	 * Generate Application No. (Ten Identification No.)
	 */
	public function generateApplicationNo($data)
	{
		$gewog_code = $data['gewog_code'];
		$lhakhang_code = $data['lhakhang_code'];
		$section = $data['section'];
		$type = $data['type'];
		$prefix_no =$gewog_code.'-'.$lhakhang_code.'-'.$section.'-'.$type.'-';
		$column = $data['column'];
		$tableObj = $data['tableObj'];
		$count=strlen($prefix_no);
		$results = $tableObj->getApplicationNo($prefix_no,$column);
		$array_list = array();
		foreach($results as $result): 
			array_push($array_list, substr($result[$column], $count)); 
		endforeach;
		
		$next_serial = (sizeof($array_list) > 0)?max($array_list) + 1:1;
		switch(strlen($next_serial)){
			case 1: $next_tr_serial = "".$next_serial; break;
			default: $next_tr_serial = $next_serial; break; 
		}
		return $application_no = $prefix_no.$next_tr_serial;
	}
	/** 
	 * Add pecha  Action
	 */
	public function addpechaAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getpost();	
			$app_no_data = array(
				'gewog_code'    => $form['GewogId'],
				'lhakhang_code' => $form['LhakhangId'],
				'section'       => $form['LhaSectionId'],
				'type'          => 10,
				'column'        => 'TenId',
				'tableObj'      => $this->getDefinedTable(Nangten\NangtenTable::class),
 			);
			$tenId = $this->generateApplicationNo($app_no_data);
			$DzongkhagNameDz=$this->getDefinedTable(Administration\DistrictTable::class)->getColumn($form['DzongkhagId'],'DzongkhagNameDz');
			$GewogNameDz=$this->getDefinedTable(Administration\BlockTable::class)->getColumn($form['GewogId'],'GewogNameDz');
			$VillageNameDz=$this->getDefinedTable(Administration\VillageTable::class)->getColumn($form['VillageId'],'VillageNameDz');
			$LhakhangNameDz=$this->getDefinedTable(Administration\LhakhangTable::class)->getColumn($form['LhakhangId'],'LhakhangNameDz');
			$LhaSectionNameDz=$this->getDefinedTable(Administration\LhakhangSectionTable::class)->getColumn($form['LhaSectionId'],'SectionNameDz');
			$TenGroupNameDz=$this->getDefinedTable(Nangten\GroupTable::class)->getColumn(10,'GroupNameDz');
			$data = [
				'DzongkhagId'	    	=>$form['DzongkhagId'],
				'DzongkhagNameDz'	    =>$DzongkhagNameDz,
				'GewogId'	    		=>$form['GewogId'],
				'GewogNameDz'	    	=>$GewogNameDz,
				'LhakhangId'	    	=>$form['LhakhangId'],
				'LhakhangNameDz'	    =>$LhakhangNameDz,
				'VillageDz'	    		=>$VillageNameDz,
				'VillageId'	            =>$form['VillageId'],
				'LhaSectionId'	   		=>$form['LhaSectionId'],
				'LhaSectionNameDz'	   	=>$LhaSectionNameDz,
				'LhaOwnerNameDz'	   	=>$form['LhaOwnerNameDz'],
				'TenId'	              	=>$tenId,
				'LhaBuilderDz'         	=>$form['LhaBuilderDz'],
				'LhaCenturyDz'       	=>$form['LhaCenturyDz'],
				'TenGroupId'      		=>10,
				'TenGroupNameDz'        =>$TenGroupNameDz,
				'TenDescriptionDz'      =>$form['TenDescriptionDz'],
				'TenFrontPageDz'        =>$form['TenFrontPageDz'],
				'TenPaperDz'      	    =>$form['TenPaperDz'],
				'TenCaligraphDz'      	=>$form['TenCaligraphDz'],
				'TenInkDz'      		=>$form['TenInkDz'],
				'TenPagesDz'      	    =>$form['TenPagesDz'],
				'TenAuthorDz'    		=>$form['TenAuthorDz'],
				'TenLengthdz'      		=>$form['TenLengthdz'],
				'TenSizeDz'      		=>$form['TenSizeDz'],
				'TenAgeDz'      		=>$form['TenAgeDz'],
				'TenCoverDz'      		=>$form['TenCoverDz'],
				'TenDongdarDz'      	=>$form['TenDongdarDz'],
				'TenBeltRingDz'       	=>$form['TenBeltRingDz'],
				'TenDesign'      		=>$form['TenDesign'],
				'TenVolumeDz'     		=>$form['TenVolumeDz'],
				'TenCostDz'        		=>$form['TenCostDz'],
				'TenConditionDz'        =>$form['TenConditionDz'],
				'TenIdentificationDz'   =>$form['TenIdentificationDz'],
				'TenStoryDz'   			=>$form['TenStoryDz'],
				'TenCaretakerDz'   		=>$form['TenCaretakerDz'],
				'TenCheckedByDz'   		=>$form['TenCheckedByDz'],
				'subcategory'      		=>$form['subcategory'],
				'category'      		=>$form['category'],
				'author'            	=> $this->_author,
				'created'           	=> $this->_created,
				'modified'          	=> $this->_modified
			];
			$this->_connection->beginTransaction();
			$result =$this->getDefinedTable(Nangten\NangtenTable::class)->save($data);
			if($result){
					$this->_connection->commit();
					$this->flashMessenger()->addMessage("success^ Successfully added new pechha details.");
					return $this->redirect()->toRoute('pecha', array('action' =>'pecha'));
				}else {
					$this->_connection->rollback(); 
					$this->flashMessenger()->addMessage("error^ Failed to add new pechha details.");
					return $this->redirect()->toRoute('pecha');
				}	
		}		
		return new ViewModel(array(
				'title'      =>'Add Pechha',
				'dzongkahg'	 =>$this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>1)),
				'gewog'		 =>$this->getDefinedTable(Administration\BlockTable::class)->get(array('status'=>1)),
				'lhakhang'	 =>$this->getDefinedTable(Administration\LhakhangTable::class)->getAll(),
				'village'	 =>$this->getDefinedTable(Administration\VillageTable::class)->getAll(),
				'section'	 =>$this->getDefinedTable(Administration\LhakhangSectionTable::class)->getAll(),
				'material'	 =>$this->getDefinedTable(Nangten\MaterialTable::class)->get(array('GroupId'=>10)),
				'category'	 =>$this->getDefinedTable(Nangten\CategoryTable::class)->get(array('group'=>10)),
				'subcategory'=>$this->getDefinedTable(Nangten\SubCategoryTable::class)->get(array('group'=>10)),
		));
		
	}
	/** 
	 * Edit Pechha  Action
	 */
	public function editpechaAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$app_no_data = array(
				'gewog_code'    => $form['GewogId'],
				'lhakhang_code' => $form['LhakhangId'],
				'section'       => $form['LhaSectionId'],
				'type'          => $form['TenGroupId'],
				'column'        => 'TenId',
				'tableObj'      => $this->getDefinedTable(Nangten\NangtenTable::class),
 			);
			$tenId = $this->generateApplicationNo($app_no_data);
			$form = $this->getRequest()->getpost();	
			$DzongkhagNameDz=$this->getDefinedTable(Administration\DistrictTable::class)->getColumn($form['DzongkhagId'],'DzongkhagNameDz');
			$GewogNameDz=$this->getDefinedTable(Administration\BlockTable::class)->getColumn($form['GewogId'],'GewogNameDz');
			$VillageNameDz=$this->getDefinedTable(Administration\VillageTable::class)->getColumn($form['VillageId'],'VillageNameDz');
			$LhakhangNameDz=$this->getDefinedTable(Administration\LhakhangTable::class)->getColumn($form['LhakhangId'],'LhakhangNameDz');
			$LhaSectionNameDz=$this->getDefinedTable(Administration\LhakhangSectionTable::class)->getColumn($form['LhaSectionId'],'SectionNameDz');
			$TenGroupNameDz=$this->getDefinedTable(Nangten\GroupTable::class)->getColumn($form['TenGroupId'],'GroupNameDz');
			$data = [
				'id'	    			=>$form['pecha_id'],
			    'DzongkhagId'	    	=>$form['DzongkhagId'],
				'DzongkhagNameDz'	    =>$DzongkhagNameDz,
				'GewogId'	    		=>$form['GewogId'],
				'GewogNameDz'	    	=>$GewogNameDz,
				'LhakhangId'	    	=>$form['LhakhangId'],
				'LhakhangNameDz'	    =>$LhakhangNameDz,
				'VillageDz'	    		=>$VillageNameDz,
				'VillageId'	            =>$form['VillageId'],
				'LhaSectionId'	   		=>$form['LhaSectionId'],
				'LhaSectionNameDz'	   	=>$LhaSectionNameDz,
				'LhaOwnerNameDz'	   	=>$form['LhaOwnerNameDz'],
				'TenId'	              	=>$tenId,
				'LhaBuilderDz'         	=>$form['LhaBuilderDz'],
				'LhaCenturyDz'       	=>$form['LhaCenturyDz'],
				'TenGroupId'      		=>10,
				'TenGroupNameDz'        =>$TenGroupNameDz,
				'TenDescriptionDz'      =>$form['TenDescriptionDz'],
				'TenFrontPageDz'        =>$form['TenFrontPageDz'],
				'TenPaperDz'      	    =>$form['TenPaperDz'],
				'TenCaligraphDz'      	=>$form['TenCaligraphDz'],
				'TenInkDz'      		=>$form['TenInkDz'],
				'TenPagesDz'      	    =>$form['TenPagesDz'],
				'TenAuthorDz'    		=>$form['TenAuthorDz'],
				'TenLengthdz'      		=>$form['TenLengthdz'],
				'TenSizeDz'      		=>$form['TenSizeDz'],
				'TenAgeDz'      		=>$form['TenAgeDz'],
				'TenCoverDz'      		=>$form['TenCoverDz'],
				'TenDongdarDz'      	=>$form['TenDongdarDz'],
				'TenBeltRingDz'       	=>$form['TenBeltRingDz'],
				'TenDesign'      		=>$form['TenDesign'],
				'TenVolumeDz'     		=>$form['TenVolumeDz'],
				'TenCostDz'        		=>$form['TenCostDz'],
				'TenConditionDz'        =>$form['TenConditionDz'],
				'TenIdentificationDz'   =>$form['TenIdentificationDz'],
				'TenStoryDz'   			=>$form['TenStoryDz'],
				'TenCaretakerDz'   		=>$form['TenCaretakerDz'],
				'TenCheckedByDz'   		=>$form['TenCheckedByDz'],
				'subcategory'      		=>$form['subcategory'],
				'category'      		=>$form['category'],
				'author'            	=> $this->_author,
				'created'           	=> $this->_created,
				'modified'          	=> $this->_modified
			];
			$this->_connection->beginTransaction();
			$result =$this->getDefinedTable(Nangten\NangtenTable::class)->save($data);
			if($result){
					$this->_connection->commit();
					$this->flashMessenger()->addMessage("success^ Successfully edited  pecha details.");
					return $this->redirect()->toRoute('pecha', array('action' =>'viewpecha','id'=>$form['pecha_id']));
				}else {
					$this->_connection->rollback(); 
					$this->flashMessenger()->addMessage("error^ Failed to edit pecha details.");
					return $this->redirect()->toRoute('pecha');
				}	
		}		
		return new ViewModel(array(
				'title'      => 'Edit Pechha',
				'dzongkhag'	 =>$this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>1)),
				'gewog'		 =>$this->getDefinedTable(Administration\BlockTable::class)->get(array('status'=>1)),
				'lhakhang'	 =>$this->getDefinedTable(Administration\LhakhangTable::class)->getAll(),
				'village'	 =>$this->getDefinedTable(Administration\VillageTable::class)->getAll(),
				'section'	 =>$this->getDefinedTable(Administration\LhakhangSectionTable::class)->getAll(),
				'material'	 =>$this->getDefinedTable(Nangten\MaterialTable::class)->get(array('GroupId'=>10)),
				'category'	 =>$this->getDefinedTable(Nangten\CategoryTable::class)->get(array('group'=>10)),
				'subcategory'=>$this->getDefinedTable(Nangten\SubCategoryTable::class)->get(array('group'=>10)),
				'pecha'	 =>$this->getDefinedTable(Nangten\NangtenTable::class)->get(['id'=>$this->_id]),
		));
		
	}
    /**
	 *upload Pechha image
	 */	
	public function addpechaimageAction()
	{	
		$this->init();	
		$fileManagerDir = $this->_config['file_manager']['public_dir']; 
        $this->_maxSize = $this->_config['file_manager']['maxSize'];
        $this->_fileExts = $this->_config['file_manager']['public_file_types'];
		if(!is_dir($fileManagerDir)) {
			mkdir($fileManagerDir, 0777);
		}
		$this->_dir =realpath($fileManagerDir);
		$form = $this->getRequest()->getpost();
		$request = $this->getRequest();
		if ($request->isPost()) {
			$data = array_merge_recursive(
					$request->getPost()->toArray(),
					$request->getFiles()->toArray()
			);
        	 
            $file = $data['image-file'];
			$ext = substr(strrchr($file['name'], "."), 1);
			$folder_name = 'pechha';
			if(in_array($ext, $this->_fileExts)){
				if($file['size'] > $this->_maxSize ){
					$this->flashMessenger()->addMessage("error^ File size is to large. System only accepts ".$this->_maxSize."KB");
				}else{
					$a= rand(0,10);
					$b=chr(rand(97,122));
					$c=chr(rand(97,122));
					$d= rand(0,11000);
					$e=chr(rand(97,122));
					$f= rand(0,10);
					$date = date('ym',strtotime(date('Y-m-d')));
					$file_name =  $date.$a.$b.$c.$d.$e.$f.'.' . $ext; //file path of the main picture
					
				    $upload_path = $this->_dir . DIRECTORY_SEPARATOR . $folder_name .DIRECTORY_SEPARATOR. $file_name;
					
					$result = rename($file['tmp_name'],  $upload_path);
				   if($result){						
						$attachments = array(
								'id'            => $form['pecha_id'],
								'TenImage'      => $file_name,
								'modified'      => $this->_modified,
						);
						$this->getDefinedTable(Nangten\NangtenTable::class)->save($attachments);					
						$this->flashMessenger()->addMessage("success^ Pechha Image Uploaded successfully");	
						return $this->redirect()->toRoute('pecha', array('action' =>'viewpecha','id'=>$form['pecha_id']));					
					}else{
						$this->flashMessenger()->addMessage("error^ Pechha Image Uploading not successfull");
						return $this->redirect()->toRoute('pecha');
					}
				}
			}
			else{
				$this->flashMessenger()->addMessage("error^ File extension with ".$ext." not supported");
			}		
			$redirectUrl = $this->getRequest()->getHeader('Referer')->getUri();
			return $this->redirect()->toUrl($redirectUrl);
		}
		$ViewModel = new ViewModel([
			'title'           => 'Upload Pechha Image',
			'pecha'        => $this->getDefinedTable(Nangten\NangtenTable::class)->get($this->_id),
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}

	/*
	 *  Delete Pechha  action
	 */
	public function deletepechaAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$pecha_id = $array_id[0];
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getpost();	
			$thankadtl = [				
				'id'      => $form['pecha_id'],
			];
			$this->_connection->beginTransaction();
		  if($thankadtl){
				$this->getDefinedTable(Nangten\NangtenTable::class)->remove($thankadtl);
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^ Successfully removed the pecha.");
			}else {
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^ Failed to remove the pecha.");
			}
			return $this->redirect()->toRoute('pecha', array('action' =>'pecha'));
		}		
		$ViewModel = new ViewModel([
			'title'      => 'Remove pecha',
			'pecha'    => $this->getDefinedTable(Nangten\NangtenTable::class)->get($pecha_id),
		]);		
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *  View pechha action
	 */
	public function viewpechaAction()
	{  
    	$this->init(); 
		 return new ViewModel(array(
			'title'     		=> 'View Pechha',
			'pecha'				=>$this->getDefinedTable(Nangten\NangtenTable::class)->get($this->_id),
			'catObj'			=>$this->getDefinedTable(Nangten\CategoryTable::class),
			'subcatObj'			=>$this->getDefinedTable(Nangten\SubCategoryTable::class),
		)); 
	}
	/******************************Get Functions*****************/
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
				$subcategoryValue .= "<option value='".$detail['subcat_id']."'>".$detail['sub_category']."</option>";
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
	/**
	 * Get Village
	 */
	public function getvillageAction()
	{
		$form = $this->getRequest()->getPost();
		$gewogId = $form['gewogId'];
		$villageValue = "<option value=''></option>";
		$lhakhangValue = "<option value=''></option>";
		$gewog_id= $this->getDefinedTable(Administration\BlockTable::class)->getColumn(['id'=>$gewogId],'old_id');
		$village_details = $this->getDefinedTable(Administration\VillageTable::class)->get(['GewogCode'=>$gewogId]);
			foreach($village_details as $village_detail):
				$villageValue .= "<option value='".$village_detail['id']."'>".$village_detail['VillageNameDz']."</option>";
			endforeach;	
		$lhakhang_details = $this->getDefinedTable(Administration\LhakhangTable::class)->get(['GewogId'=>$gewog_id]);
			foreach($lhakhang_details as $lhakhang_detail):
				$lhakhangValue .= "<option value='".$lhakhang_detail['id']."'>".$lhakhang_detail['LhakhangNameDz']."</option>";
			endforeach;	
		echo json_encode(array(
				'villageValue' => $villageValue,
				'lhakhangValue' => $lhakhangValue,
		));
		exit;
	}
	/**
	 * Get Section
	 */
	public function getsectionAction()
	{
		$form = $this->getRequest()->getPost();
		$lhaId = $form['lhaId'];
		$sectionValue = "<option value=''></option>";
		$details = $this->getDefinedTable(Administration\LhakhangSectionTable::class)->get(['LhakhangId'=>$lhaId]);
			foreach($details as $detail):
				$sectionValue .= "<option value='".$detail['id']."'>".$detail['SectionNameDz']."</option>";
			endforeach;	
		echo json_encode(array(
				'sectionValue' => $sectionValue,
		));
		exit;
	}
}