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
            
            if (isset($args[1])) {
                echo "At least 2 args " . $args[1];
            }
            
            $this->eTest();
            echo phpinfo();
	}
        
        private function eTest()
        {
            echo "test";
        }
}
