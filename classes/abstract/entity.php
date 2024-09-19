<?php
class Entity{
	protected int $id;
	protected db $db;
	protected string $table;
	protected array $tables;
	protected array $columns;

	protected function __construct(){
		$this->db = new db();
		$this->getTables();
		if(!in_array($this->table, $this->tables)){
			exit();
		}
		$this->getColumns();
	}

	public function selectAll(){
		$return = array();
		$sql = 'SELECT * FROM ' . $this->table;
		$this->db->query($sql);
		$this->db->execute();
		if($this->db->rowCount() > 0){
			$return = $this->db->fetchAll();
		}
		return $return;
	}

	public function saveChanges(){
		$keys = $vals = $binds = array();
		foreach($this->columns as $colname){
			if(isset($this->$colname)){
				$keys[] = $colname;
				$vals[] = $this->$colname;
				$binds[$colname] = $this->$colname;
			}
		}

		if(!empty($keys) && !empty($vals)){
			if(!isset($this->id) || empty($id)){
				$sql = 'INSERT INTO ' . $this->table . '(' . implode(',', $keys) . ') VALUES (:' . implode(',:',array_keys($binds)) . ')';
				$this->db->query($sql);
				foreach($binds as $bkey => $bval){
					$this->db->bind($bkey, $bval);
				}
				$this->db->execute();
				$this->id = $this->db->lastInsertId();
			}else{
				$updates = array();
				foreach($binds as $bkey => $bval){
					$updates[] = $bkey . ' = :' . $bkey;
				}
				$sql = 'UPDATE ' . $this->table . ' SET ' . implode(',', $updates);
				$this->db->query($sql);
				foreach($binds as $bkey => $bval){
					$this->db->bind($bkey, $bval);
				}
				$this->db->execute();
			}
		}
	}

	protected function prependColumns(){
		$return = array();
		foreach($this->columns as $colname){
			$return[] = $this->table . '.' . $colname;
		}
		return $return;
	}

	protected function humanReadable(string $string){
		return ucwords(trim(preg_replace('/[^A-Za-z0-9]/', ' ', $string)));
	}

	protected function sanitizeString(string $string){
		$string = strip_tags($string);
		$string = trim(preg_replace('/[^A-Za-z0-9]/', ' ', $string));
		return strip_tags($string);
	}

	private function getTables(){
		$sql = 'SELECT table_name FROM information_schema.tables';
		$this->db->query($sql);
		$this->db->execute();
		if($this->db->rowCount() > 0){
			$return = $this->db->fetchAll();
			foreach($return as $row){
				$this->tables[] = $row->TABLE_NAME;
			}
		}
	}

	private function getColumns(){
		$sql = 'DESCRIBE ' . $this->table;
		$this->db->query($sql);
		$this->db->execute();
		if($this->db->rowCount() > 0){
			$return = $this->db->fetchAll();
			foreach($return as $row){
				$this->columns[] = $row->Field;
			}
		}
	}
}
?>