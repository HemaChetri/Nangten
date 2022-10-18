<?php
namespace Acl\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Expression;

class AclTable extends AbstractTableGateway
{
	protected $table = 'sys_acl'; //tablename
	
	public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

	/**
	 * Return All records of table
	 * @return Array
	 */
	public function getAll()
	{
	    $adapter = $this->adapter;
	    $sql = new Sql($adapter);
	    $select = $sql->select();
	    $select->from($this->table)
	           ->order('tabindex ASC');
	    
	    $selectString = $sql->getSqlStringForSqlObject($select);
	    $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
	    return $results;
	}

    /**
	 * Return records of given id
	 * @param Int |Array $param
	 * @return Array
	 */
	public function get($param)
	{
		$where = ( is_array($param) )? $param: array('id' => $param);
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table)
		       ->where($where);
		$select->order('tabindex ASC');
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}

	/**
     * Return column value of given where condition | id
     * @param Int|array $parma
     * @param String $column
     * @return String | Int
     */
    public function getColumn($param, $column)
    {         
		$where = ( is_array($param) )? $param: array('id' => $param);
		$fetch = array($column);
		$adapter = $this->adapter;       
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);
		$select->columns($fetch);
		$select->where($where);
		$select->order('tabindex ASC');

		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray(); 
		$columns = '';
		foreach ($results as $result):
			$columns = $result[$column];
		endforeach; 
	  
	   return $columns;        
    }
    /**
	 * Save record
	 * @param String $array
	 * @return Int
	 */
	public function save($data)
	{
	    if ( !is_array($data) ) $data = $data->toArray();
	    $id = isset($data['id']) ? (int)$data['id'] : 0;
	    
	    if ( $id > 0 )
	    {
	    	$result = ($this->update($data, array('id'=>$id)))? $id:0;
	    } else {
	        $this->insert($data);
	    	$result = $this->getLastInsertValue(); 
	    }	    	    
	    return $result;    
	}
	/**
	 * Return All Highest Role for Menu
	 * @return Array
	 */
	public function getHighestRole()
	{
	    $adapter = $this->adapter;
	    $sql = new Sql($adapter);
	    $select = $sql->select();
	    $select->from(array('r'=>'sys_roles'));
		$select->columns(array('id' => new Expression('MAX(id)')));
	    
	    $selectString = $sql->getSqlStringForSqlObject($select);
	    $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
	    foreach ($results as $result):
			$columns = $result['id'];
		endforeach; 
		return $columns;
	}
	/**
	 * Return Role Specific Modules for Menu
	 * @return Array
	 */
	public function getModules($user_role,$highest_role=NULL)
	{	
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		
		$sub0 = $user_role;
		if(in_array($highest_role,$sub0)){
			$select = $sql->select();
			$select->from(array('m'=>'sys_modules'));
			$select->columns(array('module' => new Expression('DISTINCT(id)')));
			$select->order('id ASC');
		}else{
			$select = $sql->select();
			$select->from(array('m'=>'sys_modules'),array());
			$select->join(array('rm'=>'sys_role_module'),'m.id=rm.module',array(),Select::JOIN_LEFT);
			$select->columns(array('module' => new Expression('DISTINCT(m.id)')));
			$select->where
						->nest
							->in('rm.role', $sub0)
							->OR->in('m.general',array(1))			
						->unnest;
			$select->order('m.module ASC');
		}
	    $selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		//print_r($results); exit;
		return $results;
	}
	/**
	 * Returns the access control list to render role-specific menu system
	 * @return Zend_Db_Table_Rowset | Array
	 */
	public function renderMenu($user, $user_module=NULL, $highest_role=NULL)
	{
    	
		$role = $user->role;
		$user_id = $user->id;
		
		$columns = array('tabindex', 'controller', 'route', 'action', 'menu', 'role', 'dashboard', 'icon', 'description');
		
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		
		$sub0 = explode(',',$role);
		
		if(in_array($highest_role,$sub0)){
			$sub1 = new Select(array('a'=>'sys_acl'));
			$sub1->columns(array('acl' => new Expression('DISTINCT(a.id)')));
			$sub8 = array();
		}else{
			$sub1 = new Select(array('ra'=>'sys_role_acl'));
			$sub1->columns(array('acl' => new Expression('DISTINCT(ra.acl)')));
			$sub1->where->in('ra.role', $sub0);

			$sub7 = new Select(array('a'=>'sys_acl'));
			$sub7->columns(array('controller_tab' => new Expression('DISTINCT(SUBSTR(tabindex, 1, 2))')));
			$sub7->where->in('a.id', $sub1);

			$sub8 = new Select(array('a'=>'sys_acl'));
			$sub8->columns(array('acl' => new Expression('DISTINCT(a.id)')));
			$sub8->where->in('a.tabindex', $sub7);
			$sub8->where(array('a.display'=>'1','a.system'=>'1'));
			
		}
		$sub2 = new Select(array('a2'=>$this->table));
		$sub2->columns(array('tabindex' => new Expression('DISTINCT(SUBSTR(tabindex, 1, 1))')));
		$sub2->where
			->nest
				->where->in('a2.id', $sub1)
				->OR->in('a2.id', $sub8)
				->OR->in('a2.role', $sub0)
			->unnest;
		$sub2->where(array('a2.display'=>'1'));
		if($user_module!=NULL):
			$sub2->where->in('a2.resource', $user_module);
		endif;
		
		$sub3 = new Select(array('a3'=>$this->table));
		$sub3->columns(array('tabindex' => new Expression('DISTINCT(SUBSTR(tabindex, 1, 2))')));
		$sub3->where
			->nest
				->where->in('a3.id', $sub1)
				->OR->in('a3.id', $sub8)
				->OR->in('a3.role', $sub0)
			->unnest;
		$sub3->where(array('a3.display'=>'1'));
		if($user_module!=NULL):
			$sub3->where->in('a3.resource', $user_module);
		endif;
		
		$select = $sql->select();
		$select->from(array('a'=>$this->table));
		$select->columns($columns);
		$select->join(array('m'=>'sys_modules'), 'm.id = a.resource', array('module'));
		$select->where
			->nest
				->in('a.id', $sub1)
				->OR->in('a.role', $sub0)
				->OR->in('a.tabindex', $sub2)
				->OR->in('a.tabindex', $sub3)
			->unnest;
		$select->where(array('a.display'=>'1'));
		if($user_module!=NULL):
			$select->where->in('a.resource', $user_module);
		endif;
		$select->order('tabindex ASC');
		 
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	} 
	/**
	 * Returns the access control list to render role-specific button
	 * @return Zend_Db_Table_Rowset | Array
	 */
	public function renderButton($param, $user_role, $highest_role=NULL)
	{
    	$where = ( is_array($param) )? $param: array('id' => $param);
		$role = $user_role;
		$sub0 = explode(',',$role);
		
		$columns = array('id as acl','menu', 'action', 'route', 'icon', 'class', 'btn_type', 'btn_label', 'permission_level');
		
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		
		if(in_array($highest_role,$sub0)){
			$sub1 = new Select(array('a'=>'sys_acl'));
			$sub1->columns(array('acl' => new Expression('DISTINCT(id)')));
		}else{
			$sub1 = new Select(array('ra'=>'sys_role_acl'));
			$sub1->columns(array('acl' => new Expression('DISTINCT(acl)')));
			$sub1->where->in('role', $sub0);
		}
		
		$select = $sql->select();
		$select->from(array('a'=>$this->table));
		$select->columns($columns);
		$select->where($where);
		$select->where
			->nest
				->in('a.id', $sub1)
				->OR->in('a.role', $sub0)
			->unnest;
		 
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	} 
	/**
	 * Returns the access control list to render role-specific tabs
	 * @return Zend_Db_Table_Rowset | Array
	 */
	public function renderTabs($param, $user_role, $highest_role=NULL)
	{
    	$where = ( is_array($param) )? $param: array('id' => $param);
		$role = $user_role;
		$sub0 = explode(',',$role);
		
		$columns = array('id as acl_id','menu', 'action', 'route', 'icon','class');
		
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		
		if(in_array($highest_role,$sub0)){
			$sub1 = new Select(array('a'=>'sys_acl'));
			$sub1->columns(array('acl' => new Expression('DISTINCT(id)')));
		}else{
			$sub1 = new Select(array('ra'=>'sys_role_acl'));
			$sub1->columns(array('acl' => new Expression('DISTINCT(acl)')));
			$sub1->where->in('role', $sub0);
		}
		
		$select = $sql->select();
		$select->from(array('a'=>$this->table));
		$select->columns($columns);
		$select->where($where);
		$select->where
			->nest
				->in('a.id', $sub1)
				->OR->in('a.role', $sub0)
			->unnest;
		 
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	} 
	/**
	 * Return all the dashboard menu
	 * @param Array $userId
	 * @return Array
	 */
	public function renderDashboard($user,$highest_role=NULL)
	{
		$role = $user->role;
		$user_id = $user->id;
		
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		
		$sub0 = explode(',',$role);
		
		if(in_array($highest_role,$sub0)){
			$sub2 = new Select(array('m'=>'sys_modules'));
			$sub2->columns(array('module' => new Expression('DISTINCT(id)')));
		}else{
			$sub2 = new Select(array('m'=>'sys_modules'),array());
			$sub2->join(array('rm'=>'sys_role_module'),'m.id=rm.module',array(),Select::JOIN_LEFT);
			$sub2->columns(array('module' => new Expression('DISTINCT(m.id)')));
			$sub2->where
						->nest
							->in('rm.role', $sub0)
							->OR->in('m.general',array(1))			
						->unnest;
		}
		$select = $sql->select();
		$select->from(array('a'=>$this->table));
		$select->join(array('m'=>'sys_modules'), 'm.id = a.resource', array('module','color'));
		
		$select->where(array('a.dashboard'=>'1','a.display'=>'1'));
		$select->where->in('m.id', $sub2);
		$select->order('a.tabindex ASC');
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}

	/**
     *  Return Boolean
     *  @param int $id
     *  @return true | false
     */
	public function remove($id)
	{
		return $this->delete(array('id' => $id));
	}
	/**
     *  Return Array
     *  @param int $role
     *  @param int $resource
     *  @return Array
     */
	public function getAclPermission($resource,$process)
	{
		$adapter = $this->adapter;       
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table,array());
		$select->columns(array('process' => new Expression('DISTINCT(process)')));
		$select->where(array('resource' => $resource, 'system' => '0'));
		if($process != '-1'):
			$select->where(array('process' => $process));
		endif;
		$select->where->NotIn('process',array(0));
		$select->where->NotIn('process_action',array(0,10));
		$select->order('process ASC');
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	/**
     *  Return Array
     *  @param int $role
     *  @param int $resource
     *  @return Array
     */
	public function getRoleAcl($resource,$process,$role)
	{
		$adapter = $this->adapter;       
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table,array());
		$select->columns(array('role' => new Expression('DISTINCT(role)')));
		$select->where(array('resource' => $resource,'process' => $process,'system' => '0'));
		if($role != '-1'):
			$select->where(array('role' => $role));
		endif;
		$select->where->NotIn('process_action',array(0,10));
		$select->order('role ASC');
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	/**
     *  Return Array
     *  @param int $role
     *  @param int $resource
     *  @return Array
     */
	public function getProcessPermission($resource,$process)
	{
		$adapter = $this->adapter;       
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from(array('a'=>$this->table),array());
		$select->join(array('ra'=>'sys_role_acl'), 'a.id = ra.acl', array());
		$select->columns(array('process' => new Expression('DISTINCT(a.process)')));
		$select->where(array('a.resource' => $resource, 'system' => '0'));
		if($process != '-1'):
			$select->where(array('a.process' => $process));
		endif;
		$select->where->NotIn('a.process',array(0));
		$select->where->NotIn('a.process_action',array(0,10));
		$select->order('a.process ASC');
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	/**
     *  Return Array
     *  @param int $role
     *  @param int $resource
     *  @return Array
     */
	public function getRolePermission($resource,$process,$role)
	{
		$adapter = $this->adapter;       
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from(array('a'=>$this->table),array());
		$select->join(array('ra'=>'sys_role_acl'), 'a.id = ra.acl', array());
		$select->columns(array('role' => new Expression('DISTINCT(ra.role)')));
		$select->where(array('a.resource' => $resource,'a.process' => $process));
		if($role != '-1'):
			$select->where(array('ra.role' => $role));
		endif;
		$select->where->NotIn('a.process_action',array(0,10));
		$select->order('ra.role ASC');
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	/**
     *  Return Array
     *  @param int $role
     *  @param int $resource
     *  @return Array
     */
	public function getProcessActions($process)
	{
		$adapter = $this->adapter;       
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from(array('a'=>$this->table),array());
		$select->join(array('pa'=>'sys_process_action'),'a.process_action = pa.id', array('action'));
		$select->columns(array('action_id' => new Expression('DISTINCT(a.process_action)')));
		$select->where(array('a.process' => $process));
		$select->where->NotIn('a.process_action',array(0,10));
		$select->order('pa.id ASC');
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	/**
	* check particular row is present in the table 
	* with given column and its value
	* 
	*/
	public function isPresent($param)
	{
		$where = ( is_array($param) )? $param: array('id' => $param);
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table)
		       ->where($where);
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return (sizeof($results)>0)? 1:0;
	}
	/**
     *  Return Array
     *  @param int $role
     *  @param int $resource
     *  @return Array
     */
	public function getReports($resource,$report)
	{
		$process_action = array(10);
		$adapter = $this->adapter;       
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);
		$select->where(array('resource' => $resource));
		$select->where->In('process_action',$process_action);
		if($report != '-1'):
			$select->where(array('id' => $report));
		endif;
		$select->order('menu ASC');
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	} 	
}