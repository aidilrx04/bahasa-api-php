<?php

function cors()
{

    // Allow from any origin
    // if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
    // }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }
}


// enable cors
cors();

// connect to db

// get server status
$status_filepath = '../../private/status.json';
$credential_filepath = '../../private/';

$status_file = fopen($status_filepath, 'r') or die('Invalid server status!');
$server_status = json_decode(fread($status_file, filesize($status_filepath)), true)['status'];
fclose($status_file);

// get db credential
$credential_filename = $server_status === "DEVELOPMENT" ? $credential_filepath . "db-dev.json" : $credential_filepath . "db-prod.json";

$credential_file = fopen($credential_filename, 'r') or die('Invalid db credential!');
$credential = json_decode(fread($credential_file, filesize($credential_filename)), true);
fclose($credential_file);

// connect to db
$host = $credential['host'];
$user = $credential['user'];
$pass = $credential['pass'];
$name = $credential['name'];

$conn = mysqli_connect($host, $user, $pass, $name);

$length = $_GET['length'] ?? 999999999;
$amount = $_GET['amount'] ?? 10;
$order = $_GET['order'] ?? 'random';

$words = get_word($amount, $length, $order);

// return result;
header('Content-Type: application/json');
echo json_encode($words);

function get_word($amount, $length, $order = 'random')
{
    global $conn;
    $order_str = $order === 'random' ? 'RAND()' : ($order === 'desc' ? 'word desc' : 'word asc');
    $queryStr = "SELECT * FROM words WHERE `length` <= ? ORDER BY {$order_str} LIMIT ?";
    $res = [];

    if ($stmt = $conn->prepare($queryStr)) {
        $stmt->bind_param('ss', $length, $amount);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                array_push($res, $row);
            }
        }
    }

    return $res;
}


$conn->close();
