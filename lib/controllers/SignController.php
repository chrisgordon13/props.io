<?php
class SignController 
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
			switch ($args[0]) {
				case 'Up':
					if ($verb == 'GET') {
						return $this->getSignUp();					
					}
					if ($verb == 'POST') {
						return $this->postSignUp($data);
					}
				case 'In':
					if ($verb == 'GET') {
						return $this->getLogIn();
					}
					if ($verb == 'POST') {
						return $this->postLogIn($data);
					}
				case 'Out':
					if ($verb == 'PUT' || $verb == 'GET') {
						return $this->putLogOut();
					}					
			}
		}
	}

	private function getSignUp()
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		$template = 'sign.up.html';
		echo $view->render($template, array('output_type'=>$request->output_type, 'csrf'=>$_SESSION['csrf']));
	}

	private function postSignUp($data)
	{
		$user = $this->deps['user'];
		$tools = $this->deps['tools'];

		if ($tools->checkCsrf()) {				
			if ($user->create($data)) {
				$user->password = '';
				echo json_encode(array("status"=>"success", "user"=>$user));
			} else {
				echo json_encode(array("status"=>"failure", "errors"=>$user->errors));
			}
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>array('csrf'=>true)));
		}
	}
	
	private function getLogIn()
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		$template = 'log.in.html';
		echo $view->render($template, array('output_type'=>$request->output_type, 'csrf'=>$_SESSION['csrf']));
	}

	private function postLogIn($data)
	{
		$user = $this->deps['user'];
		$tools = $this->deps['tools'];

		if ($tools->checkCsrf()) {				
			if ($user->authenticate($data)) {
				$user->password = '';
				echo json_encode(array("status"=>"success", "user"=>$user));				
			} else {
				echo json_encode(array("status"=>"failure", "errors"=>$user->errors));
			}
		} else {
			echo json_encode(array("status"=>"failure", "errors"=>array('csrf'=>true)));
		}
	}	

	private function putLogOut() 
	{
		$user = $this->deps['user'];

		$_SESSION = array();
		$user->authenticated = false;

		header("Location: /");
	}

	private function forgotPassword() 
	{
		// Todo
	}	
}
