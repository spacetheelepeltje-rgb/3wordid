<?php

echo time();

function checkAndCorrectURL($url) {
	

	if(!strpos($url,'.')) {

		 return "invalid";
		 
		 };			  	 
		 
	if(strpos($url,'ftp')!== false) {

		 return "invalid";
		 
		 };	

    if (strpos($url, 'http://') === 0) {

        $url = str_replace('http://', 'https://', $url);
        
        return $url;    
    }
    
    if (strpos($url, 'https://') === 0) {
    
        return $url;    
    }
    
    if (substr_count($url, '.') ==1 or substr_count($url, '.') ==2) {
		
		return "https://" . $url;
	}
    
    return "invalid";
}

// Test the function
$testUrls = [
    'examplecom',
    'www.example.com',
    'ftp://example.com',
    'http://example.com',
    'https://example.com',
    'example.com/path?query=string',
    'example'
];

foreach ($testUrls as $testUrl) {
	
    echo "Original: $testUrl, Corrected: " . checkAndCorrectURL($testUrl) . "\n";

}


