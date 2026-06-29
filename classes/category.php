<?php
class category extends Entity{
	public int $id;
	public string $name;
	public int $descriptor = 0;

	public function __construct(){
		$this->table = 'categories';
		parent::__construct();
	}

	public function loadCategory(int $id){
		$sql = 'SELECT ' . implode(', ', $this->prependColumns()) . ' FROM  ' . $this->table . ' WHERE ' . $this->table . '.id = :id LIMIT 1';
		$this->db->query($sql);
		$this->db->bind('id', $id);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$row = $this->db->fetch();
			foreach($row as $key => $val){
				$this->$key = $val;
			}
		}
	}

	public function getIdFromName(string $name){
		$return = false;
		$sql = 'SELECT ' . $this->table . '.id, ' . $this->table . '.name, ' . $this->table . '.descriptor FROM ' . $this->table . ' WHERE ' . $this->table . '.name = :name LIMIT 1';
		$this->db->query($sql);
		$this->db->bind('name', $name);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$row = $this->db->fetch();
			$this->id = $row->id;
			$this->name = $row->name;
			$this->descriptor = $row->descriptor;
			$return = $this->id;
		}
		return $return;
	}
}
?>