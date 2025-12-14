<?php
include '../config/connection.php';

$result = mysqli_query($conn, 'DESCRIBE vms_coordinator_notes');
if($result){
    echo 'Table structure after migration:' . PHP_EOL;
    while($row = mysqli_fetch_assoc($result)){
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
}else{
    echo 'Error: ' . mysqli_error($conn);
}
?>