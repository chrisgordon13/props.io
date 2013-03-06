<?php
class HotelImagesModel 
{	
	private $deps;
	public $collection;
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
		
		$sql = "SELECT h.URL
				FROM ean.hotelimagelist h
				WHERE h.EANHotelID = :id
				ORDER BY h.DefaultImage DESC
				";

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			
			$stmt->execute();
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				
				$row['url'] = str_replace('http:', '', $row['URL']);
				$this->collection[] = $row;			

				$status_read = true;
			}

		} catch (Exception $e) {
				
				$this->errors['status'] = "Collection failed.";
		}

		return $status_read;
	}
}
