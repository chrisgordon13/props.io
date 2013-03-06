<?php
class SearchModel 
{	
	private $deps;
	public $id, $key, $user_id, $name, $search_text, $min_rating, $max_rating, $latitude, $longitude, $proximity, $fixed_variable_pricing, $min_price, $max_price, $max_price_percent, $max_price_condition, $max_price_rating;
	public $errors;

	public function __construct(DependencyHelper $deps, $key='') 
	{		
		$this->deps = $deps;

		if ($key != '') {
			$this->read($key);
		}
	}

	public function create($data) 
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_create = false;

		$user_id = $data['user_id'];
		$name = $data['search_name'];
		
		$search_text = $data['search_text'];
		
		$min_rating = $tools->getRating($search_text);

		$trimmed_search = $tools->stripWords($data['search_text']);	
		if ($geo_pieces = $tools->getGeoCenter($trimmed_search)) {	
			$latitude = $geo_pieces['lat'];
			$longitude = $geo_pieces['lon'];
		} else {
			$latitude = NULL;
			$longitude = NULL;
		}

		$sql = "INSERT INTO searches 
				(user_id, name, search_text, min_rating, latitude, longitude, date_added, date_updated) 
				VALUES (:user_id, :name, :search_text, :min_rating, :latitude, :longitude, NOW(), NOW())
				";		

		try {
			$stmt = $db->prepare($sql);
			
			$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
			$stmt->bindValue(':name', $name, PDO::PARAM_STR);
			$stmt->bindValue(':search_text', $search_text, PDO::PARAM_STR);
			$stmt->bindValue(':min_rating', $min_rating, PDO::PARAM_INT);
			//$stmt->bindValue(':max_rating', $max_rating, PDO::PARAM_INT);
			$stmt->bindValue(':latitude', $latitude, PDO::PARAM_STR);
			$stmt->bindValue(':longitude', $longitude, PDO::PARAM_STR);
			//$stmt->bindValue(':proximity', $proximity, PDO::PARAM_INT);
			//$stmt->bindValue(':min_price', $min_price, PDO::PARAM_INT);
			//$stmt->bindValue(':max_price', $max_price, PDO::PARAM_INT);

			$stmt->execute();
			
			$this->id = $db->lastInsertId();
			$this->key = $tools->keyConvert($this->id);	
			$this->user_id = $user_id;
			$this->name  = $name;
			$this->search_text  = $search_text;
			$this->min_rating  = $min_rating;
			//$this->max_rating  = $max_rating;
			$this->latitude  = $latitude;
			$this->longitude  = $longitude;
			//$this->proximity  = $proximity;
			//$this->min_price  = $min_price;
			//$this->max_price  = $max_price;
			
			$status_create = true;

		} catch (Exception $e) {
				
				$this->errors['status'] = "Create failed.";
		}

