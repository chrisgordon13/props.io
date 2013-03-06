<?php
class RequestHelper
{
	public $ip, $verb, $params, $output_type, $data;

	public function __construct()
	{
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->agent = urlencode($_SERVER['HTTP_USER_AGENT']);
		$this->verb = strtoupper($_SERVER['REQUEST_METHOD']);
		parse_str($_SERVER['QUERY_STRING'], $this->params);
		$this->output_type = isset($this->params['output_type']) ? $this->params['output_type'] : 'full';
		$this->data = array();

		switch ($this->verb) {
			case "GET":
				$this->data = $_GET;
				break;
			case "POST":
				$this->data = $_POST;
				break;
			case "PUT":
				parse_str(file_get_contents("php://input"),$this->data);
				break;
			case "DELETE":
				parse_str(file_get_contents("php://input"),$this->data);
				break;
		}		
	}
}
