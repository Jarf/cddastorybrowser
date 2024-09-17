<?php
class story extends Entity{
	public int $id;
	public int $category;
	public string $story;
	public string $categoryName;

	public function __construct(){
		$this->table = 'stories';
		parent::__construct();
	}

	public function loadStory(int $id = null){
		$sql = 'SELECT ' . implode(', ', $this->prependColumns()) . ', categories.name AS categoryName FROM ' . $this->table . ' JOIN categories ON stories.category = categories.id WHERE categories.descriptor = 0';
		if(!empty($id)){
			$sql .= ' AND ' . $this->table . '.id = :id';
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
				$this->$key = $val;
			}
		}
		$this->parseStory();
	}

	public function parseStory(){
		$this->story = nl2br($this->story);
		// Replace color tags with styled spans
		preg_match_all('/\<color_(\w+)\>(.*)\<\/color\>/ms', $this->story, $colormatches);
		if(!empty($colormatches)){
			foreach($colormatches[0] as $cmkey => $cmval){
				$newstring = '<span style="color: ' . $colormatches[1][$cmkey] . '">' . $colormatches[2][$cmkey] . '</span>';
				$this->story = str_replace($colormatches[0][$cmkey], $newstring, $this->story);
			}
		}
	}
}
?>