<?php
class story extends Entity{
	public int $id;
	public int $category;
	public string $story;
	public string $categoryName;
	public string $style = 'default';
	public string $prevstory;
	public string $nextstory;

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
					if($key === 'categoryName'){
						$this->$key = $this->humanReadable($val);
					}
				}
			}
			$this->parseStory();
		}
	}

	public function getRandomStoryId(){
		$category = new categories();
		$category = $category->getRandomCategory();
		$storiescount = new stories();
		$storiescount = $storiescount->countStories($category->id, null, true);
		return $category->name . '/' . rand(1, $storiescount);
	}

	public function getStoryId(string $categoryname, int $categorystoryid){
		$id = false;
		$sql = 'SELECT stories.id FROM ' . $this->table . ' JOIN categories ON stories.category = categories.id WHERE categories.name = :categoryname ORDER BY stories.id ASC LIMIT 1 OFFSET ' . ($categorystoryid - 1);
		$this->db->query($sql);
		$this->db->bind('categoryname', $categoryname);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$id = $this->db->fetch()->id;
		}
		return $id;
	}

	public function getStoryUrl(int $storyid){
		$return = false;
		$sql = 'SELECT categories.name, COUNT(stories.id) AS storyCount FROM stories JOIN categories ON stories.category = categories.id WHERE stories.id <= :storyid AND categories.descriptor = 0 AND stories.category = (SELECT stories.category FROM stories WHERE stories.id = :storyid LIMIT 1) GROUP BY categories.id';
		$this->db->query($sql);
		$this->db->bind('storyid', $storyid);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$row = $this->db->fetch();
			$return = $row->name . '/' . $row->storyCount;
		}
		return $return;
	}

	public function parseStory(){
		$this->story = trim($this->story);
		// Add line breaks
		$this->story = nl2br($this->story);
		$this->story = preg_replace('/\s{2,}/ms', '<br/>', $this->story);
		// Remove color tags
		$this->story = preg_replace('/<\/?color(_\w+)?>/', '', $this->story);
		// Replace lt gt
		$this->story = preg_replace('/<lt>(.*?)<gt>/', '$1' , $this->story);
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
		// Style specific parsing
		switch ($this->style) {
			case 'news':
				// all caps first line, make headline
				if(preg_match('/^([^a-z]+)(<br\/>|(:\s))+/', $this->story, $headline) === 1){
					$pos = strpos($this->story, $headline[1]);
					$len = strlen($headline[0]);
					$headline = '<h1>' . $headline[1] . '</h1><hr/>';
					$this->story = substr_replace($this->story, $headline, $pos, $len);
				}
				break;
			
			case 'local_files_simple':
				if(preg_match('/((^.*\.[\S]{3,4})|(^apps\/[A-Za-z]+))<br\s\/>$/m', $this->story, $filename) === 1){
					$pos = strpos($this->story, $filename[1]);
					if($pos === 0){
						$len = strlen($filename[0]);
						$filename = '<h1>' . $filename[1] . '</h1>';
						$this->story = substr_replace($this->story, $filename, $pos, $len);
					}
				}
				break;

			case 'radio_archive':
				$this->story = '<h1>Automated Audio Transcript</h1>' . $this->story;
				break;
		}
		// Replace brs with hr
		$this->story = str_replace(array('<br/>', '<br>'), '<hr/>', $this->story);
	}

	public function getNextPrevIds(){
		$next = $prev = 1;
		$this->db->query('SELECT MIN(stories.id) AS next FROM stories JOIN categories ON stories.category = categories.id WHERE descriptor = 0 AND stories.id > :storyid LIMIT 1');
		$this->db->bind('storyid', $this->id);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$row = $this->db->fetch();
			if(is_numeric($row->next)){
				$next = $row->next;
			}
		}
		$this->db->query('SELECT MAX(stories.id) AS prev FROM stories JOIN categories ON stories.category = categories.id WHERE descriptor = 0 AND stories.id < :storyid LIMIT 1');
		$this->db->bind('storyid', $this->id);
		$this->db->execute();
		if($this->db->rowCount() === 1){
			$row = $this->db->fetch();
			if(is_numeric($row->prev)){
				$prev = $row->prev;
			}
		}
		$this->nextstory = $this->getStoryUrl($next);
		$this->prevstory = $this->getStoryUrl($prev);
	}

	public function getMetaDescription(){
		$description = 'A ' . $this->categoryName . ' category snippet - ' . preg_replace('/\s+/', ' ', strip_tags(str_replace(array('<br/>', '<hr/>'), ' ', $this->story)));
		if(strlen($description) > 120){
			$description = substr($description, 0, 117);
			$description = substr($description, 0, strrpos($description, ' ')) . '...';
		}
		return $description;
	}

	private function fetchDescriptor(string $descriptor){
		if(!empty(trim($descriptor))){
			$matchcount = preg_match_all('/\<\w+\>/', $descriptor, $matches);
			if($matchcount > 1){
				$descriptors = array();
				foreach($matches[0] as $match){
					$descriptors[$match] = $this->fetchDescriptor($match);
				}
				foreach($descriptors as $mkey => $mval){
					$pos = strpos($descriptor, $mkey);
					$descriptor = substr_replace($descriptor, $mval, $pos, strlen($mkey));
				}
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
					$descriptor = trim(preg_replace('/[<>]/', ' ', $descriptor));
				}
			}
			if(preg_match('/\<\w+\>/', $descriptor) === 1){
				$descriptor = $this->fetchDescriptor($descriptor);
			}
		}
		return $descriptor;
	}

	public function getDependencies(){
		$return = array();
		if(isset($this->style)){
			$css = DIR_CSS . 'stories/' . $this->style . '.css';
			if(file_exists($css)){
				$css = file_get_contents($css);
				preg_match_all('/background-image:\s?url\(\'([^\']+)\'\);/s', $css, $images, PREG_PATTERN_ORDER);
				if(!empty($images)){
					$images = end($images);
					foreach($images as $image){
						$return[] = array(
							'type' => 'image',
							'path' => $image
						);
					}
				}
				if(preg_match('/@font-face{[^}]+src:\s?url\(\'([^\']+)\'\);/s', $css, $font) === 1){
					$return[] = array(
						'type' => 'font',
						'path' => end($font)
					);
				}
			}
		}
		return $return;
	}
}
?>