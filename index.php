<?php session_start();?>

<!DOCTYPE html>
<head>
  <link rel="stylesheet" href="styles.css">  
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.4.1/darkly/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
  <script src="https://kit.fontawesome.com/d105316e91.js" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-colorschemes"></script>
  <style>
  <?php
      $mobile = strstr($_SERVER['HTTP_USER_AGENT'],'iPhone');
      if ($mobile) echo "html {font-size: 2rem;}";
  ?>
  .nav-pills .nav-link.active {
    background-color: rgb(51, 112, 235);
  }
  
  /* .btn-primary {
    background-color: rgb(51, 112, 235);
  } */

  textarea, input[type="text"], input[type="number"] {
    background-color: rgb(35, 33, 30);
    border: 1px solid;
  }

  .form-control:focus {
    background-color: rgb(35, 33, 30);
    border: 1px solid;
    color: rgb(234, 217, 187);
  }

  </style>

  <title>Logger</title>
  <link rel="icon" href="https://i.ibb.co/c3cNLQ7/billd3.png">
</head>

<body>

<?php
require_once 'vendor/autoload.php';

// init configuration
$clientID = '172854096684-niqcqe1al91vo0vl3gth63nv0t17lqvb.apps.googleusercontent.com';
$clientSecret = 'tGD3rKypVsep2A1iVgq3X8xw';
$redirectUri = 'http://logger.today';

// create Client Request to access Google API
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

// authenticate code from Google OAuth Flow
if (!$_SESSION["gid"]) {
  if (isset($_GET['code'])) {
      $code = $_GET['code'];
      $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
      $client->setAccessToken($token['access_token']);

      // get profile info
      $google_oauth = new Google_Service_Oauth2($client);
      $google_account_info = $google_oauth->userinfo->get();
      $gmail =  $google_account_info->email;
      $gname =  $google_account_info->name;
      $gid = $google_account_info->id;
      $gpic = $google_account_info->picture;

      console_log("ID: $gid");
      console_log("ID: $code");        

      $_SESSION["gid"] = $gid;
      $_SESSION["gname"] = $gname;
      $_SESSION["gmail"] = $gmail;
      $_SESSION["gpic"] = $gpic;
  } else {
      $gurl = $client->createAuthUrl();
      echo <<<EOF
      <div class="text-center">
        <img style="width: 100px; margin-top: 80px;" src="https://s5.gifyu.com/images/20453d5c971c78b_a.gif">
        <button class="btn btn-primary" type="submit" style="margin-top: 100px;" onclick="location.href = '$gurl';">Google Login</button>
      </div>
      EOF;
      die();
  }
} else {
  $gid = $_SESSION["gid"];
  $gname = $_SESSION["gname"];
  $gmail = $_SESSION["gmail"];
  $gpic = $_SESSION["gpic"];

  // console_log("From session");
  // console_log("ID: $gid");  
}

// requests info
// console_log('$_REQUEST: ');
// console_log(print_r($_REQUEST, true));

// console_log('$_SESSION: ');
// console_log(print_r($_SESSION, true));

// DB connection

$dbhost = 'localhost';
$dbname = 'logger';
$dbuser = 'root';
$dbpass = 'zmYHyBgKJcc*9N';
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

if (!$conn) die('Could not connect: ' . mysqli_connect_error());

// functions

function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if ($result === FALSE) {
      console_log("Fail: $query");
      console_log(mysqli_error($conn));
      die(mysqli_error($conn));    
    } 
    return $result;
}

function getSingle($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if($result === FALSE) die(mysqli_error($conn));   
    $row = mysqli_fetch_row($result)[0];
    return $row;
}

function getRows($query, $key) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if($result === FALSE) die(mysqli_error($conn));   
    $array = array();
    while ($row = mysqli_fetch_assoc($result)){      
      $array[] = $row["$key"];
    }
    return $array;
}


function println($str) {
  print $str . '<br>';
}

function console_log($output, $with_script_tags = true) {
  $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
  if ($with_script_tags) $js_code = '<script>' . $js_code . '</script>';
  echo $js_code;
}


// Get parameters

