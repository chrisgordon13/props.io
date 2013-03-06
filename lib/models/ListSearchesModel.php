<?php
class ListSearchesModel 
{	
	private $deps;
	public $collection;
	public $errors;

	public function __construct(DependencyHelper $deps, $key='') 
	{		
		$this->deps = $deps;
		
		$this->collection = array();

		if ($key != '') {
			$this->read($key);
		}
	}

	public function read($key)
	{
		$user = $this->deps['user'];
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_read = false;

		$list_id = $tools->keyConvert($key, true);

		
		$sql = "SELECT s.id as search_id, s.name, s.search_text, s.min_rating, s.max_rating, s.latitude, s.longitude, s.proximity, s.fixed_variable_pricing, s.min_price, s.max_price, s.max_price_percent, s.max_price_condition, s.max_price_rating, ls.list_id
				FROM searches s
				LEFT JOIN list_searches ls ON (s.id = ls.search_id AND ls.list_id = :list_id AND ls.date_archived IS NULL)
				WHERE s.user_id = :user_id				
				AND s.date_archived IS NULL
				";

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':list_id', $list_id, PDO::PARAM_INT);
			$stmt->bindValue(':user_id', $user->id, PDO::PARAM_INT);
			
			$stmt->execute();
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				
				$row['search_key'] = $tools->keyConvert($row['search_id']);
				$this->collection[] = $row;			

				$status_read = true;
			}

		} catch (PDOException $e) {
				
			$this->errors['status'] = "Collection failed.";
			$this->errors['db'] = $e->getMessage();
		}

		return $status_read;
	}

	public function readDistinct($key)
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_read = false;

		$list_id = $tools->keyConvert($key, true);

		
		$sql = "SELECT s.id as search_id, s.name, s.search_text, s.min_rating, s.max_rating, s.latitude, s.longitude, s.proximity, s.fixed_variable_pricing, s.min_price, s.max_price, s.max_price_percent, s.max_price_condition,	s.max_price_rating, ls.list_id
				FROM searches s
				JOIN list_searches ls ON (s.id = ls.search_id AND ls.list_id = :list_id AND ls.date_archived IS NULL)
				AND s.date_archived IS NULL
				";

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':list_id', $list_id, PDO::PARAM_INT);
			
			$stmt->execute();
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				
				$row['search_key'] = $tools->keyConvert($row['search_id']);
				$this->collection[] = $row;			

				$status_read = true;
			}

		} catch (PDOException $e) {
				
			$this->errors['status'] = "Distinct collection failed.";
			$this->errors['db'] = $e->getMessage();
		}

		return $status_read;
	}
}
