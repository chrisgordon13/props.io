<?php
class ListSearchModel 
{	
	private $deps;
	public $id, $key, $list_id, $list_key, $search_id, $search_key;
	public $errors;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;
	}

	public function create($data) 
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_create = false;

		$list_id = $tools->keyConvert($data['list_key'], true);
		$search_id = $tools->keyConvert($data['search_key'], true);	

		$sql = "INSERT INTO list_searches 
				(list_id, search_id, date_added, date_updated) 
				VALUES (:list_id, :search_id, NOW(), NOW())
				ON DUPLICATE KEY UPDATE date_updated = NOW(), date_archived = NULL
				";		

		try {
			$stmt = $db->prepare($sql);
			
			$stmt->bindValue(':list_id', $list_id, PDO::PARAM_INT);
			$stmt->bindValue(':search_id', $search_id, PDO::PARAM_INT);

			$stmt->execute();
			
			$this->id = $db->lastInsertId();
			$this->key = $tools->keyConvert($this->id);
			$this->list_id = $list_id;
			$this->list_key = $data['list_key'];
			$this->search_id = $search_id;
			$this->search_key = $data['search_key'];
			
			$status_create = true;

		} catch (PDOException $e) {
				
			$this->errors['status'] = "Create failed.";
			$this->errors['db'] = $e->getMessage();
		}

		return $status_create;
	}

	public function delete($list_key, $search_key)
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_delete = false;

		$list_id = $tools->keyConvert($list_key, true);
		$search_id = $tools->keyConvert($search_key, true);

		$sql = "UPDATE list_searches 
				SET date_archived = NOW()
				WHERE list_id = :list_id
				AND search_id = :search_id
				";		

		try {
			$stmt = $db->prepare($sql);
			
			$stmt->bindValue(':list_id', $list_id, PDO::PARAM_INT);
			$stmt->bindValue(':search_id', $search_id, PDO::PARAM_INT);

			$stmt->execute();			
		
			$status_delete = true;

		} catch (PDOException $e) {
				
			$this->errors['status'] = "Delete failed.";
			$this->errors['db'] = $e->getMessage();
		}

		return $status_delete;
	}
}
