<?php
class stories extends Entity{
	public int $category;
	public array $stories;

	public function __construct(){
		$this->table = 'stories';
		parent::__construct();
	}

	public function loadStories(int $categoryid = null, int $limit = 0, int $page = 0, string $search = null){
		$where = $bind = $return = array();
		$sql = 'SELECT stories.id, stories.story FROM stories';
		if(!empty($categoryid)){
			$where[] = 'stories.category = :category';
			$bind['category'] = $categoryid;
		}
		if(!empty($search)){
			$where[] = 'stories.story LIKE :search';
			$bind['search'] = '%' . $search . '%';
		}
		if(!empty($where)){
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$offset = 0;
		if(!empty($limit)){
			$offset = $limit * $page;
			$sql .= ' LIMIT :limit OFFSET :offset';
			$bind['limit'] = $limit;
			$bind['offset'] = $offset;
		}
		$this->db->query($sql);
		if(!empty($bind)){
			foreach($bind as $bkey => $bval){
				$this->db->bind($bkey, $bval);
			}
		}
		$this->db->execute();
		if($this->db->rowCount() > 0){
			$return = $this->db->fetchAll();
		}
		$previewlength = 48;
		foreach($return as &$row){
			$ellipsis = false;
			$row->story = $this->sanitizeString($row->story);
			$row->story = preg_split('/[\s]{2,}/m', $row->story);
			if(count($row->story) > 1){
				$ellipsis = true;
			}
			$row->story = current($row->story);
			if(strlen($row->story) > $previewlength){
				$row->story = substr($row->story, 0, $previewlength);
				$ellipsis = true;
			}
			if($ellipsis === true){
				$row->story .= '...';
			}
			
		}

		$this->stories = $return;

		$return = $this->countStories($categoryid, $search);
		$return['data'] = &$this->stories;
		return $return;
	}

	private function countStories(int $categoryid = null, string $search = null){
		$return = array(
			'recordsTotal' => 0,
			'recordsFiltered' => 0
		);
		// Get total for category
		$where = $bind = array();
		if(!empty($categoryid)){
			$where[] = 'stories.category = :category';
			$bind['category'] = $categoryid;
		}
		$sql = 'SELECT COUNT(stories.id) AS total FROM stories';
		if(!empty($where)){
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$this->db->query($sql);
		if(!empty($bind)){
			foreach($bind as $bkey => $bval){
				$this->db->bind($bkey, $bval);
			}
		}
		$this->db->execute();
		$row = $this->db->fetch();
		$return['recordsTotal'] = $row->total;
		if(!empty($search)){
			// Get total for filtered
			$where = $bind = array();
			if(!empty($categoryid)){
				$where[] = 'stories.category = :category';
				$bind['category'] = $categoryid;
			}
			if(!empty($search)){
				$where[] = 'stories.story LIKE :search';
				$bind['search'] = '%' . $search . '%';
			}
			$sql = 'SELECT COUNT(stories.id) AS total FROM stories';
			if(!empty($where)){
				$sql .= ' WHERE ' . implode(' AND ', $where);
			}
			$this->db->query($sql);
			if(!empty($bind)){
				foreach($bind as $bkey => $bval){
					$this->db->bind($bkey, $bval);
				}
			}
			$this->db->execute();
			$row = $this->db->fetch();
			$return['recordsFiltered'] = $row->total;
		}else{
			$return['recordsFiltered'] = $return['recordsTotal'];
		}
		return $return;
	}
}
?>