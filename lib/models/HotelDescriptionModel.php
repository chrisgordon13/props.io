<?php
class HotelDescriptionModel 
{	
	private $deps;
	public $description;
	public $errors;

	public function __construct(DependencyHelper $deps, $key='') 
	{		
		$this->deps = $deps;

		if ($key != '') {
			$this->read($key);
		}
	}

	public function read($key)
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_read = false;
		
		$id = $tools->keyConvert($key, true);
		
		$sql = "SELECT d.PropertyDescription
				FROM ean.propertydescriptionlist d
				WHERE d.EANHotelID = :id
				";

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			
			$stmt->execute();
			
			$row = $stmt->fetch(PDO::FETCH_ASSOC);			

			$this->description = $row['PropertyDescription'];

			$status_read = true;

		} catch (Exception $e) {
				
				$this->errors['status'] = "Read failed.";
		}

		return $status_read;
	}
}
