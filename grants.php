<?PHP
//This script generates a static html file containing the grants one Orcid ID.
//Suggest a cron job run the script every evening
$orcid = ''; //Orcid ID
$uri = ''; //output file name
$location =''; //file path to output file location

//ask ORCID for a JSON response
$context = stream_context_create(array(
  'http' => array(
    'method' => "GET",
    'header' => "Accept: application/json"
    )
  )
);
//set_time_limit(20); //extends time for 20 seconds
include_once '../includes/apps.inc'; //Get connection information
//Connect
$db = new mysqli($apps_server, $apps_username, $apps_password, $apps_database);
if($db->connect_errno > 0){
    die('Unable to connect to database [' . $db->connect_error . ']');
}
$result = $db->query("SELECT fac_orcid,fac_uri FROM lib_fac WHERE fac_active='Yes' AND fac_orcid !='';");
$resultnum = $result->num_rows;
$db->close(); // Close DB
$i=0;
while($row = $result->fetch_assoc()){
set_time_limit(5);
$i++;
$orcid = $row['fac_orcid'];
$uri = $row['fac_uri'];
$outputrow='';
// Output Starts
$url = 'https://pub.orcid.org/v2.1/'.$orcid;
$orcidapi = file_get_contents($url, false, $context);
$data = json_decode($orcidapi, true);
$firstname = $data['person']['name']['given-names']['value'];
$lastname = $data['person']['name']['family-name']['value'];
$firstinitial = $firstname[0];

//ORCID Grants
$grants = $data['activities-summary']['fundings']['group']; 
if(!empty($grants)) {
	$outputrow .= '<div class="row">';
	$outputrow .= '<div class="col-sm-12">';	
	$outputrow .= '<div class="card"><div class="card-header"><strong>Grants</strong></div><ul class="card-text">';
	foreach($grants as $grant){
	$name = $grant['funding-summary'][0]['source']['source-name']['value'];
	$title = $grant['funding-summary'][0]['title']['title']['value'];
	$date = $grant['funding-summary'][0]['start-date']['year']['value'];
	$organization = $grant['funding-summary'][0]['organization']['name'];
	$outputrow .= '<li class="citation">'.$name.', <strong>'.$title.'</strong>';
	if($organization !='') $outputrow .=', '.$organization;
	if($date !='') $outputrow .= ', '.$date;
	$outputrow .= '</li>';
	}
	$outputrow .= '</ul></div></div>';
	$outputrow .= '</div>'; // end row

	$cachefile= $location.'/'.$uri.'.html'; // generate the static html file
	ob_start(); 
	//echo '<!-- Do not edit. This file is auto generated each day at 12:01am from /home/library2/scripts -->'."\r\n\r\n";
	echo $outputrow;
	$fp = fopen($cachefile,'w');
	fwrite($fp,ob_get_contents());
	fclose($fp);
	}
}
?>
