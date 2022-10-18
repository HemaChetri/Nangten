<?php
/*
 * 
 * Helper -- Date Format Helper 
 * Date (Day-Month-Year) 
 * 
 */
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class DateFormatHelper extends AbstractHelper
{	
	public function __invoke($date,$format=NULL)
	{	
		if($format==1){
			$dated = strtotime($date); 
			$fdated = date('F j, Y', $dated);
			return $fdated; 
		}else{
			return ($date == '0000-00-00')? '00-00-0000': date("d-m-Y", strtotime($date));
		}
	}
}
