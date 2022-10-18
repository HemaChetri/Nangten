<?php
/*
 * Helper -- Currency format
 */
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class Currencyhelper extends AbstractHelper
{
	public function __invoke($number)
	{
		return 'Nu. '.number_format($number, 3, '.', ',');
	}
}
