<?php
class factions extends Entity{
	public array $factions;

	public function __construct(){
		$this->table = 'factions';
		parent::__construct();
	}

	public function indexListings(int $factionid = null){
		$where = $bind = $return = array();
		if(!empty($categoryid)){
			$where[] = 'factions.id = :factionid';
			$bind['factionid'] = $factionid;
		}
		$sql = 'SELECT factions.id, factions.code, factions.description, factions.name, "Dialogue" AS type, COUNT(dialogue.id) AS storiesCount FROM factions LEFT JOIN npcs ON npcs.faction = factions.id LEFT JOIN dialogue ON dialogue.npc = npcs.id';
		if(!empty($where)){
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$sql .= ' GROUP BY factions.id ORDER BY factions.name ASC';
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
				$row->nameReadable = $this->humanReadable($row->name);
			}
		}
		$this->factions = $return;
	}
}
?>