<?php
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Acl\Model\NotifyTable;

class Notificationhelper extends AbstractHelper
{
	private $notifyTable;
	private $_notifications = array();
	
	public function __construct(NotifyTable $notifyTable)
	{
		$this->notifyTable = $notifyTable;
	}
	
	public function __invoke($user_id)
	{  	
		$notifications = $this->notifyTable->getLimit(array('n.user'=>$user_id, 'n.flag'=>'0')); 
		$notificationCount = $this->notifyTable->getCount(array('user'=>$user_id, 'flag'=>'0'));
		foreach($notifications as $row):
		 	$this->_notifications[] = array(
			     'id'       => $row['id'],
	     		 'user'     => $row['user'],
				 'flag' 	=> $row['flag'],
				 'desc' 	=> $row['description'],
				 'route'    => $row['route'],
				 'action'   => $row['action'],
				 'created'  => $row['created'],
				 'key'      => $row['key'],
				 'priority' => $row['priority'],
				 'count'    => $notificationCount,
			 );
		endforeach;		
		return $this->_notifications;
	}
}
