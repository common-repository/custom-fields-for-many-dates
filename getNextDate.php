<?php

	global $wpdb;	

	// get the values of pre-existing date rows
	$existingDates = $wpdb->get_results("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE (post_ID='".$post->ID."') AND ".
		"(meta_key LIKE 'DateInput%') ORDER BY meta_id");
		
	// get the values of pre-existing date title rows	
	$existingDateTitle = $wpdb->get_results("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE (post_ID='".$post->ID."') AND ".
		"(meta_key LIKE 'DateTitleInput%') ORDER BY meta_id");
		
	//declare a 3d array that will combine the data and date title rows for sorting
	$existingData;	
		
	// declare a counter
	$count = 0;
	
	// Only go through the array combination and sorting if the date array isn't empty
	if (!(empty($existingDates)))
	{
		// combine the meta values into a single array
		foreach ($existingDates as $existingDate)
		{
			$existingData[$count][1]=$existingDate->meta_value;
			$existingData[$count][2]=$existingDateTitle[$count]->meta_value;
			
			$count += 1;
		}	
		
		// capture the number of rows that were moved into the 3d array in the previous loop in $rows
		$rows = $count;
		
		// $pass will be used to track the "passes" through the array as the bubble sort works
		$pass = 1;
		// reset count
		$count = 0;
		
		// sort the array using a bubble sort
		do
		{
			$currentRowTime = strtotime($existingData[$count][1]);
			$nextRowTime = strtotime($existingData[$count+1][1]);
			
			if ($currentRowTime > $nextRowTime)
			{
				$oldDate = $existingData[$count][1];
				$oldDateTitle = $existingData[$count][2];
				
				$existingData[$count][1] = $existingData[$count+1][1];
				$existingData[$count][2] = $existingData[$count+1][2];
				
				$existingData[$count+1][1] = $oldDate;
				$existingData[$count+1][2] = $oldDateTitle;
			}
			
			$count += 1;
			
			if (($count==($rows-($pass))))
			{
				$count = 0;
				$pass += 1;
			};
			
			if ($rows-($pass)==0)
				$count = -1;
			
		}while (!($count==-1));
		
		// loop through the values and add stop when the found value is greater than the current time
		$count = 0;
		do
		{	
			$currentRowTime = strtotime($existingData[$count][1]);
			$now = strtotime("now");
			
			$count += 1;
		} while (($currentRowTime < $now)&&(!($count==$rows)));
		
		// print out the value of the current row (need to subtract one from count because it increments 
		//	at the end of the do while loop
		
		echo $existingData[($count-1)][1];
	}
	else
	{
		echo "No Deadline Entered";
	};
?>