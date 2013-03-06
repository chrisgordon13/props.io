<?php
class HotelRatesModel 
{	
	private $deps;
	public $collection, $rates;
	public $errors;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;
	}

	public function read($user_id)
	{
		$tools = $this->deps['tools'];
		$db = $this->deps['db'];

		$status_read = false;	
		
		$sql = "SELECT id, user_id, name, private, access_code 
				FROM lists 
				WHERE user_id = :user_id 
				AND date_archived IS NULL
				";

		try {
			$stmt = $db->prepare($sql);	
			
			$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
			
			$stmt->execute();
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				
				$row['key'] = $tools->keyConvert($row['id']);
				$this->collection[] = $row;			

				$status_read = true;
			}

		} catch (Exception $e) {
				
				$this->errors['status'] = "Collection failed.";
		}

		return $status_read;
	}

	public function readRates($key, $customer_agent, $customer_ip, $include_details, $options, $locale, $currency_code, $arrival_date, $departure_date, $room_details) 
	{
		$tools = $this->deps['tools'];
		$rates_found = false;

		$id = $tools->keyConvert($key, true);		
		$include_details = 'true';
		$options = 'ROOM_TYPES';

		$url_parts[] = "minorRev=" . $this->deps['ean_rev'];
		$url_parts[] = "cid=" . $this->deps['ean_shop_cid'];
		$url_parts[] = "apiKey=" . $this->deps['ean_api_key'];
		$url_parts[] = "customerUserAgent=" . $customer_agent;
		$url_parts[] = "customerIpAddress=" . $customer_ip;
		$url_parts[] = "includeDetails=" . $include_details;
		$url_parts[] = "options=" . $options;		
		$url_parts[] = "locale=" . $locale;
		$url_parts[] = "currencyCode=" . $currency_code;
		$url_parts[] = "hotelId=" . $id;
		$url_parts[] = "arrivalDate=" . $arrival_date;
		$url_parts[] = "departureDate=" . $departure_date;
		
		foreach ($room_details as $room_num => $room_occupancy) {
			$url_parts[] = "room" . $room_num . "=" . $room_occupancy;
		}

		$url = "http://api.ean.com/ean-services/rs/hotel/v3/avail?" . implode("&", $url_parts);

		//echo $url . "<br />";
	
		$this->buildCalendar($arrival_date, $departure_date);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = json_decode(curl_exec($ch), true);

		if (is_array($response) && isset($response['HotelRoomAvailabilityResponse']['HotelRoomResponse'])) {

			$this->room_count = $response['HotelRoomAvailabilityResponse']['@size'];
			$this->ta_rating = $response['HotelRoomAvailabilityResponse']['tripAdvisorRating'];
			$this->ta_count = $response['HotelRoomAvailabilityResponse']['tripAdvisorReviewCount'];

			$room_set = ($this->room_count > 1) ? $response['HotelRoomAvailabilityResponse']['HotelRoomResponse'] : array($response['HotelRoomAvailabilityResponse']['HotelRoomResponse']);

			foreach ($room_set as $rooms) {			
				
				$rate_code = $rooms['rateCode'];
				$rate['title'] = $rooms['rateDescription'];	
				$supplier_type = $rooms['supplierType'];

				if (!isset($rooms['RoomType']['@roomCode'])) {
					continue;
				} else {
					$room_type_code = $rooms['RoomType']['@roomCode'];
					$rate['description'] = $rooms['RoomType']['description'];
					$rate['description_long'] = $rooms['RoomType']['descriptionLong'];
				}
				
				if (isset($rooms['RateInfos']['RateInfo']['RoomGroup']['Room'][0])) {
					$rate_key_code = $rooms['RateInfos']['RateInfo']['RoomGroup']['Room'][0]['rateKey'];
				} else {
					$rate_key_code = $rooms['RateInfos']['RateInfo']['RoomGroup']['Room']['rateKey'];
				}
				
				foreach ($rooms['RateInfos']['RateInfo']['ChargeableRateInfo'] as $rate_key => $rate_data) {
					
					switch ($rate_key) {
								
						case '@total':

							$rate['total'] = $rate_data;
							break;
						case '@surchargeTotal':

							$rate['surcharge_total'] = $rate_data;
							break;
						case '@nightlyRateTotal':

							$rate['nightly_rate_total'] = $rate_data;
							break;
						case '@maxNightlyRate':

							$rate['max_rate'] = $rate_data;
							break;
						case '@currencyCode':

							$rate['currency_code'] = $rate_data;
							break;
						case '@commissionableUsdTotal':

							$rate['commissionable_total'] = $rate_data;
							break;
						case '@averageRate':

							$rate['ave_rate'] = $rate_data;
							break;
						case '@averageBaseRate':

							$rate['ave_base_rate'] = $rate_data;
							break;
						case 'NightlyRatesPerRoom':

							$rate_set = ($rate_data['@size'] > 1) ? $rate_data['NightlyRate'] : array($rate_data['NightlyRate']);
							$rate['daily_rates'] = array();
							foreach ($rate_set as $daily_rate) {
								$rate['daily_rates'][] = $daily_rate['@rate'];
								if (isset($daily_rate['@promo']) && $daily_rate['@promo'] == true) {
									$rate['daily_base_rates'][] = $daily_rate['@baseRate'];
								}
							}
							break;						
					}
				}	
				
				$rate['calendar'] = $this->assignCalendar($arrival_date, $rate['daily_rates']);
				$rate['booking_link'] = $this->buildBookingLink(415085, $id, 'en', 'USD', $arrival_date, $departure_date, count($room_details), $room_details, $rate_code, $room_type_code, $rate_key_code, $rate['total'], $supplier_type);
				$this->rates[] = $rate;
				$rates_found = true;
			}
		}		

		return $rates_found;
		
	}

	private function buildCalendar($arrival_date, $departure_date) 
	{		
		$departure_date_time = strtotime($departure_date);
		
		if (date("D", strtotime($arrival_date)) == "Sun") {
			$start_date_time = strtotime($arrival_date);
		} else {			
			$start_date_time = strtotime('last sunday', strtotime($arrival_date));
		}

		$week = 1;
		$seconds_in_a_day = 86400;

		for ($i=0; $i<50; $i++) {
			
			if ($i > 0 && ($i % 7 == 0)) {
				if ($start_date_time+($seconds_in_a_day*$i) >= $departure_date_time) {
					break;
				}
				$week++;
			}

			$day = date("j", $start_date_time+($seconds_in_a_day*$i));
			$this->calendar[$week][$day] = "";			
		}
	}

	private function assignCalendar($arrival_date, $daily_rates) 
	{		
		$arrival_date_time = strtotime($arrival_date);

		$seconds_in_a_day = 86400;
		$d = 0;
		foreach ($daily_rates as $rate) {
			
			$date_rate_num = date("j", $arrival_date_time+($seconds_in_a_day*$d));
			$assigned_rates[$date_rate_num] = $rate;
			$d++;
		}

		foreach ($this->calendar as $week_num => $week) {
			foreach ($week as $day_num => $day) {

				$calendar[$week_num][$day_num] = isset($assigned_rates[$day_num]) ? $assigned_rates[$day_num] : "--";
			}
		}
		
		return $calendar;
	}
	
	/* Build a booking link
	** $cid = Affiliate CID 
	** $hotel_id = Hotel's ID
	** $language = Language
	** $currency = Currency
	** $check_in = Check In date URL encoded
	** $check_out = Check Out date URL encode
	** $room_count = Number of rooms being booked
	** $room_data = Array of Room data to include Adult Count, Child Count and Child's ages
	** $rate_code = Rate code taken from availability request
	** $room_type_code = Room type code taken from availability request
	** $rate_key = Rate key taken from availability request
	** $total_price = Total price taken from availability request
	** $supplier_type = Supplier type taken from availability request
	**

	https://www.travelnow.com/templates/[your CID]/hotels/123748/book?
	lang=en (use lower case two letter language code)
	&currency=USD (use 3 letter currency code) 
	&checkin=09%2F19%2F2011
	&checkout=09%2F21%2F2011 
			(use URL-encoded date value in format of MM/DD/YYYY)
	&roomsCount=1
	&rooms[0].adultsCount=2
	&rooms[0].childrenCount=1
	&rooms[0].children[0].age=9 
	&hotelId=123748
	&rateCode=200142350
	&roomTypeCode=200029050
	&hrnQuoteKey=21ecf040-b16c-4863-8e37-d1469b322b04  
		(use the rateKey from the room availabilty choice)
	&selectedPrice=254.52 (total price)
	&pagename=ToStep1
	&supplierType=H

	Example room group for more than one room:

	https://www.travelnow.com/templates/[your CID]/hotels/123748/book?
	lang=en 
	&currency=USD 
	&checkin=09%2F19%2F2011
	&checkout=09%2F21%2F2011 
	&roomsCount=3
	&rooms[0].adultsCount=2
	&rooms[0].childrenCount=1
	&rooms[0].children[0].age=9 
	&rooms[1].adultsCount=2
	&rooms[1].childrenCount=2
	&rooms[1].children[0].age=10
	&rooms[1].children[1].age=12
	&rooms[2].adultsCount=2
	&rooms[2].childrenCount=1
	&rooms[2].children[0].age=5  
	&hotelId=123748
	&rateCode=200142350
	&roomTypeCode=200029050
	&hrnQuoteKey=21ecf040-b16c-4863-8e37-d1469b322b04  
	&selectedPrice=254.52  
	&pagename=ToStep1
	&supplierType=H
	*/
	private function buildBookingLink($cid=415085, $hotel_id, $language='en', $currency='USD', $check_in, $check_out, $room_count, $room_details, $rate_code, $room_type_code, $rate_key, $total_price, $supplier_type) 
	{		
		$url_parts[] = "lang=" . $language;
		$url_parts[] = "currency=" . $currency;
		$url_parts[] = "checkin=" . urlencode($check_in);
		$url_parts[] = "checkout=" . urlencode($check_out);
		$url_parts[] = "roomsCount=" . $room_count;
		
		//tools::pP($room_details);
		$i=0;
		foreach ($room_details as $room_string) {
			
			$room = explode(",", $room_string);

			$url_parts[] = "rooms[$i].adultsCount=" . array_shift($room);
			$child_count = count($room);					
			if ($child_count > 0) {
				$url_parts[] = "rooms[$i].childrenCount=" . $child_count;
				foreach ($room as $c => $child_age) {
					$url_parts[] = "rooms[$i].children[$c].age=" . $child_age;
				}
			}
			$i++;
		}
		/*
		for ($i=0; $i<$room_count; $i++) {
			$url_parts[] = "rooms[$i].adultsCount=" . $room_data[$i]['adult_count'];
			if (isset($room_data[$i]['child_count'])) {
				$url_parts[] = "rooms[$i].childrenCount=" . $room_data[$i]['child_count'];
				foreach ($room_data[$i]['children'] as $c => $age) {
					$url_parts[] = "rooms[$i].children[$c].age=" . $age;
				}
			}
		}
		*/
		$url_parts[] = "hotelId=" . $hotel_id;
		$url_parts[] = "rateCode=" . $rate_code;
		$url_parts[] = "roomTypeCode=" . $room_type_code;
		$url_parts[] = "hrnQuoteKey=" . $rate_key;
		$url_parts[] = "selectedPrice=" . $total_price;
		$url_parts[] = "pagename=ToStep1";
		$url_parts[] = "supplierType=" . $supplier_type;

		return "https://book.props.io/templates/$cid/hotels/$hotel_id/book?" . implode("&", $url_parts);	
		


		/*
		foreach ($room_details as $room_string) {

			$room_out = array();
			$room = explode(",", $room_string);

			$room_out['adult_count'] = array_shift($rooms);

			$child_count = count($room);					
			if ($child_count > 0) {
				$room_out['child_count'] = $child_count;
				foreach ($room as $child_age) {
					$room_out['children'][] = array('age'=>$child_age);
				}
			}
			$room_data[] = $room_out;
		}
		*/
	}

	public function readRateOutlook($key, $customer_agent, $customer_ip, $include_details, $options, $locale, $currency_code, $arrival_dates, $departure_dates) 
	{
		$tools = $this->deps['tools'];

		$status_read = false;

		$id = $tools->keyConvert($key, true);
		
		$api_keys = array("598cxzmwqw9h3fgp48tvsgah", "gqpgupdzu2zsfj7ab5h6dnc3", "y8hp88b5t3tyc4ez44gwspem", "yhujf6pcj4g5meabtpye69w3", "s92c8tq2ub4fg85p443adcgp", "beqgxk53kms7qpm9jz2rqb8a", "3e3mmb9ptn4mp6fpyywjnbez", "4kptn3gmh5m6em7uy3datd7z", "9qcpyz8j2g2krt6s6x22mfgv", "kcgtfs2yh3tk4ts5qvznp6gb", "kd6t33n43gzhpnm23q96menv", "2cnybubdaxqwa25xz9xw47w4", "ge8t3pg9sqxnm97f92z9rkvg", "8x5w7g8skmaqg3zw9khua95q", "9sjdmz77qm38fa5p92rkzgpv", "puw69yaufzkdt58fycp77v7u", "pwx5kekwy3nnc278f3eug53d", "cead39p74v86jcchnev54dz6", "jbr6s247pbc2v9cn6z59tu3g", "m7kpgzuwp5kjgm7r7r329kbu", "bejjhczethf2zvd5v7cxn3x2", "umc3hmxbt5ngjyr8fawybt9j", "3jzx4shkxfu7pvzhjdtchazk", "45b33bwbpm46urmq7vq76ymn", "5w9ku46ddhmdxsdqzwgnqzpd", "598cxzmwqw9h3fgp48tvsgah", "gqpgupdzu2zsfj7ab5h6dnc3", "y8hp88b5t3tyc4ez44gwspem", "yhujf6pcj4g5meabtpye69w3", "s92c8tq2ub4fg85p443adcgp", "beqgxk53kms7qpm9jz2rqb8a", "3e3mmb9ptn4mp6fpyywjnbez", "4kptn3gmh5m6em7uy3datd7z", "9qcpyz8j2g2krt6s6x22mfgv", "kcgtfs2yh3tk4ts5qvznp6gb", "kd6t33n43gzhpnm23q96menv", "2cnybubdaxqwa25xz9xw47w4", "ge8t3pg9sqxnm97f92z9rkvg", "8x5w7g8skmaqg3zw9khua95q", "9sjdmz77qm38fa5p92rkzgpv", "puw69yaufzkdt58fycp77v7u", "pwx5kekwy3nnc278f3eug53d", "cead39p74v86jcchnev54dz6", "jbr6s247pbc2v9cn6z59tu3g", "m7kpgzuwp5kjgm7r7r329kbu", "bejjhczethf2zvd5v7cxn3x2", "umc3hmxbt5ngjyr8fawybt9j", "3jzx4shkxfu7pvzhjdtchazk", "45b33bwbpm46urmq7vq76ymn", "5w9ku46ddhmdxsdqzwgnqzpd");
		
		for ($i=0; $i<count($arrival_dates); $i++) {
			
			$url_parts = array();
			$url_parts[] = "minorRev=" . $this->deps['ean_rev'];
			$url_parts[] = "cid=" . $this->deps['ean_shop_cid'];
			$url_parts[] = "apiKey=" . $api_keys[$i];
			$url_parts[] = "customerUserAgent=" . $customer_agent;
			$url_parts[] = "customerIpAddress=" . $customer_ip;
			$url_parts[] = "includeDetails=" . $include_details;
			$url_parts[] = "options=" . $options;		
			$url_parts[] = "locale=" . $locale;
			$url_parts[] = "currencyCode=" . $currency_code;
			$url_parts[] = "hotelId=" . $id;
			$url_parts[] = "arrivalDate=" . $arrival_dates[$i];
			$url_parts[] = "departureDate=" . $departure_dates[$i];
			$url_parts[] = "room1=1";
			$url_parts[] = "maxRatePlanCount=50";
			$url_parts[] = "supplierCacheTolerance=MED";

			$urls[] = "http://api.ean.com/ean-services/rs/hotel/v3/avail?" . implode("&", $url_parts);	
			
			$this->rates[$i]['arrival_date'] = date("m/d/Y", strtotime($arrival_dates[$i]));
			$this->rates[$i]['departure_date'] = date("m/d/Y", strtotime($departure_dates[$i]));
		}

		$response_set = $tools->multiRequest($urls);
 
		foreach ($response_set as $response_key => $json_response) {

			$response = json_decode($json_response, true);

			if (is_array($response) && isset($response['HotelRoomAvailabilityResponse']['HotelRoomResponse'])) {

				$this->room_count = $response['HotelRoomAvailabilityResponse']['@size'];
				$room_set = ($this->room_count > 1) ? $response['HotelRoomAvailabilityResponse']['HotelRoomResponse'] : array($response['HotelRoomAvailabilityResponse']['HotelRoomResponse']);
				$low_ave_rate = 99999999999999;
				$high_ave_rate = 0;

				foreach ($room_set as $rooms) {						

					if (!isset($rooms['RoomType']['@roomCode'])) {
						continue;
					} else {
						$rate['description'] = $rooms['RoomType']['description'];
					}
					
					foreach ($rooms['RateInfos']['RateInfo']['ChargeableRateInfo'] as $rate_key => $rate_data) {
						
						switch ($rate_key) {
									
							case '@currencyCode':

								//$rate['currency_code'] = $rate_data;
								break;
							case '@averageRate':

								$rate['ave_rate'] = $rate_data;
								break;
							case 'NightlyRatesPerRoom':

								$rate_set = ($rate_data['@size'] > 1) ? $rate_data['NightlyRate'] : array($rate_data['NightlyRate']);
								$rate['daily_rates'] = array();
								foreach ($rate_set as $daily_rate) {
									$rate['daily_rates'][] = $daily_rate['@rate'];
								}
								break;						
						}
					}	
					
					if ($rate['ave_rate'] < $low_ave_rate) {
						$this->rates[$response_key]['low_description'] = $rate['description'];
						$this->rates[$response_key]['low_ave_rate'] = $rate['ave_rate'];
						$this->rates[$response_key]['low_daily_rates'] = $rate['daily_rates'];
						$low_ave_rate = $rate['ave_rate'];
						$status_read = true;
					}

					if ($rate['ave_rate'] > $high_ave_rate) {
						$this->rates[$response_key]['high_description'] = $rate['description'];
						$this->rates[$response_key]['high_ave_rate'] = $rate['ave_rate'];
						$this->rates[$response_key]['high_daily_rates'] = $rate['daily_rates'];
						$high_ave_rate = $rate['ave_rate'];
						$status_read = true;
					}					
				}
				
				$rate = array();
			}
		}		

		return $status_read;		
	}
}
