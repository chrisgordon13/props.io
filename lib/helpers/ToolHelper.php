<?php
class ToolHelper 
{
	private $found_rating;
	private $found_arrival_date;
	private $found_departure_date;

	public function __construct() 
	{
		$this->found_rating = 'fr_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$this->found_arrival_date = 'fad_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$this->found_departure_date = 'fdd_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}

	public function keyConvert($in, $to_num = false, $pad_up = 6, $passKey = null) 
	{	
		$index = "bcdfghjkmnpqrstvwxyz23456789";
		if ($passKey !== null) {

			for ($n = 0; $n<strlen($index); $n++) {
				$i[] = substr( $index,$n ,1);
			}

			$passhash = hash('sha256',$passKey);
			$passhash = (strlen($passhash) < strlen($index)) ? hash('sha512',$passKey) : $passhash;

			for ($n=0; $n < strlen($index); $n++) {
				$p[] =  substr($passhash, $n ,1);
			}

			array_multisort($p,  SORT_DESC, $i);
			$index = implode($i);
		}

		$base  = strlen($index);

		if ($to_num) {
			// Digital number  <<--  alphabet letter code
			$in  = strrev($in);
			$out = 0;
			$len = strlen($in) - 1;
			for ($t = 0; $t <= $len; $t++) {
				$bcpow = bcpow($base, $len - $t);
				$out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
			}

			if (is_numeric($pad_up)) {
				$pad_up--;
				if ($pad_up > 0) {
					$out -= pow($base, $pad_up);
				}
			}
			$out = sprintf('%F', $out);
			$out = substr($out, 0, strpos($out, '.'));
		} else {
			// Digital number  -->>  alphabet letter code
			if (is_numeric($pad_up)) {
				$pad_up--;
				if ($pad_up > 0) {
					$in += pow($base, $pad_up);
				}
			}

			$out = "";
			for ($t = floor(log($in, $base)); $t >= 0; $t--) {
				$bcp = bcpow($base, $t);
				$a   = floor($in / $bcp) % $base;
				$out = $out . substr($index, $a, 1);
				$in  = $in - ($a * $bcp);
			}
			$out = strrev($out); // reverse
		}

		return $out;
	}	

	public function getRating($query) 
	{	
		$query = $this->stripWords($query);

		$rating = 3;		

		$comp_ratings['1 star'] = 1;
		$comp_ratings['1-star'] = 1;
		$comp_ratings['one star'] = 1;
		$comp_ratings['2 star'] = 2;
		$comp_ratings['2-star'] = 2;
		$comp_ratings['two star'] = 1;
		$comp_ratings['3 star'] = 3;
		$comp_ratings['3-star'] = 3;
		$comp_ratings['three star'] = 1;
		$comp_ratings['4 star'] = 4;
		$comp_ratings['4-star'] = 4;
		$comp_ratings['four star'] = 1;
		$comp_ratings['5 star'] = 5;
		$comp_ratings['5-star'] = 5;
		$comp_ratings['five star'] = 1;

		foreach ($comp_ratings as $comp => $comp_rating) {
			
			$pos = stripos($query, $comp);

			if ($pos === false) {

			} else {
				$this->found_rating = $comp;
				$rating = $comp_rating;
			}
		}

		return $rating;
	}

	public function getTravelDates($query)
	{
		$found_dates = false;

		$query = $this->stripWords($query);
		
		$arrival_time = false;
		$departure_time = false;
		$looking_for_arrival_date = true;

		$unique_word = '';
		$u = 0;
		$last_hit_count = 0;
		$words = explode(" ", $query);
		$num_words = count($words);
		
		for ($i = 0; $i < $num_words; $i++) {
			for ($j = $i; $j < $num_words; $j++) {
				for ($k = $i; $k <= $j; $k++) {
					$unique_word .= $words[$k] . " ";
					$u++;
				}
				if (strtotime(trim($unique_word)) !== false) {
					if ($looking_for_arrival_date) {
						if ($u > $last_hit_count) {
							$arrival_time = strtotime(trim($unique_word));
							$arrival_month = date('F', $arrival_time);
							$last_hit_count = $u;
							$this->found_arrival_date = trim($unique_word);
						} else {
							$looking_for_arrival_date = false;
							$last_hit_count = 0;
							if (strtotime(trim($unique_word), $arrival_time) > $arrival_time) {
								$departure_time = strtotime(trim($unique_word), $arrival_time);
								$last_hit_count = $u;
								$this->found_departure_date = trim($unique_word);
							} 
						}
					} else {
						if ($u > $last_hit_count && strtotime(trim($unique_word), $arrival_time) > $arrival_time) {
							$departure_time = strtotime(trim($unique_word), $arrival_time);
							$last_hit_count = $u;
							$this->found_departure_date = trim($unique_word);
						}
					}
				}
				$unique_word = '';
				$u = 0;
			}
		}

		if ($arrival_time && !$departure_time) {
			
			$query = $this->stripWords($query);
		
			$departure_time = false;

			$unique_word = '';
			$u = 0;
			$last_hit_count = 0;
			$words = explode(" ", $query);
			$num_words = count($words);
			
			for ($i = 0; $i < $num_words; $i++) {
				for ($j = $i; $j < $num_words; $j++) {
					for ($k = $i; $k <= $j; $k++) {
						$unique_word .= $words[$k] . " ";
						$u++;
					}
					if (strtotime(trim($arrival_month . ' ' . trim($unique_word))) !== false) {
						if ($u > $last_hit_count && strtotime(trim($arrival_month . ' ' . trim($unique_word)), $arrival_time) > $arrival_time) {
							$departure_time = strtotime(trim($arrival_month . ' ' . trim($unique_word)), $arrival_time);
							$last_hit_count = $u;
							$this->found_departure_date = trim($unique_word);
						}						
					}
					$unique_word = '';
					$u = 0;
				}
			}
		}
		
		if ($arrival_time) {
			if ($departure_time) {
				$found_dates = ['arrival_date'=>date('m/d/Y', $arrival_time), 'departure_date'=>date('m/d/Y', $departure_time)];
			} else {
				$found_dates = ['arrival_date'=>date('m/d/Y', $arrival_time), 'departure_date'=>date('m/d/Y', strtotime('+1 day', $arrival_time))];
			}
		}

		return $found_dates;
	}	

	public function getGeoCenter($query) 
	{		
		$query = $this->stripWords($query);

		$unique_word = '';	
		$words = explode(" ",$query);
		$num_words = count($words);
		
		for ($i = 0; $i < $num_words; $i++) {
			for ($j = $i; $j < $num_words; $j++) {
				for ($k = $i; $k <= $j; $k++) {
				   $unique_word .= $words[$k] . "+";
				}
				$locations[] = $unique_word;
				$unique_word = '';
			}
		}

		$batch_query = implode('&location=', $locations);
	
		$url = "http://www.mapquestapi.com/geocoding/v1/batch?key=Fmjtd%7Cluu221uzlu%2C8x%3Do5-5f2gl&thumbMaps=false&location=$batch_query";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$places = json_decode(curl_exec($ch), true);

		if ($places['info']['statuscode'] == 0) {
			
			if (is_array($places)) {
				$results = $places['results'];
				foreach ($results as $locations) {
					foreach ($locations as $points) {
						foreach ($points as $point) {
							if (isset($point['geocodeQualityCode'])) {
								$geos[substr($point['geocodeQualityCode'], 0, 2)][] = array("lat"=>$point['latLng']['lat'], "lon"=>$point['latLng']['lng']);							
							}						
						}					
					}
				}
				
				if (isset($geos['P1'])) {
					return $geos['P1'][0];
				}
				if (isset($geos['L1'])) {
					return $geos['L1'][0];
				}
				if (isset($geos['I1'])) {
					return $geos['I1'][0];
				}
				if (isset($geos['B1'])) {
					return $geos['B1'][0];
				}
				if (isset($geos['B2'])) {
					return $geos['B2'][0];
				}
				if (isset($geos['B3'])) {
					return $geos['B3'][0];
				}
				if (isset($geos['Z3'])) {
					return $geos['Z3'][0];
				}
				if (isset($geos['Z2'])) {
					return $geos['Z2'][0];
				}
				if (isset($geos['Z1'])) {
					return $geos['Z1'][0];
				}
				if (isset($geos['A5'])) {
					return $geos['A5'][0];
				}
			}
		}

		return false;
	}

	public function stripWords($query) 
	{		
		$words = [$this->found_rating=>'', $this->found_arrival_date=>'', $this->found_departure_date=>'', 'hotels near'=>'', 'hotels around'=>'', 'hotels in'=>''];

		return trim(strtr(strtolower($query), $words));
	}

	public function checkCsrf() 
	{
		if(isset($_SESSION['csrf']) && isset($_POST['csrf']) && $_SESSION['csrf'] == $_POST['csrf']) {
			return true;
		} else {
			return false;
		}
	}

	public function multiRequest($data, $options = array())
	{
		// array of curl handles
	  $curly = array();
	  // data to be returned
	  $result = array();
	 
	  // multi handle
	  $mh = curl_multi_init();
	 
	  // loop through $data and create curl handles
	  // then add them to the multi-handle
	  foreach ($data as $id => $d) {
	 
		$curly[$id] = curl_init();
	 
		$url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
		curl_setopt($curly[$id], CURLOPT_URL,            $url);
		curl_setopt($curly[$id], CURLOPT_HEADER,         0);
		curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
	 
		// post?
		if (is_array($d)) {
		  if (!empty($d['post'])) {
			curl_setopt($curly[$id], CURLOPT_POST,       1);
			curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
		  }
		}
	 
		// extra options?
		if (!empty($options)) {
		  curl_setopt_array($curly[$id], $options);
		}
	 
		curl_multi_add_handle($mh, $curly[$id]);
	  }
	 
	  // execute the handles
	  $running = null;
	  do {
		curl_multi_exec($mh, $running);
	  } while($running > 0);
	 
	 
	  // get content and remove handles
	  foreach($curly as $id => $c) {
		$result[$id] = curl_multi_getcontent($c);
		curl_multi_remove_handle($mh, $c);
	  }
	 
	  // all done
	  curl_multi_close($mh);
	 
	  return $result;
	}

	public function pP($val) 
	{
		echo "<pre>";
		print_r($val);
		echo "</pre>";
	}
}
