<?php
class RoleHelper
{
	public function check($user, $action, $object)
	{
		switch get_class($object) {
			case "ListHotelModel":
				break;
		}				
	}
}
