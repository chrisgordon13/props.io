<?php
class SearchesModel 
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

		$sql = "SELECT id, user_id, name, search_text, min_rating, max_rating, latitude, longitude, proximity, fixed_variable_pricing, min_price, max_price, max_price_percent, max_price_condition, max_price_rating
				FROM searches
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

		} catch (Exception $e) {
				
				$this->errors['status'] = "Collection failed.";
		}

		return $status_read;
	}
}
