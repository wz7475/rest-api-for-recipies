<?php
    $output = json_encode($data['json']); 
    if($output === false)
    {
        echo json_last_error_msg();
    }
    else
    {
        echo $output;
    }
?>