<?php
$logFile = "notionContent.txt";

$rawBody = file_get_contents("php://input");

file_put_contents($logFile, $rawBody); 

