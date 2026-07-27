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
		$sql = 'SELECT npcs.code, "NPC" AS type, npcs.name, factions.code AS faction, COUNT(dialogue.id) AS storiesCount FROM npcs JOIN factions ON npcs.faction = factions.id LEFT JOIN dialogue ON dialogue.npc = npcs.id';
		if(!empty($where)){
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$sql .= ' GROUP BY npcs.id ORDER BY npcs.code ASC';
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
				$row->name .= ' (' . $row->code . ')';
			}
		}
		$this->npcs = $return;
	}
}
?>