<?php
class story extends Entity{
	public int $id;
	public int $category;
	public string $story;

	public function __construct(){
		$this->table = 'stories';
		parent::__construct();
	}
}
?>