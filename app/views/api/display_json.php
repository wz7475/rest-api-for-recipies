<?php
// header('Content-Type: application/json; charset=utf-8');
$output = json_encode($data['json']);
if ($output === false) {
    echo json_last_error_msg();
} else {
    echo $output;
}
?>