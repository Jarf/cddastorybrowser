<?php
class story extends Entity{
	public int $id;
	public int $category;
	public string $story;
	public string $categoryName;
	public string $style = 'default';

	public function __construct(){
		$this->table = 'stories';
		parent::__construct();
	}

	public function loadStory(int $id = null){
		$sql = 'SELECT ' . implode(', ', $this->prependColumns()) . ', categories.name AS categoryName, styles.name AS style FROM ' . $this->table . ' JOIN categories ON stories.category = categories.id LEFT JOIN categoriesStyles ON categories.id = categoriesStyles.categoriesId LEFT JOIN styles ON categoriesStyles.stylesId = styles.id WHERE categories.descriptor = 0';
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
				if(!empty($val)){
					$this->$key = $val;
				}
			}
		}
		$this->parseStory();
	}

	public function parseStory(){
		$this->story = trim($this->story);
		// Add line breaks
		$this->story = nl2br($this->story);
		$this->story = preg_replace('/\s{2,}/ms', '<br/>', $this->story);
		// Replace color tags with styled spans
		preg_match_all('/\<color_(\w+)\>(.*)\<\/color\>/ms', $this->story, $colormatches);
		if(!empty($colormatches)){
			foreach($colormatches[0] as $cmkey => $cmval){
				$newstring = '<span style="color: ' . $colormatches[1][$cmkey] . '">' . $colormatches[2][$cmkey] . '</span>';
				$this->story = str_replace($colormatches[0][$cmkey], $newstring, $this->story);
			}
		}
		// Replace keybinds
		$this->story = preg_replace('/<keybind:(.*?)>/', 'the $1 button', $this->story);
		// Replace descriptors with words
		while(preg_match_all('/\<\w+\>/', $this->story, $descriptormatches) !== 0){
			if(!empty($descriptormatches)){
				foreach($descriptormatches[0] as $dmkey => $dmval){
					$descriptor = $this->fetchDescriptor($descriptormatches[0][$dmkey]);
					$pos = strpos($this->story, $descriptormatches[0][$dmkey]);
					$this->story = substr_replace($this->story, $descriptor, $pos, strlen($descriptormatches[0][$dmkey]));
				}
			}
		}
		// If starts and ends with apostrophes trim them
		if(preg_match('/^\"[^"]+\"$/', $this->story) === 1){
			$this->story = trim($this->story, '"');
		}
		// If newspaper
		if($this->style === 'news'){
			// all caps first line, make headline
			if(preg_match('/^([^a-z]+)(<br\/>)+/', $this->story, $headline) === 1){
				$pos = strpos($this->story, $headline[1]);
				$len = strlen($headline[0]);
				$headline = '<h1>' . $headline[1] . '</h1><hr/>';
				$this->story = substr_replace($this->story, $headline, $pos, $len);
			}
		}
		// Replace brs with hr
		$this->story = str_replace(array('<br/>', '<br>'), '<hr/>', $this->story);
	}

	private function fetchDescriptor(string $descriptor){
		$matchcount = preg_match_all('/\<\w+\>/', $descriptor, $matches);
		if($matchcount > 1){
			$descriptors = array();
			foreach($matches[0] as $match){
				$descriptors[$match] = $this->fetchDescriptor($match);
			}
			foreach($descriptors[$match] as $mkey => $mval){
				$pos = strpos($descriptor, $mval);
				$descriptor = substr_replace($descriptor, $mval, $pos, strlen($mval));
			}
			$descriptor = trim(preg_replace('/[^A-Za-z0-9]/', ' ', $descriptor));
		}elseif($matchcount === 1){
			$descriptor = $matches[0][0];
			$sql = 'SELECT stories.story AS descriptor FROM stories JOIN categories ON stories.category = categories.id WHERE categories.name = :descriptor ORDER BY RAND() LIMIT 1';
			$this->db->query($sql);
			$this->db->bind('descriptor', $descriptor);
			$this->db->execute();
			if($this->db->rowCount() === 1){
				$row = $this->db->fetch();
				$descriptor = $row->descriptor;
			}else{
				$descriptor = trim(preg_replace('/[^A-Za-z0-9]/', ' ', $descriptor));
			}
		}
		if(preg_match('/\<\w+\>/', $descriptor) === 1){
			$descriptor = $this->fetchDescriptor($descriptor);
		}
		return $descriptor;
	}
}
?>