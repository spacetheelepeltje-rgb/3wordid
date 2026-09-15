<?php

 include '../php/functions.php';
 
 $files = scandir('../3wids', SCANDIR_SORT_ASCENDING);
 //var_dump($files);
 //echo exec('ls -lax');
 // Custom sorting function
function sortByCreationTimeDesc($a, $b) {
    // Convert creation time strings to timestamps for comparison
    $timestampA = strtotime($a['creation_time']);
    $timestampB = strtotime($b['creation_time']);
    
    // Sort in descending order (most recent first)
    return $timestampB - $timestampA;
}
 // Function to get file stats including a best guess at creation time
function getFileStats($file) {
    $stats = stat('../3wids/' . $file);
    if ($stats === false) {
        return false; // File does not exist or cannot be accessed
    }
    
    // Here, we'll use ctime as a proxy for creation time on some systems. 
    // Be aware this isn't universally accurate for creation time.
    return [
        'name' => $file,
        'size' => $stats['size'],
        'creation_time' => date('Y-m-d H:i:s', $stats['ctime']),
        'last_accessed' => date('Y-m-d H:i:s', $stats['atime']),
        'last_modified' => date('Y-m-d H:i:s', $stats['mtime']),
    ];
}

// Clear stat cache to ensure fresh data
clearstatcache();

// Process each file and add stats to our array
$fileStats = [];

foreach ($files as $file) {
    $fileStat = getFileStats($file);
    if ($fileStat !== false) {
        $fileStats[] = $fileStat;
    } else {
        echo "Could not access stats for file: $file\n";
    }
}

usort($fileStats, 'sortByCreationTimeDesc');

// Display the results

//var_dump($fileStats);

$count = 0;

//echo "<pre>";

foreach($fileStats as $stat) {
	  $filename = $stat['name'];
	  
	  if($filename!="." and $filename!= "..") { 
		  
	  $widfile = '../3wids/' . $stat['name'];
	  
	  //echo $widfile;
	  
	  $contents = file_get_contents($widfile);
	  $unserialized =unserialize($contents);
	  
	  if(isset($unserialized['threewords'])) {
		  
		  echo "<p>" . $unserialized['threewords'] . "</p><br>";
		  
		  $count = $count+1;
		  
		  }
		  
	  }  else {
		  
		  //echo "not " . $filename;
		  
		  }
		  
		  if($count == 5) {
			  
			  break;
			  
		}
	}
//echo "</pre>";
