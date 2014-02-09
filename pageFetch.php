<?php
/**
	* Get a web file (HTML, XHTML, XML, image, etc.) from a URL.  Return an
	* array containing the HTTP server response header fields and content.
*/
function get_web_page($url)
{
  //initialize curl
  $ch = curl_init($url);
	
  //allow curl to transfer data and work under the proxy given
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_PROXY, '192.227.247.177:8089');
  
  //connecting through curl to the Runescape link, and fetching the data on page
  $data = curl_exec_follow($ch);	
  
  //closing curl
  curl_close($ch);
	

  //storing content data fetched by curl plus some additional curl error details
  //Segment One - Content Data
  //Segment Two = Curl Error No (If need be)
  //Segment Three - Curl Error (Again if need be)
  $arrayCurl = array( 
	$data, 
	curl_errno($ch),
	curl_error($ch)
  );

  //returning all data
  return $arrayCurl;
}
//PREMADE FUNCTION - This function in a nutshell will just manually set curl to follow redirects on a web page if need-be
//its a manual-equivalent to the 'CURL_FOLLOWLOCATION' option in CURL since some web hosts disallow that
function curl_exec_follow($ch, &$maxredirect = null) {
  
  // we emulate a browser here since some websites detect
  // us as a bot and don't let us do our job
  $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5)".
                " Gecko/20041107 Firefox/1.0";
  
  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

  $mr = $maxredirect === null ? 5 : intval($maxredirect);

  if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
    curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

  } else {
    
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if ($mr > 0)
    {
      $original_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
      $newurl = $original_url;
      
      $rch = curl_copy_handle($ch);
      
      curl_setopt($rch, CURLOPT_HEADER, true);
      curl_setopt($rch, CURLOPT_NOBODY, true);
      curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
      
	  do
      {
        curl_setopt($rch, CURLOPT_URL, $newurl);
        $header = curl_exec($rch);
        if (curl_errno($rch)) {
          $code = 0;
        } else {
          $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
          if ($code == 301 || $code == 302) {
            preg_match('/Location:(.*?)\n/', $header, $matches);
            $newurl = trim(array_pop($matches));
            
            // if no scheme is present then the new url is a
            // relative path and thus needs some extra care
            if(!preg_match("/^https?:/i", $newurl)){
              $newurl = $original_url . $newurl;
            }   
          } else {
            $code = 0;
          }
        }
      } while ($code && --$mr);
      
      curl_close($rch);
      
      if (!$mr)
      {
        if ($maxredirect === null)
        trigger_error('Too many redirects.', E_USER_WARNING);
        else
        $maxredirect = 0;
        
        return false;
      }
      curl_setopt($ch, CURLOPT_URL, $newurl);
    }
  }
  return curl_exec($ch);
}

//URL fetcher
function getURLComponent() {
  //check if a url has been filled in
  if(isset($_GET['url'])) {
 	//decrypting BASE64 encoded URL I want to fetch data from under the proxy
	$url = base64_decode($_GET['url']);

	//receiving the page result of URL data via PHP cURL 
	$res = get_web_page($url);

	//checking and outputting the appropriate pages result message to the file reader
	//if curl is returning a valid error number to the requested URL option
	if($res[1] != 0)
	  //output CURL error number and message
	  return "Proxy Connection Error [".$res[1]."]: ".$res[2]." \r\n
			  Please Contact Support and Inform them of this issue.";
	else
	  //else if no error, output the page contents encrypted in BASE64
	  return base64_encode($res[0]);
  }
}

//initializing URL fetcher once the document has been entered
echo getURLComponent();
?>