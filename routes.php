 <?php
// Routes define controller end points based on a requested URI.
// A lookup is done on the first 'piece' of the path. For example: https://props.io/<controller reference>/<argument 1>/<argument 2>/etc
// Subsequent 'pieces' are the responsibility of the controller referenced.
// Each controller class must implement a processRequest method.



//----------------------------------------------------------
// Default and Curated List URL's
//
// /
//		GET:	Shows default index page
//
// /xmyrwe
//		GET:	Shows index page for 'xmyrwe'
//
$routes['default'] = 'DefaultController';
//----------------------------------------------------------



//----------------------------------------------------------
// Hotel URL's
//
// /Hotels
//		GET:	Shows a list for all hotels
//
// /Hotels/abbccd
//		GET:	Shows hotel 'abbccd' general information
//
// /Hotels/abbccd/Description
//		GET:	Shows hotel 'abbccd' description
//
// /Hotels/abbccd/Rates
//		GET:	Shows hotel 'abbccd' room and rate information (Expedia, Orbitz, Travelocity, etc.)
//
// /Hotels/abbccd/Images
//		GET:	Shows hotel 'abbccd' images
//
// /Hotels/abbccd/Amenities
//		GET:	Shows hotel 'abbccd' amenities
//
$routes['hotels'] = 'HotelsController';
//----------------------------------------------------------



//----------------------------------------------------------
// List Manager URL's
//
// /Lists
//		GET:	Shows all lists for current user
//		POST:	Adds a new list owned by current user
//
// /Lists/xmyrwe
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
$routes['lists'] = 'ListsController';
//----------------------------------------------------------



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
$routes['searches'] = 'SearchesController';
//----------------------------------------------------------

//----------------------------------------------------------
$routes['map'] = 'MapController';
$routes['sign'] = 'SignController';
$routes['about'] = 'AboutController';
$routes['flush'] = 'FlushController';
$routes['test'] = 'TestController';
//----------------------------------------------------------
?>