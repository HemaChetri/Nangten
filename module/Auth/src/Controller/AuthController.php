<?php
namespace Auth\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter as AuthAdapter;
use Laminas\Session\Container;
use Laminas\Authentication\Result;
use Laminas\Mvc\MvcEvent;
use Administration\Model as Administration;

class AuthController extends AbstractActionController
{
	private $container;
    private $dbAdapter;
    protected $_password;// password plugin

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->dbAdapter = $this->container->get(DbAdapter::class);
    }

    public function getDefinedTable($table)
    {
        $definedTable = $this->container->get($table);
        return $definedTable;
    }

    public function indexAction()
    {
        $auth = new AuthenticationService();
		if($auth->hasIdentity()):
			return $this->redirect()->toRoute('home');
		else:
			return $this->redirect()->toRoute('auth', array('action' =>'login'));
		endif;
		
        return new ViewModel([
        	'title' => 'Login'
        ]);
    }
    /** 
     * Login 
     */
    public function loginAction()
    {
		$messages = null;
		$auth = new AuthenticationService();
        if($auth->hasIdentity() && $this->params()->fromRoute('id') != "NoKeepAlive"):
			 return $this->redirect()->toRoute('home');
        endif;
        if ($this->getRequest()->isPost()) 
		{
			$data = $this->getRequest()->getPost();
            $staticSalt = $this->password()->getStaticSalt();// Get Static Salt using Password Plugin
            if(filter_var($data['username'], FILTER_VALIDATE_EMAIL)):
                $identitycolumn = "email";
            else:
                $identitycolumn = "mobile";
            endif;
        
            $authAdapter = new AuthAdapter($this->dbAdapter,
                                           'sys_users', // there is a method setTableName to do the same
                                           $identitycolumn, // there is a method setIdentityColumn to do the same
                                           'password', // there is a method setCredentialColumn to do the same
                                           "SHA1(CONCAT('$staticSalt', ?, salt))" // setCredentialTreatment(parametrized string) 'MD5(?)'
                                          );            
            $authAdapter
                    ->setIdentity($data['username'])
                    ->setCredential($data['password'])
                ;
            $authService = new AuthenticationService();
            $result = $authService->authenticate($authAdapter);
            //echo"<pre>"; print_r($result); exit;
            switch ($result->getCode()) 
			{
                case Result::FAILURE_IDENTITY_NOT_FOUND:
                    // nonexistent identity
                    $this->flashMessenger()->addMessage("error^ A record with the supplied identity (username) could not be found.");
                    break;

                case Result::FAILURE_CREDENTIAL_INVALID:
                    // invalid credential
                    $this->flashMessenger()->addMessage("info^ Please check Caps Lock key is activated on your computer.");
                    $this->flashMessenger()->addMessage("error^ Supplied credential (password) is invalid, Please try again.");
                    break;

                case Result::SUCCESS:
                    $storage = $authService->getStorage();
                    $storage->write($authAdapter->getResultRowObject());
                    $role = $this->identity()->role;
                    $time = 1209600; // 14 days 1209600/3600 = 336 hours => 336/24 = 14 days
                    if ($data['rememberme']) {
                        $sessionManager = new \Laminas\Session\SessionManager();
                        $sessionManager->rememberMe($time);
                    }
                    $id = $this->identity()->id; 
                    $login = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($id, $column='logins');
                    
                    $data = array(
                            'id'         => $id,
                            'last_login' => date('Y-m-d H:i:s'),
                            'last_accessed_ip' => !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : ( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] ),
                            'logins' => $login + 1
                    ); 
                    $this->getDefinedTable(Administration\UsersTable::class)->save($data);
					//check whether user is block
					$status = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($id, $column='status');
					if($status == "9"){
					   return $this->redirect()->toRoute('auth', array('action' => 'logout', 'id'=>'1'));	
					}
                    $this->flashMessenger()->addMessage("info^ Welcome,</br>You have successfully logged in!");
                    return $this->redirect()->toRoute('home');
                break;

                default:
                    //other failure--- currently silent
                break;  
            }
            return $this->redirect()->toRoute('auth', array('action' => 'login'));
            
			if ( $this->params()->fromRoute('id') == "NoKeepAlive" ):
				$auth = new AuthenticationService();
				$auth->clearIdentity();
				$sessionManager = new \Laminas\Session\SessionManager();
				$sessionManager->forgetMe();
				$this->flashMessenger()->addMessage('warning^Your session has expired, please login again.');
			endif;
        }
        $ViewModel = new ViewModel(array(
			'title' => 'Log into System',
		));
		$ViewModel->setTerminal(false);
		return $ViewModel;
    }
    /**
     * Logout
     */
    public function logoutAction()
	{
        if(!$this->identity()){
	    	  $this->flashMessenger()->addMessage("warning^ Your session has already expired. Login in to proceed.");
	    	  return $this->redirect()->toRoute('auth', array('action' => 'login'));
	    }
		$auth = new AuthenticationService();
		$msg = $this->params()->fromRoute('id');
		$id = $this->identity()->id;   
		$data = array(
		    'id'          => $id,
			'last_logout' => date('Y-m-d H:i:s')	    
		); 
		
		$this->getDefinedTable(Administration\UsersTable::class)->save($data);

		if ($auth->hasIdentity()) {
			$identity = $auth->getIdentity();
		}			
		
		$auth->clearIdentity();
		$sessionManager = new \Laminas\Session\SessionManager();
		$sessionManager->forgetMe();
		
		if($msg == "1"):
		    $this->flashMessenger()->addMessage('warning^You cannot use the system as you are blocked. Contact the administrator.');
		else:
			$this->flashMessenger()->addMessage('info^You have successfully logged out!');
		endif;
		
		return $this->redirect()->toRoute('auth', array('action'=>'login'));
	}
    /**
	 * forgotpwd
	 */
 	public function forgotpwdAction()
    {
        $this->_password = $this->password();

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $userDtls = $this->getDefinedTable(Administration\UsersTable::class)->get(array('email' => $data['email']));
            if(sizeof($userDtls) == 0){
                $this->flashMessenger()->addMessage('error^ This email is not registered with any of the users in the system.');
                return $this->redirect()->toRoute('auth', array('action' => 'forgotpwd'));
            }else{
                foreach ($userDtls as $row);
                $id = $row['id']; $email = $row['email']; $name = $row['name'];
                $dynamicSalt = $this->_password->generateDynamicSalt();
                $staticSalt = $this->_password->getStaticSalt();
                $generatedPassword = $this->_password->generatePassword();
                $password = $this->_password->encryptPassword(
                    $staticSalt,
                    $generatedPassword,
                    $dynamicSalt
                );
                $udata = array(
                        'id'         => $id,
                        'password'   => $password,
                        'salt'	     => $dynamicSalt,
                        'modified'   => date('Y-m-d H:i:s'),
                );
                $result = $this->getDefinedTable(Administration\UsersTable::class)->save($udata);
                if($result > 0):
                    $notify_msg = "You have requested for password reset (Forgot Password). Please find your new password below: <br><br> New Password: ".$generatedPassword;
					$mail = array(
						'email'    => $data['email'],
						'name'     => $name,
						'subject'  => 'Nangten-DoC: Forgot Password', 
						'message'  => $notify_msg,
						'cc_array' => [],
					);
					//$this->EmailPlugin()->sendmail($mail);
                    
                    $this->flashMessenger()->addMessage("success^ Your password will be sent to your registered email, i.e. ".$email.". Please check in the spam folder if you can't find in the inbox. Thank You.");
                    return $this->redirect()->toRoute('auth', array('action' => 'login'));
                else:
                    $this->flashMessenger()->addMessage("error^ Not able to send your password. Try again.");
                    return $this->redirect()->toRoute('auth', array('action' => 'forgotpwd'));
                endif;
            }
        }
        $ViewModel = new ViewModel(array('title' => 'Forgot Password',));
        $ViewModel->setTerminal(false);
        return $ViewModel;
    }
}