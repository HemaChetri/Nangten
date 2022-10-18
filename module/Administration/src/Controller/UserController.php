<?php
namespace Administration\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Interop\Container\ContainerInterface;
use Laminas\Stdlib\ArrayObject;
use Laminas\Validator\File\Size;
use Laminas\Validator\File\Extension;
use Laminas\Mail;
use Administration\Model as Administration;
use Acl\Model as Acl;

class UserController extends AbstractActionController
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
    protected $_permission;// permission plugin
	protected $_password;// password plugin
    
	/**
	 * Laminas Default TableGateway
	 * Table name as the parameter
	 * returns obj
	 */
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
		if(!isset($this->_login_location_type)){
			$this->_login_location_type = $this->_user->location_type; 
		}
		
		$this->_id = $this->params()->fromRoute('id');
		
		$this->_created = date('Y-m-d H:i:s');
		$this->_modified = date('Y-m-d H:i:s');
		
		$this->_safedataObj = $this->safedata();
		$this->_password = $this->password();
		$this->_connection = $this->_container->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
		
		$this->_permissionObj =  $this->PermissionPlugin();
		$this->_permission = $this->_permissionObj->permission($this->getEvent());
	}
	/**
	 * index Action of User Controller
	 */
    public function indexAction()
    {  
    	$this->init(); 
		
        return new ViewModel(array(
            'title' => 'User Management',
		)); 
	}
	/**
	 * user Action -- view and manage all the users
	 */
    public function usersAction()
    {  
        $this->init();  
		//echo "<pre>"; print_r($this->_permission);exit;
		$userlists = $this->getDefinedTable(Administration\UsersTable::class)->getAll($this->_permission);
		
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($userlists));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(25);
		$paginator->setPageRange(8);
		return new ViewModel(array(
				'title'        => 'Users Management',
				'paginator'    => $paginator,
				'page'         => $page,
				'locationObj'  => $this->getDefinedTable(Administration\LocationTable::class),
		)); 
	}
	/**
	 * create new user Action
	 */
	public function createAction()
    {
    	$this->init();
		
		if($this->getRequest()->isPost()):
			$form = array_merge_recursive(
					$this->getRequest()->getPost()->toArray(),
					$this->getRequest()->getFiles()->toArray()
			);
			/** Generate Password **/
			$dynamicSalt = $this->_password->generateDynamicSalt();
			$staticSalt = $this->_password->getStaticSalt();
			$generatedPassword =  $this->_password->generatePassword();
			$password = $this->_password->encryptPassword($staticSalt, $generatedPassword, $dynamicSalt);

			$data = array(
				'cid'            => $form['cid'],
				'dob'            => $form['dob'],
				'name'           => $form['name'],
				'email'          => $form['email'],
				'mobile'         => $form['mobile'],
				'role'           => $form['role'],
				'password'       => $password,
				'salt'	         => $dynamicSalt,
				'location_type'  => $form['location_type'],
				'location'       => $form['location'],
				'status'         => '1',
				'author'         => $this->_author,
				'created'        => $this->_created,
				'modified'       => $this->_modified
			);
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
			if($result > 0):
				$notify_msg = "Your user account is created and registered in the system. Please find your sign in credentails below: <br><br>Username: ".$form['email']." or ".$form['mobile']."<br> Password: ".$generatedPassword;
				$mail = array(
					'email'    => $form['email'],
					'name'     => $form['name'],
					'subject'  => 'Nangten-DoC: New User Account Credentails', 
					'message'  => $notify_msg,
					'cc_array' => [],
				);
				//$this->EmailPlugin()->sendmail($mail);
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^ Successfully created new user and user password sent to ".$form['email']);	 	             
				return $this->redirect()->toRoute('user', array('action' => 'view', 'id'=>$result));
			else:
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^ Failed to create new user."); 
				return $this->redirect()->toRoute('user');
			endif;
		endif;

		return new ViewModel(array(
			'title'		      => 'Create New User',
			'roles'           => $this->_permissionObj->getrole(),
			'locationtypes'   => $this->_permissionObj->getlocationtype(),
		));  		
	}
	/**
	 * Get User CID Details Action
	 */
	public function getuserciddtlAction()
	{   
		if(!isset($this->_config)) {
			$this->_config = $this->_container->get('Config');
		}
		$form = $this->getRequest()->getPost();
		$cid_no = $form['cid'];
		
		$url = $this->_config['ditt_api_census'];
		$census_url = $url."citizenAPI/index.php";
		$data = array(
			'cid' => $cid_no,
		);
		$records = $this->ApiPlugin()->sendApiData($census_url,$data);
		if(isset($records)){
			foreach($records as $row);
			if(!empty ( $row['lastName'] )){
				$full_name = (isset($row['middleName']))?$row['firstName']." ".$row['middleName']." ".$row['lastName']:$row['firstName']." ".$row['lastName'];
			}else{
				$full_name = (isset($row['middleName']))?$row['firstName']." ".$row['middleName']:$row['firstName'];
			}
			$dob = strtr($row['dob'], '/', '-');
			$dob = date('Y-m-d', strtotime($dob));
			echo json_encode(array(
				'cid'     => $row['cid'],
				'name'    => $full_name,
				'dob'     => $dob,
			));
		}else{
			echo json_encode(array(
				'message' => "<span style='color:red;font-size:12px;'>". $cid_no. " does not exist in Census Database.</span>",
			));
		}
		exit;
	}
	/**
	 * Get locations via location type
	 */
	public function getlocationviatypeAction()
	{		
		$form = $this->getRequest()->getPost();
		$location_type = $form['location_type'];
		
		$locations = $this->getDefinedTable(Administration\LocationTable::class)->get(array('l.location_type'=>$location_type));
		
		$location = "<option value=''></option>";
		foreach($locations as $row1):
			$location.="<option value='".$row1['id']."'>".$row1['location']."</option>";
		endforeach;
		
		echo json_encode(array(
				'location' => $location,
		));
		exit;
	}
	/**
	 * check validity of a field action 
	 */
	public function getvalidfieldAction(){
		$form = $this->getRequest()->getPost();
		switch($form['type']):
			case 'cid':
				if($form['user_id']):
					$old_value = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($form['user_id'],'cid');
					if($form['cid'] == $old_value):
						$check = true;
					else:
						$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('cid',$form['cid']);
					endif;
				else:
					$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('cid',$form['cid']);
				endif;
			break;
			case 'email':
				if($form['user_id']):
					$old_value = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($form['user_id'],'email');
					if($form['email'] == $old_value):
						$check = true;
					else:
						$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('email',$form['email']);
					endif;
				else:
					$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('email',$form['email']);
				endif;
			break;
			case 'mobile':
				if($form['user_id']):
					$old_value = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($form['user_id'],'mobile');
					if($form['mobile'] == $old_value):
						$check = true;
					else:
						$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('mobile',$form['mobile']);
					endif;
				else:
					$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('mobile',$form['mobile']);
				endif;
			break;
			default:
				$check = false;
			break;
		endswitch;
		
		echo json_encode(array(
				'valid' => $check,
		));
		exit;
	}
	/**
	 * view Action-- to view detail of particular user
	 */
	public function viewAction()
	{  
	 	$this->init();
		
		return new ViewModel(array(
				'title'	          => 'View User Details',
				'users'           => $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id),
				'rolesObj'        => $this->getDefinedTable(Acl\RolesTable::class),
				'locationtypeObj' => $this->getDefinedTable(Administration\LocationTypeTable::class),
				'locationObj'     => $this->getDefinedTable(Administration\LocationTable::class),
		));
	}
	/**
	 * update user Action
	 */
	public function updateAction()
    {
    	$this->init();
		
		if($this->getRequest()->isPost()):
			$form = array_merge_recursive(
					$this->getRequest()->getPost()->toArray(),
					$this->getRequest()->getFiles()->toArray()
			);
			$data = array(
						'id'             => $form['user_id'],
						'cid'            => $form['cid'],
						'dob'            => $form['dob'],
						'name'           => $form['name'],
						'email'          => $form['email'],
						'mobile'         => $form['mobile'],
						'role'           => $form['role'],
						'location_type'  => $form['location_type'],
						'location'       => $form['location'],
						'author'         => $this->_author,
						'modified'       => $this->_modified
			);
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
			if($result > 0):	
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^ Successfully updated user details.");
			else:
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^ Failed to update user details.");
			endif;
			return $this->redirect()->toRoute('user', array('action' => 'view', 'id'=>$form['user_id']));
		endif;
		return new ViewModel(array(
			'title'		      => 'Update User Details',
			'users'           => $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id),
			'roles'           => $this->_permissionObj->getrole(),
			'locationtypes'   => $this->_permissionObj->getlocationtype(),
			'locations'       => $this->_permissionObj->getlocation(),
		));  		
	}
	/**
	 * changestatus Action -- activate or block the user
	 * has no view
	 **/
	public function changestatusAction()
	{
	    $this->init();
	    
		$pre_status = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($this->_id, $column='status'); 
		$status = ($pre_status == '1')?'0':'1';
		$data = array( 
			'id'		=> $this->_id,
			'status' 	=> $status,
		);
		$this->_connection->beginTransaction();
		$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
		$status_msg = ($status == '1')?'Active':'Blocked';
		if($result > 0):	
			$this->_connection->commit();
			$this->flashMessenger()->addMessage("success^ Successfully changed status to ".$status_msg.".");
		else:
			$this->_connection->rollback();
			$this->flashMessenger()->addMessage("error^ Failed to change user status.");
		endif;
		return $this->redirect()->toRoute('user',array('action'=>'users'));
	}
	
	/**
	 * changepwd Action -- to change user password
	 */
	public function changepwdAction()
	{
	    $this->init();
		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			$staticSalt = $this->_password->getStaticSalt();
			$old_password_db = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($form['user_id'], $column='password');
			$old_salt = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($form['user_id'], $column='salt');
			$old_password_form = $this->_password->encryptPassword(
					$staticSalt,
					$form['current_password'],
					$old_salt
			);
				
			if($old_password_db == $old_password_form):
				if($form['new_password'] == $form['confirm_password']):
					$dynamicSalt = $this->_password->generateDynamicSalt();
					$password = $this->_password->encryptPassword(
							$staticSalt,
							$form['new_password'],
							$dynamicSalt
					);
					$data = array(
							'id'		=> $form['user_id'],
							'password'	=> $password,
							'salt'		=> $dynamicSalt,
					);
					$this->_connection->beginTransaction();
					$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
					if($result > 0):	
						$this->_connection->commit();
						$this->flashMessenger()->addMessage("success^ Successfully changed user password");
					else:
						$this->_connection->rollback();
						$this->flashMessenger()->addMessage("error^ Failed to change user password.");
					endif;
				else:
					$this->flashMessenger()->addMessage("error^ New Password and Confirmed Password doesn't match.");
				endif;
			else:
				$this->flashMessenger()->addMessage("error^ The current password you have entered is incorrect.");
			endif;
			return $this->redirect()->toRoute('user', array('action' => 'view', 'id'=>$form['user_id']));
		endif; 
		$ViewModel = new ViewModel(array(
				'title' => 'Change Password',
				'users' => $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id),
		));
		$ViewModel->setTerminal(true);
		return $ViewModel;
	}
	/**
     * Confirm current password
     */
	public function checkpwdAction()
	{
		$this->init();
		$form = $this->getRequest()->getPost();
		$user_id = $form['user_id'];
		$password = $form['current_password'];
		$salt = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($user_id,'salt');
		$staticSalt = $this->_password->getStaticSalt();
		$encryptedPassword = $this->_password->encryptPassword($staticSalt, $password, $salt);

		$current_pwd = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($user_id, $column ="password");
		
		if($current_pwd==$encryptedPassword):
			$check = TRUE;
		else:
			$check = FALSE;
		endif;
		echo json_encode(array(
			'valid' => $check,
		));
		exit;
	}
	/**
	 * resetpwd Action -- to reset user password
	 * has no view
	 */
	public function resetpwdAction()
	{
		$this->init();
		
		$users = $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id);
		foreach($users as $row);
		$dynamicSalt = $this->_password->generateDynamicSalt();
		$staticSalt = $this->_password->getStaticSalt();
		$generatedPassword = $this->_password->generatePassword();
		$password = $this->_password->encryptPassword($staticSalt, $generatedPassword, $dynamicSalt); 
			
		$data = array(
			'id'		=> $this->_id,
			'password' 	=> $password,
			'salt'	    => $dynamicSalt,
		);
		$this->_connection->beginTransaction();
		$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
		if($result > 0):
			$notify_msg = "You have requested for password reset. Please find your new password below: <br><br> New Password: ".$generatedPassword;
			$mail = array(
				'email'    => $row['email'],
				'name'     => $row['name'],
				'subject'  => 'Nangten-DoC: Password Reset', 
				'message'  => $notify_msg,
				'cc_array' => [],
			);
			//$this->EmailPlugin()->sendmail($mail);
			$this->_connection->commit();
			$this->flashMessenger()->addMessage("success^ Successfully reset the user password and new password sent to ".$row['email']);
		else:
			$this->_connection->rollback();
			$this->flashMessenger()->addMessage("error^ Failed to reset user password.");
		endif;
		return $this->redirect()->toRoute('user', array('action' => 'view', 'id'=>$this->_id));
	}
	/**
	 * user activity of the user
	 */
	public function useractivityAction()
	{
		$this->init();
		$activitylogs = $this->getDefinedTable(Acl\ActivityLogTable::class)->get(array('author' => $this->_id));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($activitylogs));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(25);
		$paginator->setPageRange(8);
		return new ViewModel(array(
				'title'          => 'Recent Activity',
				'paginator'      => $paginator,
				'page'           => $page,
				'processObj'     => $this->getDefinedTable(Acl\ProcessTable::class),
				'users'          => $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id),
		));
	}
	/**
	 * notifications of the user
	 */
	public function notificationAction()
	{
		$this->init();
		$notifications = $this->getDefinedTable(Acl\NotifyTable::class)->get(array('n.user' => $this->_id));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($notifications));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(25);
		$paginator->setPageRange(8);
		return new ViewModel(array(
				'title'          => 'Notifications',
				'paginator'      => $paginator,
				'page'           => $page,
				'processObj'     => $this->getDefinedTable(Acl\ProcessTable::class),
				'users'          => $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id),
		));
	}
}