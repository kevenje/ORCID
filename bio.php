<?PHP
//This script generates a static html file containing the Biography, Employment & Education Histories for one Orcid ID.
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

// Output Starts
$url = 'https://pub.orcid.org/v2.1/'.$orcid; 
$orcidapi = file_get_contents($url, false, $context);
$data = json_decode($orcidapi, true);
$firstname = $data['person']['name']['given-names']['value'];
$lastname = $data['person']['name']['family-name']['value'];
$description = $data['person']['biography']['content'];
$urls = $data['person']['researcher-urls']['researcher-url'];
$firstinitial = $firstname[0];
$outputrow='';
if(!empty($description)){
	$outputrow .= '<div class="row"><div class="col-12"><div class="card description"><p class="card-text" style="white-space: pre-wrap;">';
	$outputrow .= $description; 
	$outputrow .=  '</p>';
if(!empty($urls)){
	$outputrow .='<ul style="list-style:none;">';
	foreach($urls as $url){
	$linkurl = $url['url']['value'];
	$linktitle = $url['url-name'];
	$outputrow .='<li style="display:inline;padding:0 10px 0 0;"><a href="'.$linkurl.'" target="'.$linktitle.'">'.$linktitle.'</a></li>';
	}
	$outputrow .='</ul>';
}
	$outputrow .='</div></div></div>';	
}
	
$outputrow .= '<div class="row">';	
//ORCID Employment
$employments = $data['activities-summary']['employments']['employment-summary'];
if(!empty($employments)) {
	$outputrow .=  '<div class="col-sm-6">';
	$outputrow .=  '<div class="card"><div class="card-header"><strong>Professional History</strong></div><ul class="card-text">';
	foreach($employments as $job){
		$position = $job['role-title'];
		$positionstart = $job['start-date']['year']['value'];
		$positionend = $job['end-date']['year']['value'];
		$positiondept = $job['department-name'];
		$positionorg = $job['organization']['name'];
		if($positionend =='') $positionend = 'Present'; else $positionend = $positionend;
		$outputrow .=  '<li><strong>'.$position.'</strong>, '.$positionstart.'&mdash;'.$positionend;
		if($positiondept !='') $outputrow .= '<br />'.$positiondept;
		if($positionorg !='') $outputrow .=  ', '.$positionorg;
		$outputrow .= '</li>'; 
	}
$outputrow .= '</ul></div></div>';
}	
//ORCID Education 
$educations = $data['activities-summary']['educations']['education-summary']; 
if(!empty($educations)) {
	$outputrow .=  '<div class="col-sm-6">';
	$outputrow .=  '<div class="card"><div class="card-header"><strong>Education</strong></div><ul class="card-text">';
	foreach($educations as $school){
		$degree = $school['role-title'];
		$degreedate = $school['end-date']['year']['value'];
		$degreeschool = $school['organization']['name'];
		$degreeschoolcity = $school['organization']['address']['city'];
		$degreeschoolregion = $school['organization']['address']['region'];
		$degreeschoolcountry = $school['organization']['address']['country'];
		$outputrow .=  '<li>';
		if($degree !='') $outputrow .= '<strong>'.$degree.'</strong>, ';
		$outputrow .= $degreeschool.', '.$degreedate.'</li>';
}
$outputrow .=  '</ul></div></div>';
}
$outputrow .=  '</div>'; // end row	

$cachefile= $location.'/'.$uri.'.html'; // generate the static html file
ob_start(); 
//echo '<!-- Do not edit. This file is auto generated each day -->'."\r\n\r\n";
echo $outputrow;
$fp = fopen($cachefile,'w');
fwrite($fp,ob_get_contents());
fclose($fp);
?>
