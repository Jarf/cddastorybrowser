<?php
class faction extends Entity{
	public int $id;
	public string $code;
	public string $name;
	public string $description;

	public function __construct(){
		$this->table = 'factions';
		parent::__construct();
	}

	public function getIdFromCode(string $code, bool $load = false){
		$return = false;
		$sql = 'SELECT id, code, name, description FROM factions WHERE factions.code = :code LIMIT 1';
		$this->db->query($sql);
		$this->db->bind('code', $code);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$row = $this->db->fetch();
			$return = $row->id;
			if($load === true){
				foreach($row as $key => $val){
					if(!empty($val)){
						$this->$key = $val;
					}
				}
			}
		}
		return $return;
	}
}
?>