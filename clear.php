<?php
//Date: 30/09/2024, Author(s): Jonathan Ecker, Subjecty: PHP Script for reseting the order amount to 0 dynamically in a webpage.

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
    $order = 0; /* Sets the order amount to 0 */

    //Resets the product entry for the provided number to 0.
    $clear_query = $conn->prepare("UPDATE productData SET orderamount = ? WHERE number = ?");
    $clear_query->bind_param("ii", $order, $number);
    $clear_query->execute();
    $clear_query->close();
    $conn->close();
    //Returns the order value (0 since this is a reset) to the webpage.
    echo $order;
}
?>