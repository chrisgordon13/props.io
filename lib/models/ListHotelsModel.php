<?php
class ListHotelsModel 
{	
	private $deps;
	public $collection;
	public $errors;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;
	}

	public function read($list_id)
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_read = false;	
		
		$sql = "SELECT lh.id, 
				l.id as list_id, l.user_id as list_user_id, l.name as list_name, l.private as list_private, l.access_code as list_access_code, 
				h.id as hotel_id, h.Name as hotel_name, h.Address1 as hotel_address_1, h.Address2 as hotel_address_2, h.City as hotel_city, h.StateProvince as hotel_state_province, h.PostalCode as hotel_postal_code, h.Country as hotel_country, h.Latitude as hotel_lat, h.Longitude as hotel_lon, h.AirportCode as hotel_airport_code, h.PropertyCurrency as hotel_currency, h.StarRating as hotel_rating
				FROM lists l
				JOIN list_hotels lh ON (l.id = lh.list_id)
				JOIN hotels h ON (lh.hotel_id = h.id)
				WHERE l.id = :list_id
				AND l.date_archived IS NULL
				AND lh.date_archived IS NULL
				AND h.date_archived IS NULL
				";

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':list_id', $list_id, PDO::PARAM_INT);
			
			$stmt->execute();
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				
				$row['key'] = $tools->keyConvert($row['id']);
				$row['list_key'] = $tools->keyConvert($row['list_id']);
				$rot['hotel_key'] = $tools->keyConvert($row['hotel_id']);
				$this->collection[] = $row;			

				$status_read = true;
			}

		} catch (Exception $e) {
				
				$this->errors['status'] = "Collection failed.";
		}

		return $status_read;
	}
}
