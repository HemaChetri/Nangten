<?php
/*
 * 
 * Helper -- Get Resoucre with given route format
 * 
 * 
 */
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Acl\Model\AclTable;

class GetresourceHelper extends AbstractHelper
{
	protected $aclTable;
	
	public function __construct(AclTable $aclTable)
	{
		$this->aclTable = $aclTable;
	}
	
	public function __invoke($route)
	{
		$resourcecode = $this->aclTable->getColumn(array('route'=>$route),'resource');
		
		return $resourcecode;
	}
}
