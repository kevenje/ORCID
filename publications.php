<?PHP
//This script generates a static html file containing the publications one Orcid ID.
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

//set_time_limit(5); //extends time for 5 seconds
set_time_limit(5);

$outputrow='';
// Output Starts
$url = 'https://pub.orcid.org/v2.1/'.$orcid;

$jsonapi = file_get_contents($url, false, $context);
$data = json_decode($jsonapi, true);
	
$firstname = $data['person']['name']['given-names']['value'];
$lastname = $data['person']['name']['family-name']['value'];
$firstinitial = $firstname[0];

//ORCID Publications
$publications = $data['activities-summary']['works']['group']; 
if(!empty($publications)) {
$outputrow .= '<div class="row">';
$outputrow .= '<div class="col-sm-12">';	
$outputrow .= '<div class="card"><div class="card-header"><strong>Select Professional Activities</strong></div><ul class="card-text">';
$rowcolor = true;
foreach($publications as $publication){
$title = $publication['work-summary'][0]['title']['title']['value'];

$rawtype = $publication['work-summary'][0]['type'];	
$doi = '';
$doiurl ='';
$eid = '';
$eidurl = '';	
$externalids = $publication['work-summary'][0]['external-ids']['external-id'];
if($externalids !=''){
foreach($externalids as $externalid){ //look for the doi
	$idtype = $externalid['external-id-type'];
	$idvalue = $externalid['external-id-value'];
	if($idtype == 'doi'){ //get the doi if it exists
		$doi = $idvalue;
		$doiurl = 'https://doi.org/';
	}
	if($idtype == 'eid'){ // get the scopus eid if it exists
		$eid = $idvalue;
		$eidurl = 'http://www.scopus.com/record/display.url?origin=inward&eid=';
	}
}
}
if(isset($rawtype))
$type = str_replace("_"," ",$rawtype); //remove hyphen
else
$type='';
$date = $publication['work-summary'][0]['publication-date']['year']['value'];
	
//if($rawtype == 'JOURNAL_ARTICLE' || $type == 'BOOK'){	
$pub = $publication['work-summary'][0]['path'];
$puburl = 'https://pub.orcid.org/v2.1'.$pub;
$pubresult = file_get_contents($puburl, false, $context);
$pubdata = json_decode($pubresult, true);
$journal = $pubdata['journal-title']['value'];
$jurl = $pubdata['url']['value'];
$contributors = $pubdata['contributors']['contributor']; // bring up the array of authors
$names = '';
if(is_array($contributors)){
$count = count($contributors); //count authors to use for citation punctuation
$i=0;
foreach($contributors as $author){ 
	$i++;
	$names .= $author['credit-name']['value']; 
	if($i == $count-1)
	$names .= ' & ';
	elseif($i == $count)
	$names .= '';
	else
	$names .= ', ';
	}
}
else
$names = $firstinitial.' '.$lastname;
if($names == '')
$names = $firstinitial.' '.$lastname;
$outputrow .= '<li class="citation" '.(($rowcolor = !$rowcolor)?' style="background-color:#FFFFE0"':'').'>';
$outputrow .= '<span style="text-transform: capitalize;">'.$names.' </span>';
if($date !='') $outputrow .= '('.$date.'). ';
if($jurl !='') $outputrow .= '<a href="'.$jurl.'" target="'.$title.'">';
$outputrow .= '<strong>'.$title.'</strong>';
if($jurl !='') $outputrow .= '</a>';
$outputrow .= ', <em><span style="text-transform: capitalize !important;">'.$journal.'</span></em>';
if($doi != '') 
	$outputrow .= '. <a href="'.$doiurl.''.$doi.'" target="new">'.$doi.'</a>';
elseif($eid != '')
	$outputrow .= '. <a href="'.$eidurl.''.$eid.'" target="new">'.$eid.'</a>';
$outputrow .= ' [<span style="text-transform: lowercase;">'.$type.']</span>';
$outputrow .= '</li>';
$doi = '';
$doiurl ='';
$eid = '';
$eidurl = '';
}
$outputrow .= '</ul>';
$outputrow .= '<div style="font-size:x-small; text-align:right;">Imported from <a href="https://orcid.org/">https://orcid.org/</a> on '.date("Y/m/d").'</div></div></div>';
$outputrow .= '</div>'; // end row

//echo $outputrow;

$cachefile= $location.'/'.$uri.'.html'; // generate the static html file
ob_start(); 
echo '<!-- Do not edit. This file is auto generated each day -->'."\r\n\r\n";
echo $outputrow;
$fp = fopen($cachefile,'w');
fwrite($fp,ob_get_contents());
fclose($fp);

}

?>
