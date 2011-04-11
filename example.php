<pre><?php
require('phpBooli.php');

$key = '';
$secret = '';

try {
	$booli	= new phpBooli($key, $secret);

	$area = '';
	$filter = array(
		'centerLat' => 59.8569131,
		'centerLong' => 17.6359056,
		'radius' => 10
	);
	$offset	= 0;
	$count = 5;

	$listing = $booli->listing($area, $filter, $offset, $count);
} catch(Exception $e) {
	echo $e->getMessage();
}

print_r($listing);