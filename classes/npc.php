<?php
class npc extends entity{
	public int $id;
	public string $code;
	public string|null $name;
	public string|null $description;
	public int $faction;

	public function __construct(){
		$this->table = 'npcs';
		parent::__construct();
	}

	public function getIdFromCode(string $code, bool $load = false){
		$return = false;
		$sql = 'SELECT id, code, name, description FROM npcs WHERE npcs.code = :code LIMIT 1';
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