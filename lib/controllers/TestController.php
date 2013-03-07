<?php
class TestController 
{
	private $deps;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;
	}

	public function processRequest($args='') 
	{
            if (isset($args[0])) {
                echo "At least one arg " . $args[0];
            }
		echo phpinfo();
	}
}