		return $status_create;
	}

	public function read($key) 
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_read = false;		

		$id = $tools->keyConvert($key, true);

		$sql = "SELECT id, user_id, name, search_text, min_rating, max_rating, latitude, longitude, proximity, fixed_variable_pricing, min_price, max_price, max_price_percent, max_price_condition, max_price_rating
				FROM searches
				WHERE id = :id 
				AND date_archived IS NULL
				";		

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			
			$stmt->execute();
			
			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			$this->id = $row['id'];
			$this->key = $key;	
			$this->user_id = $row['user_id'];
			$this->name  = $row['name'];
			$this->search_text  = $row['search_text'];
			$this->min_rating  = $row['min_rating'];
			$this->max_rating  = $row['max_rating'];
			$this->latitude  = $row['latitude'];
			$this->longitude  = $row['longitude'];
			$this->proximity  = $row['proximity'];
			$this->fixed_variable_pricing = $row['fixed_variable_pricing'];
			$this->min_price  = $row['min_price'];
			$this->max_price  = $row['max_price'];
			$this->max_price_percent = $row['max_price_percent'];
			$this->max_price_condition = $row['max_price_condition'];
			$this->max_price_rating = $row['max_price_rating'];

			$status_read = true;

		} catch (Exception $e) {
				
				$this->errors['status'] = "Read failed.";
		}

		return $status_read;
	}

	public function update($key, $data) 
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_update = false;

		$id = $tools->keyConvert($key, true);		
		$name = $data['search_name'];				
		$min_rating = $data['min_rating'];
		$max_rating = $data['max_rating'];
		$latitude = $data['lat'];
		$longitude = $data['lon'];
		$proximity = $data['proximity'];	
		$fixed_variable_pricing = $data['fixed_variable_pricing'];
		//$min_price = $data['min_price'];
		$min_price = 0;
		$max_price = $data['max_price'];
		$max_price_percent = $data['max_price_percent'];
		$max_price_condition = $data['max_price_condition'];
		$max_price_rating = $data['max_price_rating'];

		$sql = "UPDATE searches 
				SET name = :name, 
				min_rating = :min_rating,
				max_rating = :max_rating,
				latitude = :latitude,
				longitude = :longitude,
				proximity = :proximity,
				fixed_variable_pricing = :fixed_variable_pricing,
				min_price = :min_price,
				max_price = :max_price,				
				max_price_percent = :max_price_percent,
				max_price_condition = :max_price_condition,
				max_price_rating = :max_price_rating,
				date_updated = NOW()
				WHERE id = :id
				";		

		try {
			$stmt = $db->prepare($sql);

			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$stmt->bindValue(':name', $name, PDO::PARAM_STR);
			//$stmt->bindValue(':search_text', $search_text, PDO::PARAM_STR);
			$stmt->bindValue(':min_rating', $min_rating, PDO::PARAM_INT);
			$stmt->bindValue(':max_rating', $max_rating, PDO::PARAM_INT);
			$stmt->bindValue(':latitude', $latitude, PDO::PARAM_STR);
			$stmt->bindValue(':longitude', $longitude, PDO::PARAM_STR);
			$stmt->bindValue(':proximity', $proximity, PDO::PARAM_INT);
			$stmt->bindValue(':fixed_variable_pricing', $fixed_variable_pricing, PDO::PARAM_STR);
			$stmt->bindValue(':min_price', $min_price, PDO::PARAM_INT);
			$stmt->bindValue(':max_price', $max_price, PDO::PARAM_INT);			
			$stmt->bindValue(':max_price_percent', $max_price_percent, PDO::PARAM_INT);
			$stmt->bindValue(':max_price_condition', $max_price_condition, PDO::PARAM_STR);
			$stmt->bindValue(':max_price_rating', $max_price_rating, PDO::PARAM_INT);

			$stmt->execute();

			$this->id = $id;
			$this->key = $key;
			$this->name  = $name;
			//$this->search_text  = $search_text;
			$this->min_rating  = $min_rating;
			$this->max_rating  = $max_rating;
			$this->latitude  = $latitude;
			$this->longitude  = $longitude;
			$this->proximity  = $proximity;
			$this->fixed_variable_pricing  = $fixed_variable_pricing;
			$this->min_price  = $min_price;
			$this->max_price  = $max_price;
			$this->max_price_percent  = $max_price_percent;
			$this->max_price_condition  = $max_price_condition;
			$this->max_price_rating  = $max_price_rating;
			
			$status_update = true;	
		
		} catch (PDOException $e) {
				
				$this->errors['Update'] = $e->getMessage();
		}

		return $status_update;
	}

	public function delete($key) {
		
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_delete = false;

		$id = $tools->keyConvert($key, true);

		$sql = "UPDATE searches
				SET date_archived = NOW()
				WHERE id = :id
				";		
		
		try {
			$stmt = $db->prepare($sql);
	
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			
			$stmt->execute();				
			
			$status_delete = true;	
		
		} catch (Exception $e) {
				
				$this->errors['Delete'] = "Delete failed.";
		}

		return $status_delete;
	}
}
