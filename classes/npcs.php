<?php
class npcs extends Entity{
	public array $npcs;

	public function __construct(){
		$this->table = 'npcs';
		parent::__construct();
	}

	public function indexListings(int $factionid = null){
		$where = $bind = $return = array();
		if(!empty($factionid)){
			$where[] = 'npcs.faction = :factionid';
			$bind['factionid'] = $factionid;
		}
		$sql = 'SELECT npcs.id, npcs.code, npcs.name FROM npcs JOIN factions ON npcs.faction = factions.id';
		if(!empty($where)){
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$this->db->query($sql);
		if(!empty($bind)){
			foreach($bind as $bkey => $bval){
				$this->db->bind($bkey, $bval);
			}
		}
		$this->db->execute();
		if($this->db->rowCount() > 0){
			$return = $this->db->fetchAll();
			foreach($return as &$row){
				if(empty($row->name)){
					$row->name = $this->humanReadable($row->code);
				}
			}
		}
		$this->npcs = $return;
	}
}
?>