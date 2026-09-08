<?php
class dialogues extends Entity{
	public array $dialogues;

	public function __construct(){
		$this->table = 'dialogue';
		parent::__construct();
	}

	public function indexListings(string $factioncode, string $npccode){
		$return = array();
		$sql = 'SELECT dialogue.id, dialogue.code, dialogue.dialogue, factions.code AS factioncode, npcs.code as npccode FROM dialogue JOIN npcs ON dialogue.npc = npcs.id JOIN factions ON npcs.faction = factions.id WHERE npcs.code = :npccode AND factions.code = :factioncode';
		$this->db->query($sql);
		$this->db->bind('npccode', $npccode);
		$this->db->bind('factioncode', $factioncode);
		$this->db->execute();
		if($this->db->rowCount() > 0){
			$return = $this->db->fetchAll();
		}
		$this->dialogues = $return;
	}

	public function getEdgeDialogueUrl(bool $first = true){
		$return = false;
		$sql = 'SELECT dialogue.id, factions.code AS faction, npcs.code AS npc FROM dialogue JOIN npcs ON dialogue.npc = npcs.id JOIN factions ON factions.id = npcs.faction ORDER BY dialogue.id ' . ($first === true ? 'ASC' : 'DESC') . ' LIMIT 1';
		$this->db->query($sql);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$row = $this->db->fetch();
			$return = '../dialogue/' . $row->faction . '/' . $row->npc . '/';
			if($first === true){
				$return .= '1';
			}else{
				$npc = new npc();
				$npc->getIdFromCode($row->npc, true);
				$return .= $npc->getDialogueCount();
			}
		}
		return $return;
	}

	public function countDialogues(string $factioncode = null, string $npccode = null){
		$return = false;
		$sql = 'SELECT COUNT(dialogue.id) AS count FROM dialogue JOIN npcs ON dialogue.npc = npcs.id JOIN factions ON npcs.faction = factions.id';
		$where = $bind = array();
		if(!empty($factioncode)){
			$where[] = 'factions.code = :factioncode';
			$bind['factioncode'] = $factioncode;
		}
		if(!empty($npccode)){
			$where[] = 'npcs.code = :npccode';
			$bind['npccode'] = $npccode;
		}
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
		if($this->db->rowCount() === 1){
			$return = $this->db->fetch()->count;
		}
		return $return;
	}

	public function getRandomDialogueUrl(){
		$return = false;
		$sql = 'SELECT factions.code AS factioncode, npcs.code AS npccode FROM dialogue JOIN npcs ON dialogue.npc = npcs.id JOIN factions ON npcs.faction = factions.id ORDER BY RAND() LIMIT 1';
		$this->db->query($sql);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$row = $this->db->fetch();
			$return = '/dialogue/' . $row->factioncode . '/' . $row->npccode . '/' . rand(1, $this->countDialogues($row->factioncode, $row->npccode));
		}
		return $return;
	}
}
?>