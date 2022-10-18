<?php
/*
 * Helper -- Canvas JS (Get Graphical Reports)
 * chophel@athang.com
 */
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Interop\Container\ContainerInterface;

class CanvasHelper extends AbstractHelper
{
	private $_container;
	public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }
	
	public function __invoke($chartID,$dataPoints,$colorSet,$axisY)
	{	
		echo <<<EOF
		<script>
		$(function () {
			var $chartID = new CanvasJS.Chart("$chartID", {
				theme: "light2",
				colorSet:  "$colorSet",
				exportEnabled: true,
				animationEnabled: true,
				legend: {
					verticalAlign: "bottom",
					horizontalAlign: "center"
				},
				axisY: {
					title: "$axisY",
				},
				data: [$dataPoints]
			});
			$chartID.render();
		});
		</script>
		EOF;
	}
}
