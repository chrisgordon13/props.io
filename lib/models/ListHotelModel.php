<?php
class ListHotelModel 
{	
	private $deps;
	public $id, $key, $list_id, $list_key, $hotel_id, $hotel_key;

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
		$hotel_id = $tools->keyConvert($data['hotel_key'], true);	

		$sql = "INSERT INTO list_hotels 
				(list_id, hotel_id, date_added, date_updated) 
				VALUES (:list_id, :hotel_id, NOW(), NOW())
				";		

		try {
			$stmt = $db->prepare($sql);
			
			$stmt->bindValue(':list_id', $list_id, PDO::PARAM_INT);
			$stmt->bindValue(':hotel_id', $hotel_id, PDO::PARAM_INT);

			$stmt->execute();
			
			$this->id = $db->lastInsertId();
			$this->key = $tools->keyConvert($this->id);
			$this->list_id = $list_id;
			$this->list_key = $data['list_key'];
			$this->hotel_id = $hotel_id;
			$this->hotel_key = $data['hotel_key'];
			
			$status_create = true;

		} catch (Exception $e) {
				
				$this->errors['status'] = "Create failed.";
		}

		return $status_create;
	}

	public function delete($key)
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_delete = false;

		$id = $tools->keyConvert($key, true);		

		$sql = "UPDATE list_hotels 
				SET date_archived = NOW()
				WHERE id = :id
				";		

		try {
			$stmt = $db->prepare($sql);
			
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);

			$stmt->execute();			
		
			$status_delete = true;

		} catch (Exception $e) {
				
				$this->errors['status'] = "Delete failed.";
		}

		return $status_delete;
	}
}
