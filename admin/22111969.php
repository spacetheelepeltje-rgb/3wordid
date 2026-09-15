<?php

   include '../php/functions.php';
   
   $files = scandir('../3wids', SCANDIR_SORT_ASCENDING);
   
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

 
    echo "<table>";
 
    foreach ($fileStats as $key=>$stats) {
		
	echo "<tr><td>" . $key . "</td>";
	
			
		foreach ($stats as $stats_key=>$stats_value) {
			
			echo "<td>" . $stats_value . "</td>";
		
		
		
		}
	
	echo "</tr>";
	
    }
  
	echo "</table>";
