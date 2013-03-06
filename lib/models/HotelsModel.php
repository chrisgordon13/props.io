<?php
class HotelsModel 
{	
	private $deps;
	public $ids, $collection;
	public $errors;

	public function __construct(DependencyHelper $deps) 
	{	
		$this->deps = $deps;
	}

	public function read($lat, $lon, $proximity=10, $min_rating=3, $max_rating=5, $curated=true) 
	{	
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_read = false;

		$sql = "SELECT p.EANHotelID as id, p.Name as name, p.Address1 as address_1, p.Address2 as address_2, p.City as city, p.StateProvince as state_province, p.PostalCode as postal_code, p.Country as country, p.Latitude as latitude, p.Longitude as longitude, p.StarRating as star_rating, p.StarRating as ta_rating, p.HighRate as high_rate, p.LowRate as low_rate, p.PropertyCurrency as currency, p.CheckInTime as check_in_time, p.CheckOutTime as check_out_time, p.Default_Image as default_image, p.TripAdvisorRatingURL, ( 3959 * acos( cos( radians(:lat) ) * cos( radians( p.Latitude ) ) * cos( radians( p.Longitude ) - radians(:lon) ) + sin( radians(:lat) ) * sin( radians( p.Latitude ) ) ) ) AS distance 
				FROM propsio.properties p 
				WHERE p.StarRating >= :min_rating
				AND p.StarRating <= :max_rating
				AND p.Default_Image <> ''
				HAVING distance <= :proximity
				";
		
		if ($curated) {
			$sql .= " ORDER BY distance";
		} else {
			$sql .= " ORDER BY p.StarRating, distance";
		}

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':lat', $lat, PDO::PARAM_STR);
			$stmt->bindValue(':lon', $lon, PDO::PARAM_STR);
			$stmt->bindValue(':proximity', $proximity, PDO::PARAM_INT);
			$stmt->bindValue(':min_rating', $min_rating, PDO::PARAM_INT);
			$stmt->bindValue(':max_rating', $max_rating, PDO::PARAM_INT);
			
			$stmt->execute();
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				
				$row['key'] = $tools->keyConvert($row['id']);
				$row['default_image'] = str_replace('http:', '', $row['default_image']);
				$this->collection[$row['id']] = $row;	
				$this->ids[] = $row['id'];

				$status_read = true;
			}

		} catch (PDOException $e) {
				
			$this->errors['status'] = "Collection failed.";
			$this->errors['db'] = $e->getMessage();
		}

		return $status_read;

	}

	public function readDynamic($ids, $options='HOTEL_SUMMARY')
	{
		// Valid options include: 'ROOM_TYPES', 'ROOM_RATE_DETAILS', 'HOTEL_SUMMARY';
		$request = $this->deps['request'];
		$tools = $this->deps['tools'];

		$status_read = false;

		$include_details = 'true';
		
		$locale = 'en_US';
		$currency_code = 'USD';

		$url_parts[] = "minorRev=" . $this->deps['ean_rev'];
		$url_parts[] = "cid=" . $this->deps['ean_shop_cid'];
		$url_parts[] = "apiKey=" . $this->deps['ean_api_key'];
		$url_parts[] = "customerUserAgent=" . $request->agent;
		$url_parts[] = "customerIpAddress=" . $request->ip;
		$url_parts[] = "includeDetails=" . $include_details;
		$url_parts[] = "options=" . $options;
		$url_parts[] = "numberOfResults=" . "500";
		$url_parts[] = "locale=" . $locale;
		$url_parts[] = "currencyCode=" . $currency_code;
		$url_parts[] = "hotelIdList=" . implode(",", $ids);
		$url_parts[] = "room1=1";
		$url_parts[] = "maxRatePlanCount=500";
		$url_parts[] = "supplierCacheTolerance=MIN_ENHANCED";
		
		$url = "http://api.ean.com/ean-services/rs/hotel/v3/list?" . implode("&", $url_parts);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = json_decode(curl_exec($ch), true);
		

		if (is_array($response) && isset($response['HotelListResponse']['HotelList'])) {

			$hotel_count = $response['HotelListResponse']['HotelList']['@size'];
			if ($hotel_count > 0) {
				$hotel_set = $response['HotelListResponse']['HotelList']['HotelSummary'];

				foreach ($hotel_set as $hotel_item) {

					$hotel_id = $hotel_item['hotelId'];
					$this->dynamic[$hotel_id] = $hotel_item;
					$status_read = true;
				}
			}
		}
		
		return $status_read;
	}
}
