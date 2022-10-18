<?php
namespace Administration\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;

class BlockTable extends AbstractTableGateway
{
	protected $table = 'adm_block'; //tablename

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
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	/**
	 * Return records of given condition array | given id
	 * @param Int $id
	 * @return Array
	 */
	public function getOgewog($param)
	{
		$where = ( is_array($param) )? $param: array('old_id' => $param);
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table)
		        ->where($where);
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	/**
	 * Return records of given condition array | given id
	 * @param Int $id
	 * @return Array
	 */
	public function getColumnValue($column,$value,$param=NULL)
	{
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);
			if($value != '-1'):
				$select->where(array($column =>$value));
			endif;
			if($param!=NULL):
				$select->where($param);
			endif;
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
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();          
		$columns ='';
		foreach ($results as $result):
			$columns =  $result[$column];
		endforeach; 
		
		return $columns;       
    }
	/**
     * Return column value of given where condition | id
     * @param Int|array $parma
     * @param String $column
     * @return String | Int
     */
    public function getOldGewog($param, $column)
    {         
		$where = ( is_array($param) )? $param: array('old_id' => $param);
		$fetch = array($column);
		$adapter = $this->adapter;       
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);
		$select->columns($fetch);
		$select->where($where);

		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();          
		$columns ='';
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
	    	$result = ($this->update($data, array('id'=>$id)))?$id:0;
	    } else {
	        $this->insert($data);
	    	$result = $this->getLastInsertValue(); 
	    }	    	    
	    return $result;	     
	}
	/**
     *  Delete a record
     *  @param int $id
     *  @return true | false
     */
	public function remove($id)
	{
		return $this->delete(array('id' => $id));
	}
	/**
	 * Return Min value of the column
	 * @param String $column
	 * @return String | Int
	 */
	public function getMin($column)
	{		
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);		
		$select->columns(array(
			'min' => new Expression('MIN('.$column.')')
		));
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();	
	
		foreach ($results as $result):
		  $column =  $result['min'];
		endforeach;
		
		return $column;
	}
	/**
	 * Return max value of the column
	 * @param String $column
	 * @return String | Int
	 */
	public function getMax($column)
	{
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);		
		$select->columns(array(
			'max' => new Expression('MAX('.$column.')')
		));
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();	
	
		foreach ($results as $result):
		  $column =  $result['max'];
		endforeach;
		
		return $column;
	}
	/**
	 * Return records of given condition array
	 * @param Int $column
	 * @param Int $param
	 * @return Array
	 */
	public function getMaxRow($column,$param)
	{
		$where = ( is_array($param) )? $param: array('id' => $param);
		$adapter = $this->adapter;
		
		$sub0 = new Select($this->table);
		$sub0->columns(array(
				$column => new Expression('MAX('.$column.')')
		));
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from(array('l'=>$this->table))
				->where($where)
				->where($column);
	
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	/**
	 * Return records of given condition array
	 * @param Int $column
	 * @param Int $param
	 * @return Array
	 */
	public function getMinRow($column,$param)
	{
		$where = ( is_array($param) )? $param: array('id' => $param);
		$adapter = $this->adapter;
	
		$sub0 = new Select($this->table);
		$sub0->columns(array(
				$column => new Expression('MIN('.$column.')')
		));
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from(array('l'=>$this->table))
			   ->where($where)
		       ->where($column);
	
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
}		