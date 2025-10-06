<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smart water meter";

$conn = new mysqli($servername,$username,$password,$dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, temperature, tds_value, turbidity_value, flow_rate, total_volume FROM users";
$result=$conn->query($sql);
$users=[];
if($result->num_rows>0)
    {
        while($row=$result->fetch_assoc()){
            $users[]=$row;
        }
    }
    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Water Meter Data Dashboard</title>
        <link rel="stylesheet" href="style.css"> 
        <style>
            .my1 { 
                font-family: sans-serif; 
                padding: 20px; 
                background-color: #f4f7f6; 
            }
            .my2 { 
                overflow-x: auto; 
                margin-top: 20px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            table {
                width: 100%;
                border-collapse: collapse;
                min-width: 800px; 
                background-color: white;
            }
            th, td {
                padding: 15px;
                text-align: left;
                border: 1px solid #ddd;
            }
            th {
                background-color: #007bff;
                color: white;
                text-transform: uppercase;
                font-size: 0.9em;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
        </style>
    </head>
    <body class="my1">
        <h2>Smart Water Meter Dashboard</h2>
        <h3>Real-time Manage and still focusing on meter readings</h3>
        <div class="my2">
        <?php if(count($users)>0):?>
            <table>
            <tr>
                <th>ID</th>
                <th>TEMP (&deg;C)</th>
                <th>TDS (PPM)</th>
                <th>TURBIDITY (NTU)</th>
                <th>FLOW RATE (L/min)</th>
                <th>TOTAL VOLUME (L)</th>
        </tr>
        <?php foreach ($users as $user):?>
            <tr>
                <td><?=$user['id']?></td>
                <td><?=$user['temperature']?></td>
                <td><?=$user['tds_value']?></td>
                <td><?=$user['turbidity_value']?></td>
                <td><?=$user['flow_rate']?></td>
                <td><?=$user['total_volume']?></td>
        </tr>
        <?php endforeach;?>
            </table>
            <?php else:?>
                <p> No data found. Please ensure the ESP8266 is sending data to temp.php.</p>
                <?php endif;?>
                <br>
                </div>
    </body>
    </html>