// Get uid
$uid = getSingle("select uid from users where gid = '$gid'");
if (!$uid) {
    $ip = $_SERVER['REMOTE_ADDR'];

    query("insert into users (ip, gid, gname, gmail, gpic) values ('$ip', '$gid', '$gname', '$gmail', '$gpic')");
    $uid = getSingle("select uid from users where gid = '$gid'");
    console_log("Generated uid: $uid");
} else {
    // console_log("uid: $uid");
}
$_SESSION["uid"] = $uid; // put uid in session

// access log
date_default_timezone_set('america/new_york');
$date_now = Date("Y-m-d H:i:s");
query("insert into access_log (uid, date) values ('$uid', '$date_now')");

// body

// customize for pi
if ($gmail == 'atara.sun18@gmail.com') $gpic = 'https://i.ibb.co/DVvMD9K/IMG-2168.jpg';
if ($gmail == 'atara.sun18@gmail.com') $gpic = 'https://s5.gifyu.com/images/ezgif.com-crop3ce89edfc94b9e98.gif';
if ($gmail == 'holygeek17@gmail.com') $gpic = 'https://s5.gifyu.com/images/ffpic140613509962z72.gif';
// if ($gmail == 'holygeek17@gmail.com') $gpic = 'https://s5.gifyu.com/images/ezgif.com-crop3ce89edfc94b9e98.gif';
  ?>

  <!-- Modal Piggy -->
  <div class="modal fade" id="piggyModal" tabindex="-1" role="dialog" aria-labelledby="piggyModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLongTitle">Net Income Trends</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p id="total-saving">Total Saving: </p>
        </div>
        <!-- <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Save changes</button>
        </div> -->
      </div>
    </div>
  </div>

  <!-- Modal Habit -->
  <div class="modal fade" id="habitScoreModal" tabindex="-1" role="dialog" aria-labelledby="habitModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Habit Score Trends</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Not Enough Data
      </div>
      <!-- <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div> -->
    </div>
  </div>
</div>

  <div class="container-fluid">
    <div class='m-3'>
      <img src='<?php echo $gpic; ?>' style='width: 30px' id='avatar-img'> 
      <p style='display: inline-block; margin-left: 10px;'> <?php echo $gname; ?></p>
      <i class="fa fa-history ml-3 mr-1" aria-hidden="true" id="dropdownMonth" 
         style="display: inline-block; cursor: pointer;"
         data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
         <p style='display: inline-block; font-family: Lato;' id='dtstr'></p>
      </i>
      <div class="dropdown-menu" aria-labelledby="dropdownMonth">
        <a class="dropdown-item" style="cursor: pointer;" onclick="setDate(2019, 12, this.text)">2019.12</a>
        <a class="dropdown-item" style="cursor: pointer;" onclick="setDate(2020, 1, this.text)">2020.01</a>        
        <a class="dropdown-item" style="cursor: pointer;" onclick="setDate(2020, 2, this.text)">2020.02</a>        
        <a class="dropdown-item" style="cursor: pointer;" onclick="setDate(2020, 3, this.text)">2020.03</a>
        <a class="dropdown-item" style="cursor: pointer;" onclick="setDate(2020, 4, this.text)">2020.04</a>
        <a class="dropdown-item" style="cursor: pointer;" onclick="setDate(2020, 5, this.text)">2020.05</a>
      </div>
<!-- Weather -->    
      <p id='weather' class="m-1"
         style='display: inline-block; cursor:pointer;'>
         <i class="far fa-sun" onclick="window.open('util/weather', '_blank')"></i>
      </p>    
<!-- Coronavirus -->    
      <p id='crona' class="m-1"
         style='display: inline-block; cursor:pointer;' onclick="window.open('https://coronavirus.1point3acres.com/', '_blank')">
         Coronavirus         
      </p>       
<!-- Habit score -->    
      <p id='habit-score' class="m-1"
         data-toggle="modal" data-target="#habitScoreModal"
         style='display: inline-block; float: right; cursor:pointer;'> </p>    
