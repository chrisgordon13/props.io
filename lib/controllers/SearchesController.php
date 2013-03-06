<?php
//----------------------------------------------------------
// Search Manager URL's
//
// /Searches
//		GET:	Shows all searches for current user
//		POST:	Adds a new search owned by current user
//
// /Searches/mnoopp
//		GET:	Shows search 'mnoopp'
//		PUT:	Updates search 'mnoopp'
//		DELETE:	Deletes search 'mnoopp'
//
//----------------------------------------------------------
class SearchesController 
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
		$data = $request->data;

		if (is_array($args)) {			
			$key = $args[0];

			switch ($verb) {
				case 'GET':
					return $this->getSearch($key);
				case 'PUT':
					return $this->putSearch($key, $data);
				case 'DELETE':
					return $this->deleteSearch($key);
			}
		} else {			
			switch ($verb) {
				case 'GET':
					return $this->getSearches();
				case 'POST':
					return $this->postSearch($data);
			}
		}
	}

	private function getSearch($key) 
	{
		// Todo: Check that this search exists
		// Todo: Check that user is allowed to access this search
		$request = $this->deps['request'];
		$user = $this->deps['user'];
		$view = $this->deps['view'];

		$search = new SearchModel($this->deps);		

		if ($search->read($key)) {
			if ($request->output_type == 'json') {
				echo json_encode(array("status"=>"success", "search"=>$search));
			} else {
				$valid_ratings = array(1=>'1', 2=>'2', 3=>'3', 4=>'4', 5=>'5');
				$valid_proximities = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,25,30,35,40,45,50);

				$template = 'search.html';
				echo $view->render($template, array('output_type'=>$request->output_type, "search"=>$search, "valid_ratings"=>$valid_ratings, "valid_proximities"=>$valid_proximities));
			}			
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$search->errors));
		}
	}

	private function putSearch($key, $data)
	{
		// Todo: Check that this search exists
		// Todo: Check that user is allowed to access this search
		$search = new SearchModel($this->deps);		

		if ($search->update($key, $data)) {				
			echo json_encode(array("status"=>"success", "search"=>$search));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$search->errors));
		}
	}

	private function deleteSearch($key)
	{
		// Todo: Check that this search exists
		// Todo: Check that user is allowed to access this search
		$search = new SearchModel($this->deps);		

		if ($search->delete($key)) {				
			echo json_encode(array("status"=>"success", "search"=>$search));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$search->errors));
		}
	}

	private function getSearches()
	{
		// Todo: Check if user is logged in
		$user = $this->deps['user'];

		$searches = new SearchesModel($this->deps);

		if ($searches->read($user->id)) {
			echo json_encode(array("status"=>"success", "searches"=>$searches));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$searches->errors));
		}
	}

	private function postSearch($data)
	{
		// Todo: Check if user is logged in
		$user = $this->deps['user'];

		$search = new SearchModel($this->deps);

		$data['user_id'] = $user->id;

		if ($search->create($data)) {
			echo json_encode(array("status"=>"success", "search"=>$search));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$search->errors));
		}
	}
}
