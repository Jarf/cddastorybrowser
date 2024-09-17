<?php
class categories extends Entity{
	public $categories;

	public function __construct(){
		$this->table = 'categories';
		parent::__construct();
	}
}
?>