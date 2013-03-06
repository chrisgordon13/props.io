<?php
class FlushController 
{
	
	private $deps;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;
	}

	public function processRequest($args='') 
	{
		$cache = new Memcache;
		$cache->connect('localhost', 11211);
		$cache->flush();
	}
}
?>