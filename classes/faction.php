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
}
?>