<!-- Piggy bank -->
      <p id='net-income' class="m-1"
         data-toggle="modal" data-target="#piggyModal"
         style='display: inline-block; float: right; cursor:pointer;'> </p>      
    </div>
    <div class='m-3' style='height:1px'></div>

    <ul class="nav nav-pills nav-fill m-3" id="myTab" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="log-tab" data-toggle="pill" href="#log" role="tab" aria-controls="log" aria-selected="true">Spending</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="summary-tab" data-toggle="pill" href="#summary" role="tab" aria-controls="summary" aria-selected="false">Summary</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="recurr-tab" data-toggle="pill" href="#recurr" role="tab" aria-controls="recurr" aria-selected="false">Recurring</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="habit-tab" data-toggle="pill" href="#habit" role="tab" aria-controls="habit" aria-selected="false">Habit</a>
      </li>
    </ul>
    <div class="tab-content m-3" id="myTabContent">
<!-- Log Tab -->
      <div class="tab-pane fade show active" id="log" role="tabpanel" aria-labelledby="log-tab">
        <form id="log-form" class="mb-3" autocomplete="off">    
          <div class="form-row">
            <div class="col"> 
              <input type="text" class="form-control" id=llog name=llog placeholder="Name" required="true">
            </div>
            <div class="col"> 
              <input type="text" class="form-control" id=lcategory name=lcategory placeholder="Category" required="true">
            </div>    
            <div class="col"> 
              <input type="number" step="any" class="form-control" id=lamount name=lamount placeholder="Amount" required="true">
            </div>

            <button type=submit class="btn btn-primary" data-toggle="tooltip" data-placement="bottom" title="Log a record of expense, with name, category and the amount. Enter minus value for money income">Add Spending</button>      
          </div>
        </form>
        <div id='log-list'></div>
      </div>
<!-- Summary Tab -->
      <div class="tab-pane fade" id="summary" role="tabpanel" aria-labelledby="summary-tab">
        <div class="container">
          <div class="row">
            <div class="col"><canvas id="summaryChartPie" class="mx-auto" style="display: block" <?php if ($mobile) { echo "height='800' width='800'"; } else { echo "height='400' width='400'"; } ?>></canvas></div>
            <div class="col"><canvas id="summaryChartLine" class="mx-auto" style="display: block" <?php if ($mobile) { echo "height='800' width='800'"; } else { echo "height='400' width='400'"; } ?>></canvas></div>
            <div class="col"><canvas id="summaryChartLineMonth" class="mx-auto" style="display: block" <?php if ($mobile) { echo "height='800' width='800'"; } else { echo "height='400' width='400'"; } ?>></canvas></div>
          </div>
        </div>
        <div id="summary-list"></div>
      </div>
<!-- Recurr Tab -->
      <div class="tab-pane fade" id="recurr" role="tabpanel" aria-labelledby="recurr-tab">
        <!-- <h5 class="mt-3 mb-3">Monthly Recurrents</h5> -->
        <form id="recurr-form" class="mb-3" autocomplete="off">    
          <div class="form-row">
            <div class="col"> 
              <input type="text" class="form-control" id="rname" name=rname placeholder="Name" required="true">
            </div>
            <div class="col"> 
              <input type="number" step="any" class="form-control" id="ramount" name=ramount placeholder="Amount" required="true">
            </div>
            <button type=submit class="btn btn-primary" data-toggle="tooltip" data-placement="bottom" title="Add recurrent such as income or rent that will repeat monthly">Add Monthly Recurring</button>      
          </div>
          <!-- <input type="hidden" id=uid name=uid value="<?php echo $uid; ?>">  -->
        </form>
        <div id="recurr-list"></div>
      </div>
<!-- Habits Tab -->
      <div class="tab-pane fade" id="habit" role="tabpanel" aria-labelledby="habit-tab">

        <!-- Modal Add Habit -->
        <div class="modal fade" id="addHabitModal" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Add a new Habit!</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                TODO
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save</button>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center">
          <button class="btn btn-primary m-3" data-toggle="modal" data-target="#addHabitModal" style="width: 200px">Add a new habit</button>      
        </div>

      </div>
    </div>
</div>

<?php mysqli_close($conn); ?>


<script>
$(function() {
    // Fill contents
    getLog();
    getRecurr();
    getSummary();
    setNetIncome();
    setHabitScore();
    setTotalSaving();

    // Autocomplete
    setAutocomplete();

    // Set date string
    $('#dtstr').text(getCookie('dtstr'));
});

// Tooltips
$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})
$(document).ready(function(){ // Hide tooltips after click
    $('[data-toggle="tooltip"]').click(function () {
      $('[data-toggle="tooltip"]').tooltip("hide");
    });
});

