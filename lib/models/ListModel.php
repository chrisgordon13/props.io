<?php
class ListModel 
{	
	private $deps;
	public $id, $key, $name, $private, $access_code, $display_name, $proximity, $fixed_variable_pricing, $max_price, $max_price_percent, $max_price_condition, $max_price_rating;
	public $errors;

	public function __construct(DependencyHelper $deps, $key='') 
	{		
		$this->deps = $deps;

		$this->private = 'N';
		$this->access_code = '';
		$this->display_name = 'N';
		$this->proximity = 10;
		$this->fixed_variable_pricing = 'variable';
		$this->max_price = 1000;
		$this->max_price_percent = 10;
		$this->max_price_condition = 'average';
		$this->max_price_rating = 3;

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
		$name = $data['list_name'];	

		$sql = "INSERT INTO lists 
				(user_id, name, date_added, date_updated) 
				VALUES (:user_id, :name, NOW(), NOW())
				";		

		try {
			$stmt = $db->prepare($sql);
			
			$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
			$stmt->bindValue(':name', $name, PDO::PARAM_STR);

			$stmt->execute();
			
			$this->id = $db->lastInsertId();
			$this->key = $tools->keyConvert($this->id);	
			$this->read($this->key);
			
			$status_create = true;

		} catch (PDOException $e) {
				
				$this->errors['status'] = "Create failed.";
				$this->errors['db'] = $e->getMessage();
		}

		return $status_create;
	}

	public function read($key) 
	{	
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_read = false;		

		$this->key = $key;
		$id = $tools->keyConvert($key, true);

		$sql = "SELECT id, user_id, name, private, access_code, display_name, proximity, fixed_variable_pricing, max_price, max_price_percent, max_price_condition, max_price_rating 
				FROM lists 
				WHERE id = :id 
				AND date_archived IS NULL
				";		

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			
			$stmt->execute();
			
			$row = $stmt->fetch(PDO::FETCH_ASSOC);	
			
			if (is_array($row)) {
				
				foreach ($row as $field_name => $value) {
					$this->$field_name = $value;
				}
				
				$status_read = true;
			} else {

				$status_read = false;
				$this->errors['status'] = "Read failed. No list was found using $key";
			}

		} catch (PDOException $e) {
				
			$this->errors['status'] = "Read failed.";
			$this->errors['db'] = $e->getMessage();
		}

		return $status_read;
	}

	public function update($key, $data) 
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_update = false;

		$id = $tools->keyConvert($key, true);		
		$name = $data['list_name'];
		$private = isset($data['list_private']) ? $data['list_private'] : $this->private;
		$access_code = isset($data['list_access_code']) ? $data['list_access_code'] : $this->access_code;
		$display_name = isset($data['display_name']) ? $data['display_name'] : $this->display_name;
		$proximity = isset($data['proximity']) ? $data['proximity'] : $this->proximity;
		$fixed_variable_pricing = isset($data['fixed_variable_pricing']) ? $data['fixed_variable_pricing'] : $this->fixed_variable_pricing;
		$max_price = isset($data['max_price']) ? $data['max_price'] : $this->max_price;
		$max_price_percent = isset($data['max_price_percent']) ? $data['max_price_percent'] : $this->max_price_percent;
		$max_price_condition = isset($data['max_price_condition']) ? $data['max_price_condition'] : $this->max_price_condition;
		$max_price_rating = isset($data['max_price_rating']) ? $data['max_price_rating'] : $this->max_price_rating;

		$sql = "UPDATE lists 
				SET name = :name,
				private = :private, 
				access_code = :access_code,
				display_name = :display_name,
				proximity = :proximity,
				fixed_variable_pricing = :fixed_variable_pricing,
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
			$stmt->bindValue(':private', $private, PDO::PARAM_STR);
			$stmt->bindValue(':access_code', $access_code, PDO::PARAM_STR);
			$stmt->bindValue(':display_name', $display_name, PDO::PARAM_STR);
			$stmt->bindValue(':proximity', $proximity, PDO::PARAM_INT);
			$stmt->bindValue(':fixed_variable_pricing', $fixed_variable_pricing, PDO::PARAM_STR);
			//$stmt->bindValue(':min_price', $min_price, PDO::PARAM_INT);
			$stmt->bindValue(':max_price', $max_price, PDO::PARAM_INT);			
			$stmt->bindValue(':max_price_percent', $max_price_percent, PDO::PARAM_INT);
			$stmt->bindValue(':max_price_condition', $max_price_condition, PDO::PARAM_STR);
			$stmt->bindValue(':max_price_rating', $max_price_rating, PDO::PARAM_INT);
			

			$stmt->execute();

			$this->key = $key;
			$this->read($this->key);
			
			$status_update = true;	
		
		} catch (PDOException $e) {
				
			$this->errors['status'] = "Update failed.";
			$this->errors['db'] = $e->getMessage();
		}

		return $status_update;
	}

	public function delete($key) {
		
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_delete = false;

		$id = $tools->keyConvert($key, true);

		$sql = "UPDATE lists
				SET date_archived = NOW()
				WHERE id = :id
				";		
		
		try {
			$stmt = $db->prepare($sql);
	
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			
			$stmt->execute();				
			
			$status_delete = true;	
		
		} catch (PDOException $e) {
				
			$this->errors['status'] = "Delete failed.";
			$this->errors['db'] = $e->getMessage();
		}

		return $status_delete;
	}
}
