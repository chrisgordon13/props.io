<?php
class ListsModel 
{	
	private $deps;
	public $collection;
	public $errors;

	public function __construct(DependencyHelper $deps, $user_id=0) 
	{		
		$this->deps = $deps;

		$this->collection = array();

		if ($user_id > 0) {
			$this->read($user_id);
		}
	}

	public function read($user_id)
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_read = false;	
		
		$sql = "SELECT id, user_id, name, private, access_code, display_name, proximity, fixed_variable_pricing, max_price, max_price_percent, max_price_condition, max_price_rating 
				FROM lists 
				WHERE user_id = :user_id 
				AND date_archived IS NULL
				";

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
			
			$stmt->execute();
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				
				$row['key'] = $tools->keyConvert($row['id']);
				$this->collection[] = $row;			

				$status_read = true;
			}

		} catch (PDOException $e) {
				
			$this->errors['status'] = "Collection failed.";
			$this->errors['db'] = $e->getMessage();
		}

		return $status_read;
	}
}
