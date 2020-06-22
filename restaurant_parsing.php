<?php

//Parsing CSV file
$records 	= array_map('str_getcsv', file('restaurant_hours.csv'));
$data 		= array();
$days_arr 	= array('Mon' => 'Mon','Tue'=> 'Tue','Wed'=>'Wed','Thu'=>'Thu','Fri'=>'Fri','Sat'=>'Sat','Sun'=>'Sun');

function getListOfDaysBetweenTwoDays($arr, $start, $end) {
  
  $result 		= [];
  $has_started 	= false;

  foreach ( $arr as $item => $value ) {
    
    if( ( $item != $end && $has_started ) || $item == $start) {
      array_push($result, $value);
      $has_started = true;
    }

    if( $item == $end ) {
       array_push($result, $value);
       return $result;
    }
  }
}

// Processing restaurant list
foreach ($records as $row => $value) {
	
	$resutant_name 		= $value[0];
	$day_time_string 	= $value[1]; 
 	$day_time_break_ups = explode("/", $day_time_string);

 	foreach ($day_time_break_ups as $day_time_break_up_row => $day_time_break_up_row_value) {
 		
		$day_time_arr 				= array();
		$day_time_level_0_arr 		= array();
 		$day_time_break_ups_level_1 = explode(",", $day_time_break_up_row_value);

 		//checking day time break up level 1 length : Mon-Thu, Sun 11:30 am - 9 pm
 		if( count($day_time_break_ups_level_1) === 2){
			
			$day_range_string 						= "";
			$time_string 							= "";
			$day_time_break_ups_level_1_break_ups 	= explode("-", $day_time_break_ups_level_1[0]);
			
			if(count($day_time_break_ups_level_1_break_ups) == 2){

				$days_in_between 	= getListOfDaysBetweenTwoDays($days_arr, $day_time_break_ups_level_1_break_ups[0], $day_time_break_ups_level_1_break_ups[1]);
				$day_time_arr 		= array_merge($day_time_arr,$days_in_between);
			}else{
				array_push($day_time_arr, $day_time_break_ups_level_1_break_ups[0]);
			}

			// Check for two dash(-) 
			if( substr_count(trim($day_time_break_ups_level_1[1]),"-") == 2 ){
				
				$day_range_string 						= substr(trim($day_time_break_ups_level_1[1]), 0,7);
				$day_time_break_ups_level_2_break_ups 	= explode("-", $day_range_string);
				$days_in_between 						= getListOfDaysBetweenTwoDays($days_arr, $day_time_break_ups_level_2_break_ups[0], $day_time_break_ups_level_2_break_ups[1]);
				$day_time_arr 							= array_merge($day_time_arr,$days_in_between);
				$time_string 							= substr(trim($day_time_break_ups_level_1[1]), 8);
			}else{
				$day_range_string 	= substr(trim($day_time_break_ups_level_1[1]), 0,3);
				array_push($day_time_arr, $day_range_string);
				$time_string 		= substr(trim($day_time_break_ups_level_1[1]), 4);
				
			}
			
			foreach ($day_time_arr as $day_time_arr_key => $day_time_arr_value) {
				$data[$resutant_name][$day_time_arr_value] = $time_string; 
			}

 		}else{
 			
 			$day_range_string 	= "";
			$time_string 		= "";
 			// Check for two dash(-) 
			if( substr_count(trim($day_time_break_ups_level_1[0]),"-") == 2 ){
				
				$day_range_string 						= substr(trim($day_time_break_ups_level_1[0]), 0,7);
				$day_time_break_ups_level_2_break_ups 	= explode("-", $day_range_string);
				$days_in_between 						= getListOfDaysBetweenTwoDays($days_arr, $day_time_break_ups_level_2_break_ups[0], $day_time_break_ups_level_2_break_ups[1]);
				$day_time_level_0_arr 					= array_merge($day_time_level_0_arr,$days_in_between);
				$time_string 							= substr(trim($day_time_break_ups_level_1[0]), 8);			
				
			}else{
				$day_range_string 	= substr(trim($day_time_break_ups_level_1[0]), 0,3);
				array_push($day_time_level_0_arr, $day_range_string);
				$time_string 		= substr(trim($day_time_break_ups_level_1[0]), 4);
			}

			foreach ($day_time_level_0_arr as $day_time_arr_key => $day_time_arr_value) {
				$data[$resutant_name][$day_time_arr_value] = $time_string; 
			}	 
 		}
 	}
}


function is_restaurent_open($open_time,$close_time,$entered_time){


	$entered_time 	= date('H:i a',strtotime($entered_time));
	$open_time 		= date('H:i a',strtotime($open_time));
	$close_time 	= date('H:i a',strtotime($close_time));
	$status 		= false;

	if ($entered_time >= $open_time && $entered_time <= $close_time){
	   $status = true;
	}

	return $status;
}


function find_open_restaurant($day,$time){

	global $data;
	$result_set = array();

	foreach ($data as $key => $value) {
		
		foreach ($value as $day_key => $day_value) {
			
			$temp_day_value  			= explode("-", trim($day_value)); 
			$is_restaurent_open_status 	= is_restaurent_open($temp_day_value[0],$temp_day_value[1],$time);
			
			if(strtolower($day_key) == $day && $is_restaurent_open_status ==true){
				$result_set[] = array('resutant_name'=>$key, 'restaurant_time' => $day_value);
			}
		}
	}

	return $result_set;
}


$open_restaurant_list = find_open_restaurant('mon','11:30 am'); // Change here for testing

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="open_restaurant_list.csv";');
$f = fopen('php://output', 'w');

fputcsv($f, array('RestaurentName','Time'));
foreach ($open_restaurant_list as $row) {	
	fputcsv($f, $row);
}


?>
