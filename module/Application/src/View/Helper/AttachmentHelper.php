<?php
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Acl\Model\AttachmentTable;

class Attachmenthelper extends AbstractHelper
{
	private $attachmentTable;
	private $_attachmentFile = array();
	
	public function __construct(AttachmentTable $attachmentTable)
	{
		$this->attachmentTable = $attachmentTable;
	}
	
	public function __invoke($process)
	{  
		$params = explode("-", $process);
		$process = $params['0'];
		$process_id = $params['1'];
		$attachments = $this->attachmentTable->get(array('process'=>$process,'process_id'=>$process_id));
		foreach($attachments as $row):
		 	$this->_attachmentFile[] = array(
			     'id'          => $row['id'],
	     		 'file_name'   => $row['attachment'],
				 'description' => $row['description'],
				 'author'      => $row['author'],
				 'modified'    => $row['modified'],
			 );
		endforeach;		
		return $this->_attachmentFile;
	}
}
