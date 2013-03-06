<?php
//----------------------------------------------------------
// List Manager URL's
//
// /Lists
//		GET:	Shows all lists for current user
//		POST:	Adds a new list owned by current user
//
// /List-Manager/xmyrwe
//		GET:	Shows list 'xmyrwe'
//		PUT:	Updates list 'xmyrwe'
//		DELETE:	Deletes list 'xmyrwe'
//
// /Lists/xmyrwe/Searches
//		GET:	Shows all searches attached to list 'xmyrwe'
//		POST:	Adds a search to list 'xmyrwe'
//
// /Lists/xmyrwe/Searches/mnoopp
//		DELETE:	Deletes search 'mnoopp' from list 'xmyrwe'
//
// /Lists/xmyrwe/Hotels
//		GET:	Shows all hotels for list 'xmyrwe'
//		POST:	Adds a hotel to list 'xmyrwe'
//
// /Lists/xmyrwe/Hotels/abbccd
//		DELETE:	Deletes hotel 'abbccd' from list 'xmyrwe'
//
//----------------------------------------------------------
class ListsController 
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

			if (isset($args[1]) && $args[1] == 'Searches') {
				switch ($verb) {
					case 'GET':
						return $this->getListSearches($key);
					case 'POST':
						return $this->postListSearch($key, $data);
					case 'DELETE':
						return $this->deleteListSearch($key, $data);
				}
			}
			
			if (isset($args[1]) && $args[1] == 'Hotels') {
				switch ($verb) {
					case 'GET':
						return $this->getListHotels($key);
					case 'POST':
						return $this->postListHotel($key, $data);
					case 'DELETE':
						if (isset($args[2])) {
							return $this->deleteListHotel($args[2]);
						}
				}
			}

			switch ($verb) {
				case 'GET':
					return $this->getList($key);
				case 'PUT':
					return $this->putList($key, $data);
				case 'DELETE':
					return $this->deleteList($key);
			}
		} else {
			switch ($verb) {
				case 'GET':
					return $this->getLists();
				case 'POST':
					return $this->postList($data);
			}
		}
	}

	private function getListSearches($key)
	{
		// Todo: Check that this list exists
		// Todo: Check that user is allowed to access this list
		$list_searches = new ListSearchesModel($this->deps);
		
		if ($list_searches->read($key)) {
			echo json_encode(array("status"=>"success", "list_searches"=>$list_searches));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$list_searches->errors));
		}
	}

	private function postListSearch($key, $data)
	{
		$list_search = new ListSearchModel($this->deps);

		$data['list_key'] = $key;

		if ($list_search->create($data)) {
			echo json_encode(array("status"=>"success", "list_search"=>$list_search));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$list_search->errors));
		}
	}

	private function deleteListSearch($key, $data)
	{
		$list_search = new ListSearchModel($this->deps);	
		
		$search_key = $data['search_key'];

		if ($list_search->delete($key, $search_key)) {				
			echo json_encode(array("status"=>"success", "list_search"=>$list_search));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$list_search->errors));
		}
	}

	private function getListHotels($key)
	{
		// Todo: Check that this list exists
		// Todo: Check that user is allowed to access this list
		$list_hotels = new ListHotelsModel($this->deps);
		
		if ($list_hotels->read($key)) {
			echo json_encode(array("status"=>"success", "list_hotels"=>$list_hotels));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$list_hotels->errors));
		}
	}

	private function postListHotel($key, $data)
	{
		$list_hotel = new ListHotelModel($this->deps);

		$data['list_key'] = $key;

		if ($list_hotel->create($data)) {
			echo json_encode(array("status"=>"success", "list_hotel"=>$list_hotel));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$list_hotel->errors));
		}
	}

	private function deleteListHotel($key)
	{
		$list_hotel = new ListHotelModel($this->deps);		

		if ($list_hotel->delete($key)) {				
			echo json_encode(array("status"=>"success", "list_hotel"=>$list_hotel));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$list_hotel->errors));
		}
	}

	private function getList($key)
	{
		// Todo: Check that this list exists
		// Todo: Check that user is allowed to access this list
		$request = $this->deps['request'];
		$user = $this->deps['user'];
		$view = $this->deps['view'];

		$list = new ListModel($this->deps, $key);
		
		$searches = new ListSearchesModel($this->deps, $key);

		if (!is_array($list->errors)) {

			if ($request->output_type == 'json') {
				echo json_encode(array("status"=>"success", "list"=>$list));
			} else {
				$valid_ratings = array(1=>'1', 2=>'2', 3=>'3', 4=>'4', 5=>'5');
			
				$template = 'list.html';
				echo $view->render($template, array('output_type'=>$request->output_type, "list"=>$list, "valid_ratings"=>$valid_ratings, "searches"=>$searches->collection, "site"=>$this->deps['site']));
			}
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$list->errors));
		}
	}

	private function putList($key, $data)
	{
		// Todo: Check that this list exists
		// Todo: Check that user is allowed to access this list
		$list = new ListModel($this->deps);		

		if ($list->update($key, $data)) {				
			echo json_encode(array("status"=>"success", "list"=>$list));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$list->errors));
		}
	}

	private function deleteList($key)
	{
		// Todo: Check that this list exists
		// Todo: Check that user is allowed to access this list
		$list = new ListModel($this->deps);		

		if ($list->delete($key)) {				
			echo json_encode(array("status"=>"success", "list"=>$list));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$list->errors));
		}
	}

	private function getLists()
	{
		// Todo: Check if user is logged in
		$user = $this->deps['user'];

		$lists = new ListsModel($this->deps);

		if ($lists->read($user->id)) {
			echo json_encode(array("status"=>"success", "lists"=>$lists));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$lists->errors));
		}
	}

	private function postList($data)
	{
		// Todo: Check if user is logged in
		$user = $this->deps['user'];

		$list = new ListModel($this->deps);

		$data['user_id'] = $user->id;

		if ($list->create($data)) {
			echo json_encode(array("status"=>"success", "list"=>$list));
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>$list->errors));
		}
	}
}
