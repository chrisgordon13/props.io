<?php
class HotelsRatesModel 
{	
	private $deps;
	public $collection;
	public $errors;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;
		
		$this->collection = array();
	}

	public function read($lat, $lon, $proximity=25, $min_rating=3, $max_rating=5, $arrival='', $departure='')
	{
		$request = $this->deps['request'];
		$tools = $this->deps['tools'];

		$status_read = false;

		$include_details = 'true';
		$locale = 'en_US';
		$currency_code = 'USD';
		$arrival_date = ($arrival != '') ? $arrival : $this->deps['arrival_date'];
		$departure_date = ($departure != '') ? $departure : $this->deps['departure_date'];

		$url_parts[] = "minorRev=" . $this->deps['ean_rev'];
		$url_parts[] = "cid=" . $this->deps['ean_shop_cid'];
		$url_parts[] = "apiKey=" . $this->deps['ean_api_key'];
		$url_parts[] = "customerUserAgent=" . $request->agent;
		$url_parts[] = "customerIpAddress=" . $request->ip;
		$url_parts[] = "includeDetails=" . $include_details;
		$url_parts[] = "numberOfResults=" . "500";
		$url_parts[] = "locale=" . $locale;
		$url_parts[] = "currencyCode=" . $currency_code;
		//$url_parts[] = "hotelIdList=" . implode(",", $ids);
		$url_parts[] = "arrivalDate=" . $arrival_date;
		$url_parts[] = "departureDate=" . $departure_date;
		$url_parts[] = "room1=1";
		$url_parts[] = "latitude=" . $lat;
		$url_parts[] = "longitude=" . $lon;
		$url_parts[] = "searchRadius=" . $proximity;
		$url_parts[] = "searchRadiusUnit=" . 'M';
		$url_parts[] = "minStarRating=" . $min_rating;
		$url_parts[] = "maxStarRating=" . $max_rating;		
		$url_parts[] = "maxRatePlanCount=500";
		$url_parts[] = "sort=PROXIMITY";
		$url_parts[] = "supplierCacheTolerance=MIN_ENHANCED";
		
		//foreach ($room_details as $room_num => $room_occupancy) {
		//	$url_parts[] = "room" . $room_num . "=" . $room_occupancy;
		//}
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
					if (isset($hotel_item['RoomRateDetailsList']['RoomRateDetails'])) {
						$hotel_id = $hotel_item['hotelId'];
						$this->collection[$hotel_id]['star_rating'] = $hotel_item['hotelRating'];
						$this->collection[$hotel_id]['ta_rating_img'] = isset($hotel_item['tripAdvisorRatingUrl']) ? str_replace('http:', '', $hotel_item['tripAdvisorRatingUrl']) : '//www.tripadvisor.com/img/cdsi/img2/ratings/traveler/0.0-12345-4.gif';
						$this->collection[$hotel_id]['proximity'] = $hotel_item['proximityDistance'];
						$this->collection[$hotel_id]['rates'] = $hotel_item['RoomRateDetailsList']['RoomRateDetails'];

						$status_read = true;
					}
				}
			}
		}
		
		return $status_read;
	}

	public function getAveragePrice($condition, $rating, $proximity) {
		
		$rate_total=0;
		$rate_count=0;		

		if (is_array($this->collection)) {
			
			foreach ($this->collection as $id=>$rate_set) {
				
				$low_rate = $this->getLowRate($rate_set['rates']);

				if ($low_rate > 0) {
					$this->collection[$id]['low_rate'] = $low_rate;
					if ($rate_set['star_rating'] == $rating && $rate_set['proximity'] <= $proximity) {
						$rate_total += $low_rate;
						$rate_count++;
					}
				}
			}			
		}

		if ($rate_total > 0 && $rate_count > 0) {
			return $rate_total/$rate_count;
		} else {
			return 0;
		}
	}

	private function getLowRate($rate_set) {
		
		$low_rate = 0;

		if (isset($rate_set['RateInfos'])) {
			
			$low_rate = $rate_set['RateInfos']['RateInfo']['ChargeableRateInfo']['@averageRate'];
		} else {			
			
			foreach ($rate_set as $rate) {

				if ($rate['RateInfos']['RateInfo']['ChargeableRateInfo']['@averageRate'] < $low_rate || $low_rate == 0) {
					$low_rate = $rate['RateInfos']['RateInfo']['ChargeableRateInfo']['@averageRate'];
				}
			}
		}

		return $low_rate;
	}
}
