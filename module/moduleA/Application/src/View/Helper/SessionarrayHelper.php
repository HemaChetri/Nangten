<?php
namespace Application\View\Helper;
use Laminas\View\Helper\AbstractHelper;
use Application\Controller\Plugin\SessionArrayPlugin;

class SessionarrayHelper extends AbstractHelper
{
         
	public function __invoke($key)
	{     
		$sessionArray = new SessionArrayPlugin();
	    $sessionContents = $sessionArray->getContents($key); 
	    foreach ($sessionContents as $content);
	    return $content;   
	}
}
