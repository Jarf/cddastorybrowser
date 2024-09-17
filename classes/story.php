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
		$this->story = trim($this->story);
		// Add line breaks
		$this->story = nl2br($this->story);
		$this->story = preg_replace('/\s{2,}/ms', '<br/><br/>', $this->story);
		// Replace color tags with styled spans
		preg_match_all('/\<color_(\w+)\>(.*)\<\/color\>/ms', $this->story, $colormatches);
		if(!empty($colormatches)){
			foreach($colormatches[0] as $cmkey => $cmval){
				$newstring = '<span style="color: ' . $colormatches[1][$cmkey] . '">' . $colormatches[2][$cmkey] . '</span>';
				$this->story = str_replace($colormatches[0][$cmkey], $newstring, $this->story);
			}
		}
		// Replace descriptors with words
		preg_match_all('/\<\w+\>/', $this->story, $descriptormatches);
		if(!empty($descriptormatches)){
			foreach($descriptormatches[0] as $dmkey => $dmval){
				$descriptor = $this->fetchDescriptor($descriptormatches[0][$dmkey]);
				$pos = strpos($this->story, $descriptormatches[0][$dmkey]);
				$this->story = substr_replace($this->story, $descriptor, $pos, strlen($descriptormatches[0][$dmkey]));
			}
		}
		// Replace keybinds
		$this->story = preg_replace('/<keybind:(.*?)>/', '$1 button', $this->story);
	}

	private function fetchDescriptor(string $descriptor){
		$sql = 'SELECT stories.story AS descriptor FROM stories JOIN categories ON stories.category = categories.id WHERE categories.name = :descriptor AND categories.descriptor = 1 ORDER BY RAND() LIMIT 1';
		$this->db->query($sql);
		$this->db->bind('descriptor', $descriptor);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$row = $this->db->fetch();
			$descriptor = $row->descriptor;
		}else{
			$descriptor = trim(preg_replace('/[^A-Za-z0-9]/', ' ', $descriptor));
		}
		return $descriptor;
	}
}
?>