<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smart water meter";

$response = ['status' => 'error', 'message' => 'Initialization error', 'valve_status' => 'OFF'];
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $response['message'] = "Connection failed: " . $conn->connect_error;
    die(json_encode($response));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['temperature'], $data['tds_value'], $data['turbidity_value'], $data['flow_rate'], $data['total_volume'])) {

        $temperature = $conn->real_escape_string($data['temperature']);
        $tds_value = $conn->real_escape_string($data['tds_value']);
        $turbidity_value = $conn->real_escape_string($data['turbidity_value']);
        $flow_rate = $conn->real_escape_string($data['flow_rate']);
        $total_volume = $conn->real_escape_string($data['total_volume']);
    
        $sql = "INSERT INTO users (temperature, tds_value, turbidity_value, flow_rate, total_volume) 
                VALUES ('$temperature', '$tds_value', '$turbidity_value', '$flow_rate', '$total_volume')";
        
        if ($conn->query($sql) === TRUE) {
            $response['status'] = 'success';
            $response['message'] = 'Data logged successfully.';
        } else {
            $response['status'] = 'error';
            $response['message'] = "Database insertion failed: " . $conn->error;
        }
    } else {
        $response['message'] = 'Missing sensor data in JSON payload.';
    }
} else {
    $response['message'] = 'Invalid request method. Only POST is allowed.';
}

$valve_sql = "SELECT valve_status FROM device_state WHERE id = 1 LIMIT 1";
$result = $conn->query($valve_sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $response['valve_status'] = $row['valve_status']; 
} else {
    $response['valve_status'] = 'OFF'; 
    $response['message'] .= ' (Valve status not found in DB. Defaulting to OFF)';
}

echo json_encode($response);

$conn->close();
?>
