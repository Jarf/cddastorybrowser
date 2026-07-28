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
}
?>