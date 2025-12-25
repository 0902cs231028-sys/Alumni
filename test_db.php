<?php
include 'includes/connection.php';
$result = mysqli_query($conn, "SHOW TABLES");
if($result) {
  echo "Connected. Tables:<br>";
  while($row = mysqli_fetch_row($result)) { echo $row[0]."<br>"; }
} else {
  echo "DB error: ".mysqli_error($conn);
}