// Popovers
$(function () {
  $('[data-toggle="popover"]').popover()
})

function setDate(y, m, str) {
    document.cookie = "dtyear=" + y;
    document.cookie = "dtmonth=" + m;
    document.cookie = "dtstr=" + str;
    
    $('#dtstr').text(str);

    // refresh logs
    getLog();
    setNetIncome();

    // refresh summary
    getSummary();
    readySummaryChartPie();
    readySummaryChartLine();
}

// Log Category auto-fill
let logCatMap = new Map()
$.ajax({
        url: "dbfunc.php",
        dataType:"json",
        data: "func=getLogCatRecords"
      }).done(function (response, textStatus, jqXHR){   
        // console.log(response);
        for (var i = 0; i < response.length; i++){
            var record = response[i];
            let log = record["log"];
            let cat = record["category"];
            logCatMap.set(log, cat);
        }
        // console.log(logCatMap);
      }).fail(function (jqXHR, textStatus, errorThrown){ console.log(errorThrown); }) 

function autofillCategory(event, ui) {
  let log = ui.item.label;
  let cat = logCatMap.get(log);
  // console.log("You selected: " + log + " - " + cat);
  $("#lcategory").val(cat);
}

// Charts
var myChartPie;
var myChartLine;
var myChartLineMonth;
Chart.defaults.global.defaultFontSize = <?php if ($mobile) {echo "28";} else {echo "15";}?>;
$('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
  var pill_href = $(e.target).attr('href');  
  if (pill_href === '#summary') {
    readySummaryChartPie();
    readySummaryChartLine();
    readySummaryChartLineMonth();
  }
})

// Functions

function readySummaryChartPie() {
  if (myChartPie != null) myChartPie.destroy();
  // Get Chart Data
  $.when($.ajax({
      url: "dbfunc.php",
      dataType:"json",
      data: "func=getSummaryTitle"
    }), $.ajax({
      url: "dbfunc.php",
      dataType:"json",
      data: "func=getSummaryValue"
    })).then(function (resp1, resp2) {
      // console.log(resp1);
      // console.log(resp2);
      drawSummaryChartPie(resp1[0], resp2[0]);
  });        
}

function drawSummaryChartPie(titles, values) {
  var ctxPie = $('#summaryChartPie');
  myChartPie = new Chart(ctxPie, {
      type: 'pie',
      data: {
          labels: titles,
          datasets: [{
              label: '$ spent',    
              data: values,                 
              borderWidth: 0
          }]
      },
      options: {
        responsive: false,
        title: {
					display: true,
					text: 'Spending this month'
				},
        plugins: {
          colorschemes: {
            scheme: 'brewer.Paired12'
          },  
        }        
      }
  });
}

function readySummaryChartLine() {
  if (myChartLine != null) myChartLine.destroy();
  // console.log("Getting data...");
  // Get Chart Data
  $.when($.ajax({
      url: "dbfunc.php",
      dataType:"json",
      data: "func=getDailySpending"
    }).fail(function (jqXHR, textStatus, errorThrown){console.log(errorThrown);}), 
    $.ajax({
      url: "dbfunc.php",
      dataType:"json",
      data: "func=getDailySpendingHistory"
    }).fail(function (jqXHR, textStatus, errorThrown){console.log(errorThrown);})
    ).then(function (resp1, resp2) {
      // console.log(resp1[0]);
      // console.log(resp2[0]);      
      let amountArr = [];      
      let amount = 0;
      for (var i = 0; i < resp1[0].length; i++){
            var record = resp1[0][i];            
            amount += parseFloat(record["amount"]);                        
            amountArr.push(amount.toFixed(2));
      }
      let dateArr = [];
      let avgAmtArr = [];
      let amountAvg = 0;
      for (var i = 0; i < resp2[0].length; i++){
            var record = resp2[0][i];
            let date = record["date"];
            dateArr.push(date);
            amountAvg += parseFloat(record["amount"]);          
            avgAmtArr.push(amountAvg.toFixed(2));
      }
      // console.log(dateArr);
      // console.log(amountArr);
      drawSummaryChartLine(dateArr, amountArr, avgAmtArr);
  });        
}

