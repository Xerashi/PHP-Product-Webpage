<?php
//Date: 30/09/2024, Author(s): Jonathan Ecker, Subjecty: PHP Script for increasing the order amount dynamically in a webpage.

//Information for accessing the MySQL database.
$server = "localhost";
$user = "root";
$dbname = "draiviTest";

//Establishes connection to MySQL server
$conn = new mysqli($server, $user,  null, $dbname);

//Checks for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//Waits for a POST request from the server
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $number = $_POST['number'];
    $order = $_POST['order'];
    $order = $order + 1; /* Increases the order amount from the web page by 1 */

    //Updates the entry for the number provided to the new increased order amount.
    $add_query = $conn->prepare("UPDATE productData SET orderamount = ? WHERE number = ?");
    $add_query->bind_param("ii", $order, $number);
    $add_query->execute();
    $add_query->close();
    $conn->close();
    //Returns the order value (1 higher then before) to the webpage.
    echo $order;    
}
?>