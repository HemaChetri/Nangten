<?php
namespace Acl\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;

class AttachmentTable extends AbstractTableGateway
{
	protected $table = 'sys_attachment'; //tablename

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
	           ->order(array('id DESC'));
	    $selectString = $sql->getSqlStringForSqlObject($select);
	    $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
	    return $results;
	}
	
	/**
	 * Return records of given id
	 * @param Array $param
	 * @return Array
	 */
	public function get($param)
	{
		$where = ( is_array($param) )? $param: array('id' => $param);
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table)
		       ->where($where)
			   ->order(array('id DESC'));
		
		$selectString = $sql->getSqlStringForSqlObject($select);
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
	 * Return Count value of the column
	 * @param Array $where
	 * @return String | Int
	 */
	public function getCount($where = NULL)
	{
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table)
			->columns(array('count' => new Expression('COUNT(*)')));
		
		if($where != NULL):
			$select->where($where);
		endif;
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		
		foreach($results as $row);		
		return $row['count'];
	}
}
