<?php
/*
 * 
 * Helper -- Status using label
 * 
 */
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Acl\Model\StatusTable;

class StatusHelper extends AbstractHelper
{
	private $statusTable;
	
	public function __construct(StatusTable $statusTable)
	{
		$this->statusTable = $statusTable;
	}
	
	public function __invoke($status,$style=NULL,$size=NULL)
	{

		foreach($this->statusTable->get($status) as $row);
		switch($status):
			case 0:
				echo "<span class='badge bg-danger'>Blocked</span>";
				break;
			default:
				echo "<span class='badge bg-".$row['label']."'>".$row['status']."</span>";
				break;
		endswitch;
	}
}
