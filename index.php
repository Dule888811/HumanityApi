<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Page Title</title>
		<meta charset="utf-8">
	<!--	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1"> -->
		<link href="https://fonts.googleapis.com/css?family=Lato:400,700" rel="stylesheet">
		<link href="style.css" rel="stylesheet" type="text/css">
    </head>
    <body>
    <?php
     require_once "classes/ShiftPlanning.php";
      $shiftplanning = new ShiftPlanning(
        array(
          'key' => 'c8f4d783e6eaa1661d5b97754c0a02dfac7ce342' // enter your developer key
        )
      );
      $session = $shiftplanning->getSession( );
      if( !$session )
{
	
	$response = $shiftplanning->doLogin(
		array(
			'username' => 'dusanpavlovic',
			'password' => 'Password88',
		)
	);
	if( $response['status']['code'] == 1 )
	{
		$session = $shiftplanning->getSession( );

	}
	else
	{
		echo $response['status']['text'] . "--" . $response['status']['error'];
	}
}
    
      
      $shifts = $shiftplanning->setRequest(
        array(
          'module' => 'schedule.shifts',
          'start_date' => 'today',
          'start_date' => 'today',
          "mode" => "overview"
        )
      );
    /*     echo "<pre>";
      $employees = $shiftplanning->getEmployees( );
      var_dump($employees);  */
      $time_clock = $shiftplanning->setRequest(
        array(
          "module"=>"timeclock.timeclocks",
          "start_date"=>"today",
          "end_date"=>"today",
          )
        );
        echo "<pre>";
     //  echo $shifts['data'][3]['id']; 
          $today = date("Y/m/d");
          $today = explode("/", $today);
          $mount = $today[1];
          $day = $today[2];
          ?>
          <div class="wraper">
        <table style="width:100%">
            <tr>
              <th>Employee </th>
              <th>Position (Schedule)</th> 
              <th>Shift</th>
              <th>Timeclock</th>
            </tr>
           <?php 
               
                for ($i = 0 ; $i <= count($shifts['data']) ; $i++)
                {   
                  if($shifts['data'][$i]["start_date"]["month"] == $mount &&  $shifts['data'][$i]["start_date"]["day"] == $day)               
                  {      
                 
                    ?>   <tr>
                        <td> <?php echo $shifts['data'][$i]["employees"][0]["name"]  ?> </td>
                        <td> <?php echo $shifts['data'][$i]["schedule_name"]  ?> </td>
                        <td> <?php echo $shifts['data'][$i]["start_time"]["time"] . " - " . $shifts['data'][$i]["end_time"]["time"]  ?> </td>
                         <?php  
                                     
                                for ($y = 0 ; $y <= count($time_clock['data']) ; $y++){
                                 //     echo "<pre>";
                                  //    var_dump($shifts['data'][$i]["id"],$time_clock['data'][$y]["shift"]);
                                  if($time_clock['data'][$y]["start"]["month"] == $mount &&  $time_clock['data'][$y]["start"]["day"] == $day)               
                                  { 
                                      if($shifts['data'][$i]["id"] == $time_clock['data'][$y]["shift"] && $shifts['data'][$i]["start_time"]["time"] == $time_clock['data'][$y]["in_time"]["time"])
                                      {
                                        ?>  <td> <?php echo $time_clock['data'][$y]["in_time"]["time"] . ' - ' . $time_clock['data'][$y]["out_time"]["time"];     
                
                                      }

                                      
                                  }else {
                                   
                                    ?><td> <?php echo "/";  }               
                                       ?></td>
                                            <?php  
                                  }    ?>                    
                         </tr>           
          <?php     }          
                }
                ?>
          </table>
    </div>  
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script type="text/javascript" src ="main.js"></script> 
</body>   
</html> 