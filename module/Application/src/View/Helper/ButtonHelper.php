<?php
/**
 * Helper -- ButtonHelper
 * chophel@athang.com
 */
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Acl\Model\AclTable;
use Interop\Container\ContainerInterface;

class ButtonHelper extends AbstractHelper //implements ServiceLocatorAwareInterface 
{	
		
	protected $aclTable;
	private $_container;
	
	public function __construct(AclTable $aclTable, ContainerInterface $container)
	{
		$this->aclTable = $aclTable;
		$this->_container = $container;
	}
	
	public function __invoke($btn_type=NULL, $id=NULL, $status=NULL, $action_id=NULL, $status_array = NULL, $display=NULL)
	{  
		$routeMatch = $this->_container->get('Application')->getMvcEvent()->getRouteMatch();
		$routeName = $routeMatch->getMatchedRouteName();
		$arr = explode('/', $routeName);
		$routeName = $arr[0];
		$routeAction = $routeMatch->getParam('action');	
		$routeParamID = $routeMatch->getParam('id');
		$routeResource = $this->aclTable->getColumn(array('route'=>$routeName),'resource');
		$acl_id = $this->aclTable->getColumn(array('route'=>$routeName, 'resource' => $routeResource, 'action'=>$routeAction),'id');
		$user_id= $this->view->identity()->id;	
		$user_role= $this->view->identity()->role;	
		$user_roles = explode(',', $user_role);
		$highestRole = $this->aclTable->getHighestRole();
		$status = ($status==NULL)?'0':$status;
		switch($btn_type):
			case 1: /** Table Action Button */
				$button ="";
				foreach($this->aclTable->renderButton(array('parent_page' => $acl_id, 'button_position' => 1), $user_role,$highestRole) as $row):
					if($status == $row['permission_level']):
						$modal ="";
						if($row['btn_type']==2):
							$modal ="data-bs-toggle='modal' data-bs-target='#modal'";
						endif;
						$class="btn-info";
						if($row['class']!=""||$row['class']!=NULL):
							$class=$row['class'];						
						endif;
						$btn_label = ($row['btn_label']=='0')?'':$row['btn_label'];
						$button.="<span data-bs-toggle='tooltip' data-bs-placement='top' title='".$row['menu']."'>
							<a class='btn ".$class." btn-icon btn-sm' ".$modal." href='".$this->view->url($row['route'], array('action' => $row['action'], 'id' => $id))."' >
								<i class='icon-sm ".$row['icon']."'></i>".$btn_label." 
							</a></span>";
					endif;
				endforeach;
			break;
			case 2:/** Panel Head Button */
				$button ="";
				foreach($this->aclTable->renderButton(array('parent_page' => $acl_id, 'button_position' => 2), $user_role,$highestRole) as $row):
					if($status == $row['permission_level']):
						$modal ="";
						if($row['btn_type']==2):
							$modal ="data-bs-toggle='modal' data-bs-target='#modal'";
						endif;
						$class="btn-info";
						if($row['class']!=""||$row['class']!=NULL):
							$class="btn ".$row['class'];						
						endif;
						$btn_label = ($row['btn_label']=='0')?'':$row['btn_label'];
						$button.="<span data-bs-toggle='tooltip' data-bs-placement='top' title='".$row['menu']."'>
							<a class='".$class."' ".$modal." href='".$this->view->url($row['route'], array('action' => $row['action'], 'id'=>$id))."' >
								<i class='icon ".$row['icon']."'></i>".$btn_label."
							</a></span>";
					endif;
				endforeach;
			break;
			case 3: /** View Page Button */
				$button ="";
				foreach($this->aclTable->renderButton(array('parent_page' => $acl_id, 'button_position' => 3), $user_role,$highestRole) as $row):
					if($status == $row['permission_level']):
						$modal ="";
						if($row['btn_type']==2):
							$modal ="data-bs-toggle='modal' data-bs-target='#modal'";
						endif;
						$btn_label = ($row['btn_label']=='0')?'':$row['btn_label'];
						$button.="<span data-bs-toggle='tooltip' data-bs-placement='top' title='".$row['menu']."'>
							<a ".$modal." style='width: 100%' href=".$this->view->url($row['route'], array('action' => $row['action'], 'id' => $id))." class='btn ".$row['class']."'>
								<i class='icon ".$row['icon']."'></i>".$btn_label."
							</a></span>";
					endif;
				endforeach;
			break;
			case 4:/** Clickable Table Row */
				$href = "#";
				foreach($this->aclTable->renderButton(array('parent_page' => $acl_id, 'button_position' => 4), $user_role,$highestRole) as $row):
					if($status == $row['permission_level']):
						$href = $this->view->url($row['route'], array('action' => $row['action'], 'id' => $id));
					endif;
				endforeach;
				return $href;
			break;
			case 5: /** Buttons to check and uncheck */
				$check = "0";
				foreach($this->aclTable->renderButton(array('parent_page' => $acl_id, 'button_position' => 5), $user_role,$highestRole) as $row):
					if($status == $row['permission_level']):
						$check = "1";
					endif;
				endforeach;
				return $check;
			break;
			case 6: /** call Buttons with Button Action ID in different Parent Pages **/
				$check = "";
				if($action_id!=NULL):
					$status_array = ($status_array!=NULL)?$status_array:array(0);
					$display = ($display!=NULL)?$display:1;
					$button ="";
					foreach($this->aclTable->renderButton(array('a.id' => $action_id), $user_role,$highestRole) as $row):
						if(in_array($status,$status_array)):
							if($display==1):
								$modal ="";
								if($row['btn_type']==2):
									$modal ="data-bs-toggle='modal' data-bs-target='#modal'";
								endif;
								$class="btn-info";
								if($row['class']!=""||$row['class']!=NULL):
									$class="btn ".$row['class'];						
								endif;
								$btn_label = ($row['btn_label']=='0')?'':$row['btn_label'];
								$icon = ($row['btn_label']=='0')?'icon-sm':'icon';
								$button.="<span data-bs-toggle='tooltip' data-bs-placement='top' title='".$row['menu']."'>
									<a class='".$class."' ".$modal." title='".$row['menu']."' href='".$this->view->url($row['route'], array('action' => $row['action'], 'id'=>$id))."' >
										<i class='$icon ".$row['icon']."'></i>".$btn_label."
									</a></span>";
								return $button;
							else:
								$check = "1";
							endif;
						endif;
					endforeach;
				endif;
				return $check;
			break;
			default:
		endswitch;
		return $button;
	} 
}