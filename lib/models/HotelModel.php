<?php
class HotelModel 
{
	private $deps;
	public $id, $key, $name, $address_1, $address_2, $city, $state_province, $postal_code, $country, $latitude, $longitude, $star_rating, $ta_rating, $high_rate, $low_rate, $currency, $check_in_time, $check_out_time, $default_image, $images;
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
		$cache = $this->deps['cache'];
		$db = $this->deps['db'];

		$status_read = false;

		$this->key = $key;
		$id = $tools->keyConvert($key, true);

		$cache_key = 'hotel:read:' . $key;		

		if ($cache->get($cache_key)) {

			$row = $cache->get($cache_key);
		} else {			

			$sql = "SELECT p.EANHotelID as id, p.Name as name, p.Address1 as address_1, p.Address2 as address_2, p.City as city, p.StateProvince as state_province, p.PostalCode as postal_code, p.Country as country, p.Latitude as latitude, p.Longitude as longitude, p.StarRating as star_rating, p.StarRating as ta_rating, p.HighRate as high_rate, p.LowRate as low_rate, p.PropertyCurrency as currency, p.CheckInTime as check_in_time, p.CheckOutTime as check_out_time, p.Default_Image as default_image
			FROM properties p
			WHERE p.EANHotelID = :id
			";
			
			try {

				$stmt = $db->prepare($sql);				
				$stmt->bindValue(':id', $id, PDO::PARAM_INT);				
				$stmt->execute();				
				$row = $stmt->fetch(PDO::FETCH_ASSOC);

			} catch (PDOException $e) {
				
				$this->errors['status'] = "Read failed.";
				$this->errors['db'] = $e->getMessage();
			}
		}

		if (is_array($row)) {
					
			foreach ($row as $field_name => $value) {
				$this->$field_name = $value;
			}
			
			$cache->set($cache_key, $row);
			$status_read = true;
		} else {

			$status_read = false;
			$this->errors['status'] = "Read failed. No list was found using $key";
		}
		
		return $status_read;
	}
}
