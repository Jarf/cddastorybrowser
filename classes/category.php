<?php
class category extends Entity{
	public int $id;
	public string $name;
	public int $descriptor = 0;

	public function __construct(){
		$this->table = 'categories';
		parent::__construct();
	}
}
?>