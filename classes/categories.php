<?php
class categories extends Entity{
	public array $categories;

	public function __construct(){
		$this->table = 'categories';
		parent::__construct();
	}

	public function indexListings(int $categoryid = null, int $descriptor = 0){
		$where = $bind = $return = array();
		if(!empty($categoryid)){
			$where[] = 'categories.id = :categoryid';
			$bind['categoryid'] = $categoryid;
		}
		$where[] = 'categories.descriptor = :descriptor';
		$bind['descriptor'] = $descriptor;
		$sql = 'SELECT categories.id, categories.name, COUNT(stories.id) AS storiesCount FROM categories LEFT JOIN stories ON categories.id = stories.category';
		if(!empty($where)){
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$sql .= ' GROUP BY categories.id ORDER BY categories.name ASC';
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
		$this->categories = $return;
	}
}
?>