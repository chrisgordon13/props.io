<?php
class MapController 
{
	private $deps;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;
	}

	public function processRequest($args='') 
	{
		$view = $this->deps['view'];

		echo $view->render('map.html');
	}
}
