<?php
$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__) . '/../../';
require dirname(__FILE__) . '/../../lib/autoload.php';

class ToolHelperTest extends PHPUnit_Framework_TestCase 
{
	function testCanCreateToolHelper() 
	{
		$tools = new ToolHelper();
	}

	
}
