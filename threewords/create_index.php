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

    echo "<br>time  is : " . time() .  "<br>";
    
    echo "<table>";
 
    $filename = "";
 
    foreach ($fileStats as $key=>$stats) {
		
		if($stats != NULL) {

			foreach ($stats as $stats_key=>$stats_value) {
				
				if($stats_key == 'name') {
					
						$filename = '../3wids/' . $stats_value;
						
						//echo "<td>" . $filename . "</td>";	
						
						if(is_file($filename) and file_exists($filename)) {										
							$contents = file_get_contents($filename);
							$data = unserialize($contents);
							if(isset($data["threewords"])) {
								
								echo "<td><a href='https://3wordid.com/" . $data["threewords"] . "'>" . $data["threewords"] . "</a></td>";
								
								$location = $data["threewords"];
								if(file_exists($location)) {
									// don't overwrite if it exists
								} else {
								$copy_content = serialize($data);
									file_put_contents($location,$copy_content);
								}	
							}												
						} else {
							echo "<td>No file</td>";		
						}
					
					}
			
				
				
		
				
				if(in_array($key,[0,3,4])) {
					echo "<td>" . $stats_key . " " . $stats_value . "</td>";
				}
			
		
		}
	}
	
	echo "</tr>";
	
    }
  
	echo "</table>";

