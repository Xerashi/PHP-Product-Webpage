<?php 
    //Date: 30/09/2024, Author(s): Jonathan Ecker, Subjecty: Code for Draivi Backend Test

    //Uses composer to load PHPSpreadsheet for interacting with .xlsx files.
    require 'vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\IOFactory;

    //Had to increase timeout duo to the size of the Excel file when updatings existing entries.
    set_time_limit(120);

    //Information for accessing a temporary simple MySQL database, and the Currencylayer API for EUR to GBP conversion.
    $server = "localhost";
    $user = "root";
    $dbname = "draiviTest";
    $api_url = "http://apilayer.net/api/live?access_key=e5adcce0a8be7b2cd79a13f7bbf78a1b&currencies=GBP&source=EUR&format=1";


    //Establishes connection to MySQL server
    $conn = new mysqli($server, $user, null, $dbname);

    //Checks for connection errors
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }

    //Creates a database if one with the name draiviTest does not already exist.
    $db_create = "CREATE DATABASE IF NOT EXISTS draiviTest";
    if ($conn->query($db_create) == TRUE) {
        echo "draiviTest database created.\n";
    } 
    else {
        echo "Error creating database: " . $conn->error . "\n";
    }

    //Creates a table if one with the name productData does not already exist.
    $table_sql = "CREATE TABLE IF NOT EXISTS productData (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        number INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        bottlesize VARCHAR(30) NOT NULL,
        price FLOAT(6,2) NOT NULL, /* Limits the float to 6 digits, 2 after the decimal place */
        priceGBP FLOAT(6,2) NOT NULL, /* Limits the float to 6 digits, 2 after the decimal place */
        orderamount INT DEFAULT 0 NOT NULL, /* Sets default value for orders to 0 */
        last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP /* Creates a timestamp when an entry is made, and updates when any part of the entry updates */
    )";

    //Table creation request
    if ($conn->query($table_sql) == TRUE) {
        echo "TABLE productData created succesfully\n";
    }
    else {
        echo "Error creating table: " . $conn->error . "\n";
    }

    //Using curl to grab a JSON response from the Currencylayer API
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => "GET",
    ));
    $response = curl_exec($curl);
    curl_close($curl);

    //Converts the JSON response to a float for future math operations.
    $json_response = json_decode($response, true);
    $conversion = $json_response['quotes']['EURGBP'];

    //Alko lookup and excel download [Currently not working, provides an error when accessing the .xlsx file]
    $alko_url = "https://www.alko.fi/INTERSHOP/static/WFS/Alko-OnlineShop-Site/-/Alko-OnlineShop/fi_FI/Alkon%20Hinnasto%20Tekstitiedostona/alkon-hinnasto-tekstitiedostona.xlsx";
    $file_path = basename($alko_url);

    /* Ran into issues with downloading the .xlsx file from Alko
       Would only return HTML meta data values in the form of a .xlsx document whether using fopen(), fgetcsv(), or file_get_contents()
       Had to download the excel file manually and get data from it locally.

        $excel_data = file_get_contents($alko_url);
        file_put_contents($file_path, $excel_data);
    */

    //Using PHPSpreadsheet to retrieve entries from the local Excel document.
    $spreadsheet = IOFactory::load($file_path);
    $sheet = $spreadsheet->getActiveSheet();

    //Set getRowIterator to 5, since the 5th row is where the actual product entries begin.
    foreach($sheet->getRowIterator(5) as $row) {
        $cellIterator = $row->getCellIterator();
        //Only want the iterator to check cells with existing entries.
        $cellIterator->setIterateOnlyExistingCells(true);

        //Collecting the data from each cell into an array.
        $data = [];
        foreach($cellIterator as $cell) {  
            $data[] = $cell->getValue();        
        }

        //Only using rows with more than 4 entries in the spreadsheet to double check that the rows are populated.
        if(count($data) >= 4){
            $numero = $data[0]; /* 1st column of the spreadsheet: Number */
            $nimi = $data[1]; /* 2nd column of the spreadsheet: Names */
            $pullo = $data[3]; /* 4th column of the spreadsheet: Bottle Size */
            $hinta = $data[4]; /* 5th column of the spreadsheet: Price */
            $hintaGBP = $data[4] * $conversion; /* Uses the EURO price and multiplies it by the EUR to GBP ratio from Currencylayer */
            $hintaGBP = number_format($hintaGBP, 2); /* Formats the result to 2 decimal places */

            // Step 2: Checks to see if an entry with the same number already exists in the table.
            $check_query = $conn->prepare("SELECT COUNT(*) FROM productData WHERE number = ?");
            $check_query ->bind_param("i", $numero);
            $check_query ->execute();
            $check_query ->bind_result($count);
            $check_query ->fetch();
            $check_query ->close();

            //If an entry for that number exists in the MySQL productData table, then the product is update based off the identifying number.
            if ($count > 0) {
                $update_query = $conn->prepare("UPDATE productData SET price = ?, priceGBP = ? WHERE number = ?");
                $update_query ->bind_param('ddi', $hinta, $hintaGBP, $numero);
                $update_query ->execute();
                $update_query ->close();
            } 
            //If an entry doesn't exist then a new entry is created in the MySQL productData table.
            else {
                $insert_query = $conn->prepare("INSERT INTO productData(number, name, bottlesize ,price, priceGBP) VALUES(?,?,?,?,?)");
                $insert_query ->bind_param("issdd", $numero, $nimi, $pullo, $hinta, $hintaGBP);
                $insert_query ->execute();
                $insert_query ->close();
            }
        }     
    }
    $conn->close();
?>