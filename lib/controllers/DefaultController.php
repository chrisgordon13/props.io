<?php
//----------------------------------------------------------
// Default and Curated Index URL's
//
// /
//		GET:	Shows all hotels
//
// /xmyrwe
//		GET:	Shows all hotels for list 'xmyrwe'
//
//----------------------------------------------------------
class DefaultController 
{
	private $deps;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;
	}

	public function processRequest($args='') 
	{	
		if ($this->deps['list'] != '') {
			
			$list = new ListModel($this->deps, $this->deps['list']);

			if ($list->id > 0) {
				return $this->getCuratedIndex($list->key);
			}
		}

		return $this->getDefaultIndex();		
	}

	private function getCuratedIndex($key)
	{
		$request = $this->deps['request'];
		$user = $this->deps['user'];
		$view = $this->deps['view'];
		$tools = $this->deps['tools'];		

		$list = new ListModel($this->deps, $key);
		$searches = new ListSearchesModel($this->deps);
		$hotels = new HotelsModel($this->deps);
		$rates = new HotelsRatesModel($this->deps);

		$searches->readDistinct($key);		

		$sample_cities = ['San Francisco'=>['lat'=>37.7750, 'lon'=>-122.4183], 'New York'=>['lat'=>40.7142, 'lon'=>-74.0064], 'Chicago'=>['lat'=>41.8500, 'lon'=>-87.6500], 'Seattle'=>['lat'=>47.6097, 'lon'=>-122.3331], 'San Diego'=>['lat'=>32.7153, 'lon'=>-117.1564]];
		$city = array_rand($sample_cities);
		$placeholder = 'Hotels in ' . $city . ' from ' . date('F jS', strtotime($this->deps['arrival_date'])) . ' until ' . date('F jS', strtotime($this->deps['departure_date']));
		$sample_lat = $sample_cities[$city]['lat'];
		$sample_lon = $sample_cities[$city]['lon'];

		$query = ['lat'=>$sample_lat, 'lon'=>$sample_lon, 'proximity'=>$list->proximity, 'min_rating'=>3, 'max_rating'=>5];
		$search_text = '';
		$page_nav = '?output_type=partial&Page=2';

		$fixed_variable_pricing = $list->fixed_variable_pricing;
		$max_price = $list->max_price;
		$ave_price = $list->max_price;
		$max_price_condition = $list->max_price_condition;
		$max_price_percent = $list->max_price_percent;
		$max_price_rating = $list->max_price_rating;

		$travel_dates = ['arrival_date'=>$this->deps['arrival_date'], 'departure_date'=>$this->deps['departure_date']];

		if (isset($request->params['search_text'])) {

			$search_text = $placeholder = $request->params['search_text'];				
			
			$search_found = false;
			foreach ($searches->collection as $search_compare) {
				$pos = stripos($search_text, $search_compare['name']);
				if ($pos !== false) {
					$search_found = true;
					$search = new SearchModel($this->deps, $search_compare['search_key']); 
				}

			}

			if ($search_found) {
				
				$query = ['lat'=>$search->latitude, 'lon'=>$search->longitude, 'proximity'=>$search->proximity, 'min_rating'=>$search->min_rating, 'max_rating'=>$search->max_rating];

				$fixed_variable_pricing = $search->fixed_variable_pricing;
				$max_price = $search->max_price;
				$max_price_condition = $search->max_price_condition;
				$max_price_percent = $search->max_price_percent;
				$max_price_rating = $search->max_price_rating;

				if ($found_dates = $tools->getTravelDates($search_text)) {
					$travel_dates = array_merge($travel_dates, $found_dates);
				}

			} else {
				
				$rating = $tools->getRating($search_text);
				
				if ($found_dates = $tools->getTravelDates($search_text)) {
					$travel_dates = array_merge($travel_dates, $found_dates);
				}								
				
				if ($search_points = $tools->getGeoCenter($search_text)) {	
					$search_points['rating'] = $rating;
					$search_points['min_rating'] = $rating;
					$query = array_merge($query, $search_points);
				}				
			}

			$page_nav .= '&search_text=' . urlencode($search_text);
		}				
	
		$hotels_out = array();
		$hotel_json = array();
		if ($hotels->read($query['lat'], $query['lon'], 25, $query['min_rating'], $query['max_rating'])) {
		
			$rates->read($query['lat'], $query['lon'], 25, $query['min_rating'], $query['max_rating'], $travel_dates['arrival_date'], $travel_dates['departure_date']);

			if ($fixed_variable_pricing == 'variable') {
				
				$ave_price = $rates->getAveragePrice($max_price_condition, $max_price_rating, $query['proximity']);
				if ($max_price_percent > 0) {
					$max_price = $ave_price + ($ave_price * ($max_price_percent/100));
				} else {
					$max_price = $ave_price;
				}
			} else {

				$ave_price = $rates->getAveragePrice('average', 5);
			}
			
			foreach ($hotels->collection as $id=>$hotel) {
				if (array_key_exists($id, $rates->collection)) {
					if (isset($rates->collection[$id]['low_rate']) && $rates->collection[$id]['low_rate'] <= $max_price) {
						$hotel['low_rate'] = $rates->collection[$id]['low_rate'];
						$hotel['ta_rating_img'] = $rates->collection[$id]['ta_rating_img'];
						$hotels_out[] = $hotel;
						$hotel_json[] = array('key'=>$hotel['key'], 'latitude'=>$hotel['latitude'], 'longitude'=>$hotel['longitude']);
					}
				}
			}
		}

		if ($request->output_type == 'map') {
			echo $view->render('map.html');
		} else {
			echo $view->render('index.curated.html', array('output_type'=>$request->output_type, 'search_text'=>$search_text, 'list'=>$list, 'searches'=>$searches->collection, 'placeholder'=>$placeholder, 'hotels'=>$hotels_out, 'page_nav'=>$page_nav, 'csrf'=>$_SESSION['csrf'], 'query'=>json_encode($query), 'hotel_json'=>json_encode($hotel_json), 'max_price'=>$max_price, 'ave_price'=>$ave_price, 'arrival_date'=>$travel_dates['arrival_date'], 'departure_date'=>$travel_dates['departure_date']));
		}
	}

	private function getDefaultIndex()
	{
		$request = $this->deps['request'];
		$user = $this->deps['user'];
		$view = $this->deps['view'];
		$tools = $this->deps['tools'];		

		$hotels = new HotelsModel($this->deps);
		$searches = new SearchesModel($this->deps);
		$lists = new ListsModel($this->deps);

		$authenticated = $user->authenticated;		

		$sample_cities = ['San Francisco'=>['lat'=>37.7750, 'lon'=>-122.4183], 'New York'=>['lat'=>40.7142, 'lon'=>-74.0064], 'Chicago'=>['lat'=>41.8500, 'lon'=>-87.6500], 'Seattle'=>['lat'=>47.6097, 'lon'=>-122.3331], 'San Diego'=>['lat'=>32.7153, 'lon'=>-117.1564]];
		$city = array_rand($sample_cities);
		$placeholder = '3 Star Hotels in ' . $city;
		$sample_lat = $sample_cities[$city]['lat'];
		$sample_lon = $sample_cities[$city]['lon'];

		$query = ['lat'=>$sample_lat, 'lon'=>$sample_lon, 'proximity'=>20, 'min_rating'=>3, 'max_rating'=>5];
		$search_text = '';
		$page_nav = '?output_type=partial&Page=2';

		if (isset($request->params['search_text'])) {

			$search_text = $placeholder = $request->params['search_text'];

			$rating = $tools->getRating($search_text);	
			
			if ($search_points = $tools->getGeoCenter($search_text)) {	
				$search_points['rating'] = $rating;
				$search_points['min_rating'] = $rating;
				$query = array_merge($query, $search_points);
			}

			$page_nav .= '&search_text=' . urlencode($search_text);
		}				

		$hotels_out = array();
		$hotel_json = array();
		if ($hotels->read($query['lat'], $query['lon'], $query['proximity'], $query['min_rating'], $query['max_rating'], false)) {

			//$hotels->readDynamic($hotels->ids);

			foreach ($hotels->collection as $hotel_id => $hotel) {
				/*
				$hotel['ta_rating_img'] = isset($hotels->dynamic[$hotel_id]['tripAdvisorRatingUrl']) ? str_replace('http:', '', $hotels->dynamic[$hotel_id]['tripAdvisorRatingUrl']) : '//www.tripadvisor.com/img/cdsi/img2/ratings/traveler/0.0-12345-4.gif';
				if (isset($hotels->dynamic[$hotel_id]['lowRate'])) {
					$hotel['low_rate'] = $hotels->dynamic[$hotel_id]['lowRate'];
				}
				*/
				$hotel['ta_rating_img'] = ($hotel['TripAdvisorRatingURL'] != '') ? str_replace('http:', '', $hotel['TripAdvisorRatingURL']) : '//www.tripadvisor.com/img/cdsi/img2/ratings/traveler/0.0-12345-4.gif';
				$hotels_out[] = $hotel;
				$hotel_json[] = array('key'=>$hotel['key'], 'name'=>$hotel['name'], 'image'=>$hotel['default_image'], 'ta_rating'=>$hotel['star_rating'], 'star_rating'=>$hotel['star_rating'], 'low_rate'=>$hotel['low_rate'], 'currency'=>$hotel['currency'], 'distance'=>$hotel['distance'], 'latitude'=>$hotel['latitude'], 'longitude'=>$hotel['longitude']);
			}
		}
		
		$search_collection = ($searches->read($user->id)) ? $searches->collection : false;
		$list_collection = ($lists->read($user->id)) ? $lists->collection: false;
	
		if ($request->output_type == 'map') {
			echo $view->render('map.html');
		} else {
			echo $view->render('index.html', array('output_type'=>$request->output_type, 'search_text'=>$search_text, 'placeholder'=>$placeholder, 'hotels'=>$hotels_out, 'authenticated'=>$authenticated, 'lists'=>$list_collection, 'searches'=>$search_collection, 'page_nav'=>$page_nav, 'csrf'=>$_SESSION['csrf'], 'query'=>json_encode($query), 'hotel_json'=>json_encode($hotel_json)));
		}
		
	}

	private function getPagination() 
	{		
		if (isset($_GET['Page']) && ctype_digit($_GET['Page'])) {
			$pagination['page'] = (int)trim($_GET['Page']);
		} else {
			$pagination['page'] = 1;
		}

		$pagination['offset'] = ($pagination['page'] * 50) - 50;
		$pagination['limit'] = 50;

		return $pagination;
	}
}
