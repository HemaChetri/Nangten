<?php
namespace Acl\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Interop\Container\ContainerInterface;
use Laminas\Mvc\MvcEvent;
use Acl\Model as Acl;

class UploadController extends AbstractActionController
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
   	        $this->redirect()->toRoute('application', array('action' => 'index'));
		endif;		
		$this->_config = $this->_container->get('Config');
		$this->_user = $this->identity();
		$this->_login_id = $this->_user->id;
		$this->_login_role = $this->_user->role;
		$this->_author = $this->_user->id;		
		$this->_id = $this->params()->fromRoute('id');
		$this->_created = date('Y-m-d H:i:s');
		$this->_modified = date('Y-m-d H:i:s');		
        $fileManagerDir = $this->_config['file_manager']['dir']; 
        $this->_maxSize = $this->_config['file_manager']['maxSize'];
        $this->_fileExts = $this->_config['file_manager']['file_types'];
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
		return new ViewModel(['title' => 'Acl Menu',]);
	}
	/**
	 *  Upload files action
	 */	
	public function uploadFormAction()
	{	
		$this->init();
		$request = $this->getRequest();
		if ($request->isPost()) {
			$data = array_merge_recursive(
				$request->getPost()->toArray(),
				$request->getFiles()->toArray()
			);
        	$params = explode("-", $data['application']);
			$process = $params['0'];
			$process_id = $params['1'];
			$applicationNO = $params['2'];
			$folder_name = $this->getDefinedTable(Acl\ProcessTable::class)->getColumn($process, $column='file_folder');
            $file = $data['uploadfile'];
			$ext = substr(strrchr($file['name'], "."), 1);
			$ext = strtolower($ext);
			
			if(in_array($ext, $this->_fileExts)){
				if($file['size'] > $this->_maxSize ){
					$this->flashMessenger()->addMessage("error^ File size is to large. System only accepts ".$this->_maxSize."KB.");
				}else{
					$a= rand(0,10);
					$b=chr(rand(97,122));
					$c=chr(rand(97,122));
					$d= rand(0,11000);
					$e=chr(rand(97,122));
					$f= rand(0,10);
					$date = date('ym',strtotime(date('Y-m-d')));
					$file_name =  $date.$a.$b.$c.$d.$e.$f.'.' . $ext; //file path
					
					if(!is_dir($this->_dir . DIRECTORY_SEPARATOR . $folder_name .DIRECTORY_SEPARATOR. $applicationNO,0755,true)) {						
						mkdir($this->_dir . DIRECTORY_SEPARATOR . $folder_name .DIRECTORY_SEPARATOR. $applicationNO,0755,true);
						chmod($this->_dir . DIRECTORY_SEPARATOR . $folder_name .DIRECTORY_SEPARATOR. $applicationNO, 0755);
					}
					
				    $upload_path = $this->_dir . DIRECTORY_SEPARATOR . $folder_name .DIRECTORY_SEPARATOR. $applicationNO .DIRECTORY_SEPARATOR. $file_name;
					$result = rename($file['tmp_name'],  $upload_path);
					chmod($upload_path, 0755);
					
					if($result){						
						$attachments = array(
							'process'          => $process,
							'process_id'       => $process_id,
							'original_filename'=> $applicationNO,
							'attachment'       => $file_name,
							'description'      => $data['filename'],
							'status'           => '1',
							'author'           => $this->_author,
							'created'          => $this->_created,
							'modified'         => $this->_modified,
						);
						$this->getDefinedTable(Acl\AttachmentTable::class)->save($attachments);						
						$this->flashMessenger()->addMessage("success^ File successfully uploaded.");						
					}else{
						$this->flashMessenger()->addMessage("error^ File uploading failed. Please check file size or file type.");
					}
				}
			}
			else{
				$this->flashMessenger()->addMessage("error^ File extension with ".$ext." not supported. Please upload the allowed file type.");
			}		
			$redirectUrl = $this->getRequest()->getHeader('Referer')->getUri();
			return $this->redirect()->toUrl($redirectUrl);
		}
		$viewModel =  new ViewModel(array(
			'title'          => 'Attach',
			'application_id' => $this->_id,
		));
		$viewModel->setTerminal(true);
		return $viewModel; 
	}
	
	/**
	 * remove attach
	 */
	public function removeAction()
	{
		$this->init();

		$id = $this->params()->fromRoute('id');
		$attachment_details = $this->getDefinedTable(Acl\AttachmentTable::class)->get($id);
		if(sizeof($attachment_details) > 0 ):
			foreach ($attachment_details as $dtl);
			
			$process = $dtl['process'];
			$process_id = $dtl['process_id'];
			$original_filename = $dtl['original_filename'];
			$attachment_name = $dtl['attachment'];
			$description = $dtl['description'];
				
			$file_count = $this->getDefinedTable(Acl\AttachmentTable::class)->getCount('id',array('process'=>$process,'process_id'=>$process_id));
			
			$folder_name = $this->getDefinedTable(Acl\ProcessTable::class)->getColumn($process, $column='file_folder');
			$file_link = $this->_dir.DIRECTORY_SEPARATOR.$folder_name.DIRECTORY_SEPARATOR.$original_filename;
			$physical_count = count(scandir($file_link)) - 2;
			$fileName = $this->_dir.DIRECTORY_SEPARATOR.$folder_name.DIRECTORY_SEPARATOR.$original_filename.DIRECTORY_SEPARATOR.$attachment_name;
			
			if ( (file_exists($fileName) && is_readable($fileName))):
				if( !@is_dir($fileName) ):
					@unlink($fileName);
				endif;			   
			endif;
			
			if($physical_count == 1 && $file_count == 1):
				rmdir($file_link);
			endif;
			
			$this->getDefinedTable(Acl\AttachmentTable::class)->remove($id);
			$this->flashMessenger()->addMessage("success^ Attachment successfully removed.");
		else:
		   $this->flashMessenger()->addMessage("error^ Attachment unable to remove.");
		endif;
		
		$redirectUrl = $this->getRequest()->getHeader('Referer')->getUri();
		return $this->redirect()->toUrl($redirectUrl);	
	
	}
	
	/**
	 * @return file 
	 * @usage documents are kept at highly secured non-public locations and 
	 * this method reads and renders the document over the browser
	 */
	public function renderAction()
	{
		$this->init();

		$attachment_details = $this->getDefinedTable(Acl\AttachmentTable::class)->get($this->_id);
		
		foreach ($attachment_details as $dtl);
		$process = $dtl['process'];
		$process_id = $dtl['process_id'];
		$original_filename = $dtl['original_filename'];
		$attachment_name = $dtl['attachment'];
		$description = $dtl['description'];
		
		$folder_name = $this->getDefinedTable(Acl\ProcessTable::class)->getColumn($process, $column='file_folder');
		
		$file = $this->_dir.DIRECTORY_SEPARATOR.$folder_name.DIRECTORY_SEPARATOR.$original_filename.DIRECTORY_SEPARATOR.$attachment_name;	
		echo $this->__file($file, substr($description,0,20));
	
		return;
	}
	
	/**
	 * mime and load file
	 */
	protected function __file($file, $name)
	{
		/** check if file exists */
		if ( !(file_exists($file) && is_readable($file)) ):
		header("HTTP/1.0 404 Not Found");
		echo "<h3>Error 404 | File Not Found</h3><hr />";
		echo basename($file);	
		return;
		endif;

	 	/** mime */
		$path = pathinfo($file);
		$ext = $path['extension']; 
	   	switch ($path['extension']):
			case 'png':
			case 'jpg':
			case 'jpeg':
			case 'gif':
					$header = 'image/png';
					break;
			case 'pdf':
					$header = 'application/pdf';
					break;
			case 'xls':
			case 'xlsx':
					$header = 'application/octet-stream';
					break;
			case 'doc':
			case 'docx':
					$header = 'application/word';
					break;
			case 'txt':
					$header = 'text/plain';
					break;
		endswitch;
	     
		header('Content-Type: '.$header);
		header('Content-Disposition: attachment; filename="' . $name .'.'.$ext. '"');
		readfile($file);
	}
}