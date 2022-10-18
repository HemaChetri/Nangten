<?php
namespace Acl\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Expression;

class RolemoduleTable extends AbstractTableGateway
{
	protected $table = 'sys_role_module'; //tablename

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
	    $select->from(array('u'=>$this->table))
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
		$select->order('id ASC');
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}

	/**
	 * Return column value of given id
	 * @param Int $id
	 * @param String $column
	 * @return String | Int
	 */
	public function getColumn($id, $column)
	{   	   
		$fetch = array($column);
		$adapter = $this->adapter;  	 
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);
		$select->columns($fetch);
		$select->where(array('id' => $id));

		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();           
		
		foreach ($results as $result):
			$columns =  $result[$column];
		endforeach;
		return $columns;	    
	}
	
	/**
	 * int | int user module
	 * @return Boolean
	 */
	public function checkmapped($role, $module)
	{
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from(array('a'=>$this->table))
			   ->where(array('role' => $role, 'module'=>$module));
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
        		
		if(sizeof($results) > 0):
		    return true;
		else:
		   return false;
		endif;
	} 

    /**
	 * Save record
	 * @param String $array
	 * @return Int
	 */
	public function save($data)
	{
	    if ( !is_array($data) ) $data = $data->toArray();
	    $this->insert($data);
	    $result = $this->getLastInsertValue(); 
	    return $result;	     
	}
	
	/**
     *  Return Boolean
     *  @param int $id
     *  @return true | false
     */
    public function remove($data)
	{
		if ( !is_array($data) ) $data = $data->toArray();
		$role   = isset($data['role']) ? (int)$data['role'] : 0;
		$module = isset($data['module']) ? (int)$data['module'] : 0;
		return $this->delete(array('role'=> $role, 'module'=>$module));		
	}
}
