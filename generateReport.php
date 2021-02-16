<?php

if (isset($_POST['generateReport'])) {
/* Check if we have the year and month logs on our server. Navigate inside them if they do exist. */
    $queryYear = glob($_POST['year'], GLOB_ONLYDIR);

    $goBackLink = '<a href="generateReport.php">Go Back</a>';

if (!$queryYear) {
    echo 'There are no logs for that year<br>';
    echo $goBackLink;
    exit;
}

chdir($queryYear[0]);

$queryMonth = glob($_POST['month'], GLOB_ONLYDIR);

if (!$queryMonth) {
    echo 'There are no logs for that month<br>';
    echo $goBackLink;
    exit;
}

chdir($queryMonth[0]);

/* Downloaded the final csv file using these headers */
header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=".$queryMonth[0].$queryYear[0]."-report.csv");

/* Get the list of offers inside of the month directory */
$offers = glob('*', GLOB_ONLYDIR);

/*
 Loop through the offer directories and build an array for each of the logs: request logs, captcha logs, and score logs
- These arrays hold the emails from their respective logs
*/
$allEmails = []; 
$captchaEmails = []; 
$scoreEmails = []; 

// LOG FILES
$requestLogFile = 'REQUEST.csv';
$captchaLogFile = 'CAPTCHA.csv';
$scoreLogFile = 'SCORE.csv';

// function to parse the emails from the log files
function parseEmails($logFile) {
    if (file_exists($logFile)) {
        if (($handle = fopen($logFile, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $email = $data[9];
                if (isset($email) && $email != 's2' && $email != '') {
                    switch ($logFile) {
                        case 'REQUEST.csv':
                            array_push($GLOBALS['allEmails'], $email);
                        break;
                        case 'CAPTCHA.csv':
                            array_push($GLOBALS['captchaEmails'], $email);
                        break; 
                        case 'SCORE.csv':
                            array_push($GLOBALS['scoreEmails'], $email);
                        break; 
                    }
                }
            }
            fclose($handle);
        } 
    }
}

/* Loop through the offer directories and parse the emails from the request, captcha, and score logs. */
foreach ($offers as $offer) {
    chdir($offer);
    parseEmails($requestLogFile);
    parseEmails($captchaLogFile);
    parseEmails($scoreLogFile);
    chdir('..');
}

/*
Compare the all emails array with the captcha and score email arrays. If an email in the all emails array is not inside the captcha or score emails and is not already in the final array (removing dupes), add it to the final array.
*/
$failedCaptchaEmails = [];

foreach ($allEmails as $email) {
    if (!in_array($email, $captchaEmails) || !in_array($email, $scoreEmails)) {
          array_push($failedCaptchaEmails, $email);  
    }
}

/* Create the final csv file that is downloaded. */
$result = array_unique($failedCaptchaEmails);

$output = fopen("php://output", "w");

$csvHeaders = ['EMAIL'];

fputcsv($output, $csvHeaders);

foreach ($result as $email) {
    fputcsv($output, [$email]);
}
exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Failed Captcha Report</title>
</head>
<body>
    <form action="generateReport.php" method="post">
    <label for="month">Month:</label>
        <select name="month">
        <?php
        
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        foreach ($months as $month) {
            echo "<option value='$month'>$month</option>";
        }

        ?>
        </select>
        <label for="year">Year:</label>
        <select name="year">
            <?php 
        
            $beginningYear = 2019;

            while ($beginningYear <= date('Y')) {
                echo "<option value='$beginningYear'>$beginningYear</option>";
                $beginningYear++;
            }
            ?>
        </select>
        <button type="submit" name="generateReport">Generate Report</button>
    </form>
</body>
