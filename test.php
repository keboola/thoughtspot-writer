<?php

$database = getnev('DATABASE');
$user = getenv('USER');
$pass = getnev('PASS');
$serverlist = getenv('SERVERLIST');

$dsn = "Driver={ThoughtSpot(x64)};Database=$database;SERVERLIST=$serverlist";

$conn = odbc_connect($dsn, $user, $pass);

var_dump($conn);
