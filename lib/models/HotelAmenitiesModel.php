<?php
class HotelAmenitiesModel 
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
		
		$sql = "SELECT AttributeDesc as name, AttributeTxt as value 
				FROM ean.gdspropertyattributelink a 
				JOIN ean.gdsattributelist l ON (a.AttributeID = l.AttributeID) 
				WHERE a.EANHotelID = :id 
				AND a.LanguageCode = 'en_US' 
				AND (l.Type = 'PropertyAmenity' OR l.Type = 'RoomAmenity') 
				UNION
				SELECT AttributeDesc as name, AppendTxt as value 
				FROM ean.propertyattributelink a 
				JOIN ean.attributelist l ON (a.AttributeID = l.AttributeID) 
				WHERE a.EANHotelID = :id 
				AND a.LanguageCode = 'en_US' 
				AND (l.Type = 'PropertyAmenity' OR l.Type = 'RoomAmenity')				
				";

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			
			$stmt->execute();
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				
				$this->collection[] = $row;			

				$status_read = true;
			}

		} catch (Exception $e) {
				
				$this->errors['status'] = "Collection failed.";
		}

		return $status_read;
	}
}
