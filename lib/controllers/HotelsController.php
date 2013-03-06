<?php
//----------------------------------------------------------
// Hotel URL's
//
// /Hotels
//		GET:	Shows all hotels
//
// /Hotels/abbccd
//		GET:	Shows hotel 'abbccd' general information
//
// /Hotels/abbccd/Description
//		GET:	Shows hotel 'abbccd' description
//
// /Hotels/abbccd/Rates
//		GET:	Shows hotel 'abbccd' room and rate information (Expedia, Orbitz, Travelocity, etc.)
//
// /Hotels/abbccd/Images
//		GET:	Shows hotel 'abbccd' images
//
// /Hotels/abbccd/Amenities
//		GET:	Shows hotel 'abbccd' amenities
//
//----------------------------------------------------------
class HotelsController 
{
	private $deps;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;
	}

	public function processRequest($args='') 
	{		
		$request = $this->deps['request'];

		$verb = $request->verb;

		if (is_array($args) && $verb == 'GET') {			
			$key = $args[0];

			if (isset($args[1]) && $args[1] == 'Rates') {
				return $this->getRates($key);
			}	
			
			if (isset($args[1]) && $args[1] == 'Rate-Outlook') {
				return $this->getRateOutlook($key);
			}	

			return $this->getHotel($key);
		} 
	}

	private function getRates($key)
	{		
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		$hotel = new HotelRatesModel($this->deps);
		
		$form_url = "/Rates/$key";
		$customer_agent = urlencode($_SERVER['HTTP_USER_AGENT']);
		$customer_ip = urlencode($_SERVER['REMOTE_ADDR']);
		$include_details = true;
		$options = 'ROOM_TYPES';

		// All variables required to shop rates are looked for in the GET string, then SESSION, then set to defaults;		
		$locale = $_SESSION['locale'] = isset($_GET['locale']) ? $_GET['locale'] : (isset($_SESSION['locale']) ? $_SESSION['locale'] : 'en_US');		
		$currency_code = isset($_GET['currency_code']) ? $_GET['currency_code'] : (isset($_SESSION['currency_code']) ? $_SESSION['currency_code'] : 'USD');
		$arrival_date = $_SESSION['arrival_date'] = isset($_GET['arrival_date']) ? $_GET['arrival_date'] : (isset($_SESSION['arrival_date']) ? $_SESSION['arrival_date'] : date('m/d/Y', strtotime('next tuesday', strtotime('+2 weeks'))));
		$arrival_date_long = date('l, F jS', strtotime($arrival_date));
		$departure_date = $_SESSION['departure_date'] = isset($_GET['departure_date']) ? $_GET['departure_date'] : (isset($_SESSION['departure_date']) ? $_SESSION['departure_date'] : date('m/d/Y', strtotime('+3 days', strtotime($arrival_date))));
		$departure_date_long = date('l, F jS', strtotime($departure_date));

		$room_count = $adult_count = $child_count = 0;

		for ($i=1; $i<=8; $i++) {
			
			$adults_key = 'adults' . $i;

			$adults = ($i==1) ? $_SESSION[$adults_key] = (isset($_GET[$adults_key]) && $_GET[$adults_key] > 0) ? $_GET[$adults_key] : ((isset($_SESSION[$adults_key]) && $_SESSION[$adults_key] > 0) ? $_SESSION[$adults_key] : 2) : $_SESSION[$adults_key] = isset($_GET[$adults_key]) ? $_GET[$adults_key] : (isset($_SESSION[$adults_key]) ? $_SESSION[$adults_key] : 0); 

			if ($adults > 0) {
				
				$childs_group = array();
				for ($c=1; $c<7; $c++) {
						
					$child_key = 'child' . $i . 'c' . $c;
					$childs_age = $_SESSION[$child_key] = isset($_GET[$child_key]) ? $_GET[$child_key] : (isset($_SESSION[$child_key]) ? $_SESSION[$child_key] : 0);
					if ($childs_age > 0) {
						$childs_group[] = $childs_age;
						$template_vals[$child_key] = $childs_age;
						$child_count++;
					}					
				}
				
				if (count($childs_group) > 0) {
					$rooms[$i] = $adults . ',' . implode(",", $childs_group);
				} else {
					$rooms[$i] = $adults;
				}

				$template_vals['adults' . $i] = $adults;
				$adult_count += $adults;
			}			
		}	
		
		$adults_options = array("0"=>"--", "1"=>"1", "2"=>"2", "3"=>"3", "4"=>"4", "5"=>"5", "6"=>"6", "7"=>"7", "8"=>"8", "9"=>"9", "10"=>"10", "11"=>"11", "12"=>"12", "13"=>"13", "14"=>"14");
		$childrens_ages = array("0"=>"--", ".5"=>"<1", "1"=>"1", "2"=>"2", "3"=>"3", "4"=>"4", "5"=>"5", "6"=>"6", "7"=>"7", "8"=>"8", "9"=>"9", "10"=>"10", "11"=>"11", "12"=>"12", "13"=>"13", "14"=>"14", "15"=>"15", "16"=>"16", "17"=>"17");

		//$template = 'hotel.dates.html';
		//echo $view->render($template, array_merge($template_vals, array('form_url'=>$form_url, 'arrival_date'=>$arrival_date, 'departure_date'=>$departure_date, 'arrival_date_long'=>$arrival_date_long, 'departure_date_long'=>$departure_date_long, 'room_count'=>count($rooms), 'adult_count'=>$adult_count, 'child_count'=>$child_count, 'adults_options'=>$adults_options, 'childrens_ages'=>$childrens_ages)));

		$hotel_rates = '';
		if ($hotel->readRates($key, $customer_agent, $customer_ip, $include_details, $options, $locale, $currency_code, $arrival_date, $departure_date, $rooms)) {
			$hotel_rates = $hotel->rates;
		}
		
		$no_rate_message = "We're sorry but we can't find any rooms for these dates.<br />Please select different dates or try another property. Thank you.";

		$template = 'hotel.rates.html';
		echo $view->render($template, array('rates'=>$hotel_rates, 'no_rate_message'=>$no_rate_message));
	}

	private function getRateOutlook($key)
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		$hotel = new HotelRatesModel($this->deps);
		
		$customer_agent = urlencode($_SERVER['HTTP_USER_AGENT']);
		$customer_ip = urlencode($_SERVER['REMOTE_ADDR']);
		$include_details = false;
		$options = 'ROOM_TYPES';
		$locale = 'en_US';		
		$currency_code = 'USD';
		
		$cur_arrival_date = strtotime("next Tuesday");
		$begin_date = date("F jS, Y", strtotime("next Tuesday", $cur_arrival_date));

		for ($i=0; $i<50; $i++) {
			

			$cur_arrival_date = strtotime("next Tuesday", $cur_arrival_date);
			$cur_departure_date = strtotime("next Thursday", $cur_arrival_date);

			$arrival_dates[$i] = date("m/d/Y", $cur_arrival_date);
			$departure_dates[$i] = date("m/d/Y", $cur_departure_date);
			$end_date = date("F jS, Y", $cur_departure_date);
		}

		$rooms = "room1=1";
		

		$hotel_rates = '';
		if ($hotel->readRateOutlook($key, $customer_agent, $customer_ip, $include_details, $options, $locale, $currency_code, $arrival_dates, $departure_dates, $rooms)) {
			$hotel_rates = $hotel->rates;
		}	
		
		echo $view->render('hotel.rate.outlook.html', array('rates'=>$hotel_rates, 'begin_date'=>$begin_date, 'end_date'=>$end_date));
	}

	private function getHotel($key) 
	{		
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		$hotel = new HotelModel($this->deps, $key);
		$images = new HotelImagesModel($this->deps, $key);
		$description = new HotelDescriptionModel($this->deps, $key);
		$amenities = new HotelAmenitiesModel($this->deps, $key);
		
		$hotel->images = $images->collection;
		$hotel->description = $description->description;

		if (count($amenities->collection) > 0) {
			$len = count($amenities->collection);
			$hotel->amenities_left = array_slice($amenities->collection, 0, $len / 2);
			$hotel->amenities_right = array_slice($amenities->collection, $len / 2);
		}

		if ($request->output_type == 'json') {
			echo json_encode(array("status"=>"success", "hotel"=>$hotel));
		} else {
			echo $view->render('hotel.full.html', array('output_type'=>$request->output_type, "hotel"=>$hotel));
		}		
	}	
}
