<?php
//Date: 30/09/2024, Author(s): Jonathan Ecker, Subjecty: PHP Script for gathering product information dynamically in a webpage.

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

//Send a query to the MySQL database to retrieve product information so that the webpage can be populated(currently limited to 100 entries).
$sql = "SELECT number, name, bottlesize , price, priceGBP, orderamount FROM productData LIMIT 100";
$result = $conn->query($sql);

$items = []; /* empty array to store the items from the query */
//makes sure the response from the database isn't empty, or contains more than 0 rows.
if ($result->num_rows > 0) {
    // Fetch all items
    while($row = $result->fetch_assoc()) {
        //Fills the array with row data from the query.
        $items[] = $row;
    }
}

//Converts the response to the GET request into a JSON object
header('Content-Type: application/json');
//Sends the JSON object back to the webpage.
echo json_encode($items);
$conn->close();

?>