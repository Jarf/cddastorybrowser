<?php
class dialogue extends Entity{
	public int $id;
	public string $code;
	public string $dialogue;
	public int $npc;
	public string $npcName;
	public string $npcCode;
	public string $factionName;
	public string $factionCode;

	public function __construct(){
		$this->table = 'dialogue';
		parent::__construct();
	}

	public function loadDialogue(int $id = null){
		$sql = 'SELECT dialogue.id, dialogue.code, dialogue.dialogue, dialogue.npc, COALESCE(npcs.name, npcs.code) AS npcName, npcs.code AS npcCode, COALESCE(factions.name, factions.code) AS factionName, factions.code AS factionCode FROM dialogue JOIN npcs ON dialogue.npc = npcs.id JOIN factions ON npcs.faction = factions.id';
		if(!empty($id)){
			$sql .= ' WHERE dialogue.id = :id';
		}else{
			$sql .= ' ORDER BY RAND()';
		}
		$sql .= ' LIMIT 1';
		$this->db->query($sql);
		if(!empty($id)){
			$this->db->bind('id', $id);
		}
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$row = $this->db->fetch();
			foreach($row as $key => $val){
				if(!empty($val)){
					$this->$key = $val;
				}
			}
		}
	}

	public function getDialogueId(string $factioncode, string $npccode, int $dialogueid){
		$id = false;
		$sql = 'SELECT dialogue.id FROM dialogue JOIN npcs ON dialogue.npc = npcs.id JOIN factions ON npcs.faction = factions.id WHERE npcs.code = :npccode AND factions.code = :factioncode ORDER BY dialogue.id ASC LIMIT 1 OFFSET ' . ($dialogueid - 1);
		$this->db->query($sql);
		$this->db->bind('npccode', $npccode);
		$this->db->bind('factioncode', $factioncode);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$id = $this->db->fetch()->id;
		}
		return $id;
	}

	public function getNextPrevIds(){
		
	}
}
?>