function readySummaryChartLineMonth() {
  if (myChartLineMonth != null) myChartLineMonth.destroy();
  // console.log("Getting data...");
  // Get Chart Data
  $.when($.ajax({
      url: "dbfunc.php",
      dataType:"json",
      data: "func=getMonthlySpending"
    }).fail(function (jqXHR, textStatus, errorThrown){console.log(errorThrown);}), 
    $.ajax({
      url: "dbfunc.php",
      dataType:"json",
      data: "func=getMonthlySaving"
    }).fail(function (jqXHR, textStatus, errorThrown){console.log(errorThrown);})
    ).then(function (resp1, resp2) {
      // console.log(resp1[0]);
      // console.log(resp2[0]);      
      let amountArr = [];      
      for (var i = 0; i < resp1[0].length; i++){
            var record = resp1[0][i];                                
            amountArr.push(parseFloat(record["amount"]));
      }
      let dateArr = [];
      let avgAmtArr = [];
      for (var i = 0; i < resp2[0].length; i++){
            var record = resp2[0][i];
            let date = record["date"];
            dateArr.push(date);         
            avgAmtArr.push(parseFloat(record["amount"]));
      }
      // console.log(dateArr);
      // console.log(amountArr);
      drawSummaryChartLineMonth(dateArr, amountArr, avgAmtArr);
  });        
}

function drawSummaryChartLineMonth(titles, values1, values2) {
  var ctxLine = $('#summaryChartLineMonth');
  myChartLineMonth = new Chart(ctxLine, {
      type: 'line',
      data: {
          labels: titles,
          datasets: [{
              label: '$ spent',    
              backgroundColor: 'rgba(226, 106, 106, 1)',
					    borderColor: 'rgba(226, 106, 106, 1)',
              pointBackgroundColor: 'rgba(226, 106, 106, 1)',
              pointBorderColor: 'rgba(226, 106, 106, 1)',
              data: values1,                 
              fill: false
          }, {
              label: '$ save',    
              backgroundColor: 'rgba(0, 181, 204, 1)',
					    borderColor: 'rgba(0, 181, 204, 1)',
              pointBackgroundColor: 'rgba(0, 181, 204, 1)',
              pointBorderColor: 'rgba(0, 181, 204, 1)',
              data: values2,                 
              fill: false
          }]
      },
      options: {
        responsive: false,
        title: {
					display: true,
					text: 'Spending by month'
				},
        tooltips: {
					mode: 'index',
					intersect: false,
				},
				hover: {
					mode: 'nearest',
					intersect: true
				},
				scales: {
					xAxes: [{
						display: true,
						scaleLabel: {
							display: true,
							labelString: 'Month'
						}
					}],
					yAxes: [{
						display: true,
						scaleLabel: {
							display: true,
							labelString: 'Amount'
						}
					}]
				}       
      }
  });
}

function drawSummaryChartLine(titles, values1, values2) {
  var ctxLine = $('#summaryChartLine');
  myChartLine = new Chart(ctxLine, {
      type: 'line',
      data: {
          labels: titles,
          datasets: [{
              label: '$ spent',    
              backgroundColor: 'rgba(226, 106, 106, 1)',
					    borderColor: 'rgba(226, 106, 106, 1)',
              pointBackgroundColor: 'rgba(226, 106, 106, 1)',
              pointBorderColor: 'rgba(226, 106, 106, 1)',
              data: values1,                 
              fill: false
          }, {
              label: '$ history avg spent',    
              backgroundColor: 'rgba(0, 181, 204, 1)',
					    borderColor: 'rgba(0, 181, 204, 1)',
              pointBackgroundColor: 'rgba(0, 181, 204, 1)',
              pointBorderColor: 'rgba(0, 181, 204, 1)',
              data: values2,                 
              fill: false
          }]
      },
      options: {
        responsive: false,
        title: {
					display: true,
					text: 'Daily spending'
				},
        tooltips: {
					mode: 'index',
					intersect: false,
				},
				hover: {
					mode: 'nearest',
					intersect: true
				},
				scales: {
					xAxes: [{
						display: true,
						scaleLabel: {
							display: true,
							labelString: 'Date'
						}
					}],
					yAxes: [{
						display: true,
						scaleLabel: {
							display: true,
							labelString: 'Amount Spent'
						}
					}]
				}       
      }
  });
}

