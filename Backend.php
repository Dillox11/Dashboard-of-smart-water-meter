<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smart water meter";

$conn = new mysqli($servername,$username,$password,$dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['temperature']) && 
        isset($data['tds_value']) && isset($data['turbidity_value']) && 
        isset($data['flow_rate']) && isset($data['total_volume'])) {

        $temperature = $data['temperature'];
        $tds_value = $data['tds_value'];
        $turbidity_value = $data['turbidity_value'];
        $flow_rate = $data['flow_rate'];
        $total_volume = $data['total_volume'];

        $sql = "INSERT INTO users (temperature, tds_value, turbidity_value, flow_rate, total_volume) 
                VALUES ('$temperature', '$tds_value', '$turbidity_value', '$flow_rate', '$total_volume')";
        
        if ($conn->query($sql) === TRUE) {
            echo "Hello welcom to our website<br>";
        } else {
            echo "❌ Error: " . $sql . "<br>" . $conn->error;
            echo "Broke out website<br>";
        }
    }
}

$conn->close();
?>
