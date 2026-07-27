<?php
class dialogues extends Entity{
	public array $dialogues;

	public function __construct(){
		$this->table = 'dialogue';
		parent::__construct();
	}
}
?>