<?php
class factions extends Entity{
	public array $factions;

	public function __construct(){
		$this->table = 'factions';
		parent::__construct();
	}
}
?>