function getCookie(cname) {
  var name = cname + "=";
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for(var i = 0; i <ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

function setAutocomplete() {
    var logs;
    var cats;

    $.ajax({
        url: "dbfunc.php",
        dataType:"json",
        data: "func=getAutoLogs"
      }).done(function (response, textStatus, jqXHR){   
        $("#llog").autocomplete({
          source: response,
          select: autofillCategory
        });
      }).fail(function (jqXHR, textStatus, errorThrown){
          console.log(errorThrown);
    }) 

    $.ajax({
        url: "dbfunc.php",
        dataType:"json",
        data: "func=getAutoCats"
      }).done(function (response, textStatus, jqXHR){   
        $("#lcategory").autocomplete({
          source: response
        });
      }).fail(function (jqXHR, textStatus, errorThrown){
          console.log(errorThrown);
    })
}

function setNetIncome() {
  let netIncome = 0;
  $.ajax({
      url: "dbfunc.php",
      data: "func=getNetIncome"
    }).done(function (response, textStatus, jqXHR){      
        netIncome = parseFloat(response);
        $("#net-income").html("<i class='fas fa-donate m-1'></i><p style='display: inline-block;'>" + response + "</p>");
        if (netIncome < 0) {
            $("#net-income").css("color", "orangered");
        } else {
            $("#net-income").css("color", "green");
        }
        setAvatar(netIncome);
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        console.log("Error getting recurr list");
        console.log(errorThrown);
  })  
}

function setHabitScore() {
  let score = 0;
  $.ajax({
      url: "dbfunc.php",
      data: "func=getHabitScore"
    }).done(function (response, textStatus, jqXHR){      
      score = parseFloat(response);
      $("#habit-score").html("<i class='far fa-star m-1'></i><p style='display: inline-block;'>" + response + "</p>");
      if (score < 0) {
          $("#habit-score").css("color", "orangered");
      } else {
          $("#habit-score").css("color", "green");
      }
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        console.log("Error getting habit score");
        console.log(errorThrown);
  })  
}

function setTotalSaving() {
  $.ajax({
      url: "dbfunc.php",
      data: "func=getTotalSaving"
    }).done(function (response, textStatus, jqXHR){              
        $("#total-saving").html("Total Saving: $" + response);
    }).fail(function (jqXHR, textStatus, errorThrown){
        console.log(errorThrown);
  })  
}

function setAvatar(netIncome) {
    var gmail = "<?php echo $gmail; ?>";
    if (gmail == 'atara.sun18@gmail.com') {
        // set avatar
        if (netIncome > 0) {
          $("#avatar-img").attr("src","https://s5.gifyu.com/images/ezgif.com-crop3ce89edfc94b9e98.gif");
        } else {
          $("#avatar-img").attr("src","https://i.ibb.co/DVvMD9K/IMG-2168.jpg");
        }
    }
}

function getSummary() {
    $("#summary-list").html(`<div class="text-center"><img style="width: 200px; margin-top: 80px;" src="https://s5.gifyu.com/images/50453c5553eeb38_a.gif"></div>`);
    $.ajax({
        url: "dbfunc.php",
        data: "func=getSummary"
      }).done(function (response, textStatus, jqXHR){
          // console.log(response);
          $("#summary-list").html(response);          
          $('[data-toggle="popover"]').popover()

      }).fail(function (jqXHR, textStatus, errorThrown){
          // Show error
          console.log("Error getting recurr list");
          console.log(errorThrown);
      })
}

function getRecurr() {
    $("#recurr-list").html(`<div class="text-center"><img style="width: 200px; margin-top: 80px;" src="https://s5.gifyu.com/images/50453c5553eeb38_a.gif"></div>`);
    // Get Recurr contents
    $.ajax({
      url: "dbfunc.php",
      type: "get",
      data: "func=getRecurr"
    }).done(function (response, textStatus, jqXHR){
        // console.log(response);
        $("#recurr-list").html(response);
        $('[data-toggle="tooltip"]').tooltip()
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        console.log("Error getting recurr list");
        console.log(errorThrown);
    })
}

function getLog() {
    $("#log-list").html(`<div class="text-center"><img style="width: 200px; margin-top: 80px;" src="https://s5.gifyu.com/images/50453c5553eeb38_a.gif"></div>`);
    // Get Log contents
    $.ajax({
      url: "dbfunc.php",
      type: "get",
      data: "func=getLog"
    }).done(function (response, textStatus, jqXHR){
        // console.log(response);
        $("#log-list").html(response);
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        console.log("Error getting log list");
        console.log(errorThrown);
    })
}

function deleteLog(row) {    
    let lid = row.id.replace("log_", "");
    console.log(lid);

    $.ajax({
      url: "dbfunc.php",
      data: "func=deleteLog&lid=" + lid
    }).done(function (response, textStatus, jqXHR){
        console.log(response);
        $(row).hide();
        setNetIncome();
        getSummary();
        setAutocomplete();
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        alert(errorThrown);
    })
}


function deleteRecurr(row) {
    // console.log(row);    
    let lid = row.id.replace("rec_", "");
    // console.log(lid);

    $.ajax({
      url: "dbfunc.php",
      data: "func=deleteRec&rid=" + lid
    }).done(function (response, textStatus, jqXHR){
        console.log(response);
        $(row).hide();
        setNetIncome();
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        alert(errorThrown);
    })
}

function toggleSavingRecurr(row, flag) {
    // console.log(row);    
    let lid = row.id.replace("rec_", "");
    console.log(lid);

    $.ajax({
      url: "dbfunc.php",
      data: "func=toggleSavingRecurr&rid=" + lid + "&flag=" + flag
    }).done(function (response, textStatus, jqXHR){
        console.log(response);
        
        let tag = $(row).find("td")[2];
        if (response === "added as saving") {
            $(tag).css("color", "green");
            $(tag).removeAttr('onclick');
            $(tag).attr('onClick', 'toggleSavingRecurr(this.parentElement, 0);');
        } else {
            $(tag).css("color", "dimgrey");
            $(tag).removeAttr('onclick');
            $(tag).attr('onClick', 'toggleSavingRecurr(this.parentElement, 1);');
        }

        // $(row).hide();
        // setNetIncome();
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        alert(errorThrown);
    })
}

// Post method for Recurr tab
$("#recurr-form").submit(function(event) {    
    /* Stop form from submitting normally */
    event.preventDefault();

    /* Get from elements values */
    var values = $(this).serialize();
    
    // clean form
    $("#rname").val('');
    $("#ramount").val('');

    // Insert Recurr record
    $.ajax({
        url: "dbfunc.php",
        type: "post",
        data: values + "&func=insertRecurr"
    }).done(function (response, textStatus, jqXHR){
        // console.log(response);
        getRecurr();
        setNetIncome();
    }).fail(function (){
        // Show error
        console.log("Error post to dbfunc.php");
        console.log(errorThrown);
    })
});

// Post method for Log tab
$("#log-form").submit(function(event) {    
    /* Stop form from submitting normally */
    event.preventDefault();

    /* Get from elements values */
    var values = $(this).serialize();
    
    // clean form
    $("#llog").val('');
    $("#lcategory").val('');
    $("#lamount").val('');

    // Insert Recurr record
    $.ajax({
        url: "dbfunc.php",
        type: "post",
        data: values + "&func=insertLog"
    }).done(function (response, textStatus, jqXHR){
        console.log(response);
        getLog();
        setNetIncome();
        getSummary();
        setAutocomplete();
    }).fail(function (){
        // Show error
        console.log("Error post to dbfunc.php");
        console.log(errorThrown);
    })
});

function toggleSummaryDetail(id) {
  var arr = id.split("&&&");
  if (arr.length != 3) return;
  var dcat = arr[1];
  var dlog = arr[2];

  console.log("dcat: " + dcat + ", dlog: " + dlog);

  $.ajax({
      url: "dbfunc.php",
      data: "func=getSummaryDetail&dcat=" + dcat + "&dlog=" + dlog
    }).done(function (response, textStatus, jqXHR){
        // console.log(response);

        var target = document.getElementById(id);
        $(target).popover({"placement": "right", "title": response});
        $(target).popover('show');
        // $(target).attr({"data-toggle": "popover", "data-placement": "right", "data-content": "test"});

        // setNetIncome();
        // getSummary();
        // setAutocomplete();
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        alert(errorThrown);
    })
}
</script>

</html>