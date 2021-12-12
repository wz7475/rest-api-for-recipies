<?php
    $output = json_encode($data['dish']); 
    if($output === false)
    {
        echo json_last_error_msg();
    }
    else
    {
        echo $output;
    }
?>