<?php
/*
 * Helper -- Decimal format
 */
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class Decimalhelper extends AbstractHelper
{
	public function __invoke($number)
	{
		if(is_numeric($number)):
			return (floor($number)==$number)?number_format($number, 0, '.', ','):number_format($number, 2, '.', ',');
		else:
			return $number;
		endif;
	}
}
