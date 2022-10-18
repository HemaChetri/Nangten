<?php
namespace Acl\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Expression;

class RoleaclTable extends AbstractTableGateway
{
	protected $table = 'sys_role_acl'; //tablename

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
	    $select->from($this->table);
	    
	    $selectString = $sql->getSqlStringForSqlObject($select);
	    $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
	    return $results;
	}
    /**
	 * Return records of given condition array | given id
	 * @param Int $id
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

		$selectString = $sql->getSqlStringForSqlObject($select);
		//echo $selectString;exit;
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();          
        
		$columns = '';
		foreach ($results as $result):
			$columns =  $result[$column];
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
     *  Delete a record
     *  @param int $id
     *  @return 1 | 0
     */
	public function remove($id)
	{
		return ($this->delete(array('id' => $id)))? 1:0;
	}
	/**
	* check particular row is present in the table 
	* with given @param
	*/
	public function isPresent($param)
	{
		$where = ( is_array($param) )? $param: array('id' => $param);
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from(array('ra'=>$this->table),array());
		$select->join(array('a'=>'sys_acl'),'a.id = ra.acl', array())
		       ->where($where);
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return (sizeof($results)>0)? 1:0;
	} 
}
