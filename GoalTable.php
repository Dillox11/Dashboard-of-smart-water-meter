<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smart water meter";

$conn = new mysqli($servername,$username,$password,$dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, goal_name, target_amount, target_date,goal_period FROM goals";
$result=$conn->query($sql);
$goals=[];
if($result->num_rows>0)
    {
        while($row=$result->fetch_assoc()){
            $goals[]=$row;
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
        <h1>Goal Dashboard </h1>
        <h2>Smart Water Meter </h2>
        <h3>Goal That was ready setted,all founds in this Table</h3>
        <div class="my2">
        <?php if(count($goals)>0):?>
            <table>
            <tr>
                <th>ID</th>
                <th>Name of Goal</th>
                <th>Target in liters</th>
                <th>For Period</th>
                <th>Date</th>
        </tr>
        <?php foreach ($goals as $user):?>
            <tr>
                <td><?=$user['id']?></td>
                <td><?=$user['goal_name']?></td>
                <td><?=$user['target_amount']?> L</td>
                <td>For <?=$user['goal_period']?></td>
                <td><?=$user['target_date']?></td>
        </tr>
        <?php endforeach;?>
            </table>
            <?php else:?>
                <p> No data found.</p>
                <?php endif;?>
                <br>
                </div>
    </body>
    </html>
