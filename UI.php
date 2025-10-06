<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smart water meter";

$conn = new mysqli($servername, $username, $password, $dbname);
$connError = null;
$lastReading = null;
$goalFetchError = null;

$USAGE_GOAL_MONTHLY = 0.0;
$goalSourceMessage = "Goal is currently set to the default value of {$USAGE_GOAL_MONTHLY} L.";

if ($conn->connect_error) {
    $connError = "Connection failed: " . $conn->connect_error;
} else {
    $sql = "SELECT id, timestamp, temperature, tds_value, turbidity_value, flow_rate, total_volume FROM users ORDER BY timestamp DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $lastReading = $result->fetch_assoc();
    }

    $sqlGoal = "SELECT target_amount FROM goals ORDER BY id DESC LIMIT 1";
    $resultGoal = $conn->query($sqlGoal);

    if ($resultGoal) {
        if ($resultGoal->num_rows > 0) {
            $fetchedGoal = floatval($resultGoal->fetch_assoc()['target_amount']);
            $USAGE_GOAL_MONTHLY = $fetchedGoal;
            $goalSourceMessage = "Goal set by user to {$USAGE_GOAL_MONTHLY} L.";
        } else {
            $goalFetchError = "No goal record found, Please set a goal.";
        }
    } else {
        $goalFetchError = "Could not retrieve a goal. Please set a goal.";
    }
}

$totalVolumeLiters = $lastReading ? floatval($lastReading['total_volume']) : 0.0;

function getUsage($conn, $interval, $currentVolume) {
    if ($currentVolume <= 0) return 0.0;
    
    $sql = "SELECT total_volume FROM users WHERE timestamp <= DATE_SUB(NOW(), INTERVAL $interval) ORDER BY timestamp DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $startVolume = floatval($result->fetch_assoc()['total_volume']);
        $usage = $currentVolume - $startVolume;
        return max(0.0, $usage);
    }
    
    return $currentVolume;
}

$usageDay = 0.0;
$usageWeek = 0.0;
$usageMonth = 0.0;

if (!$connError && $lastReading) {
    $usageDay = getUsage($conn, '1 DAY', $totalVolumeLiters);
    $usageWeek = getUsage($conn, '7 DAY', $totalVolumeLiters);
    $usageMonth = getUsage($conn, '30 DAY', $totalVolumeLiters);
}


$usageToGoal = 0.0;
$goalStatusMessage = '';
$goalStatusClass = '';

if ($usageMonth > 0) {
    $usageToGoal = $USAGE_GOAL_MONTHLY - $usageMonth;

    if ($usageToGoal > 1000) {
        $goalStatusMessage = "You are well under your goal! Great job on conservation.";
        $goalStatusClass = "bg-green-100 border-data-green text-green-800";
    } elseif ($usageToGoal > 0) {
        $goalStatusMessage = "You are currently under your goal. Keep monitoring usage closely!";
        $goalStatusClass = "bg-blue-100 border-water-blue text-blue-800";
    } elseif ($usageToGoal > -500) {
        $goalStatusMessage = "You are slightly over budget. Consider reducing consumption due to setted period.";
        $goalStatusClass = "bg-orange-100 border-orange-500 text-orange-800";
    } else {
        $goalStatusMessage = "You have significantly exceeded your goal. Review your usage patterns.";
        $goalStatusClass = "bg-red-100 border-data-red text-red-800";
    }
} else {
    $usageToGoal = $USAGE_GOAL_MONTHLY;
    $goalStatusMessage = "Start tracking usage! Your monthly goal is {$USAGE_GOAL_MONTHLY} L.";
    $goalStatusClass = "bg-gray-100 border-gray-500 text-gray-800";
}

$displayGoalMonthly = number_format($USAGE_GOAL_MONTHLY, 0);
$displayUsageToGoal = number_format(abs($usageToGoal), 2);
$isUnderGoal = $usageToGoal >= 0;

if (!$connError) {
    $conn->close();
}


$RATE_PER_CUBIC_METER = 863.0;
$LITERS_PER_CUBIC_METER = 1000.0;
$RATE_PER_LITER = $RATE_PER_CUBIC_METER / $LITERS_PER_CUBIC_METER;

$totalCost = $totalVolumeLiters * $RATE_PER_LITER;

$displayRatePerM3 = number_format($RATE_PER_CUBIC_METER, 0);
$displayTotalCost = $lastReading ? number_format($totalCost, 2) : '--';

$displayVolume = $lastReading ? number_format($totalVolumeLiters, 2) : '--';
$displayFlow = $lastReading ? number_format($lastReading['flow_rate'], 2) : '--';
$displayTurbidity = $lastReading ? number_format($lastReading['turbidity_value'], 1) : '--';
$displayTds = $lastReading ? intval($lastReading['tds_value']) : '--';
$displayTemp = $lastReading ? number_format($lastReading['temperature'], 1) : '--';

$displayUsageDay = number_format($usageDay, 2);
$displayUsageWeek = number_format($usageWeek, 2);
$displayUsageMonth = number_format($usageMonth, 2);

$displayPh = $lastReading ? '7.0' : '--'; 
$displayTimestamp = $lastReading ? date('Y-m-d H:i:s', strtotime($lastReading['timestamp'])) : 'N/A';

$statusMessage = "No current data to assess quality.";
$statusClass = "bg-gray-100 border-gray-500 text-gray-800";

if ($lastReading) {
    $tds = intval($lastReading['tds_value']);
    $turbidity = floatval($lastReading['turbidity_value']);
    $statusMessage = "✅ Water Quality is currently **Excellent** and meets typical drinking standards.";
    $statusClass = "bg-green-100 border-data-green text-green-800";

    if ($turbidity > 5.0) {
        $statusMessage = "🛑 **WARNING: HIGH TURBIDITY ({$turbidity} NTU).** The water is very cloudy. Consumption is NOT recommended.";
        $statusClass = "bg-red-100 border-data-red text-red-800";
    }
    else if ($tds > 300) {
        $statusMessage = "❗ **ALERT: High TDS ({$tds} ppm).** The water quality is poor. Consider using filtration or further testing.";
        $statusClass = "bg-orange-100 border-orange-500 text-orange-800";
    }
    else if ($tds > 150) {
        $statusMessage = "🔸 **Note: Elevated TDS ({$tds} ppm).** Water is typically safe but monitor quality closely.";
        $statusClass = "bg-blue-100 border-water-blue text-blue-800";
    }
    else if (floatval($displayFlow) == 0 && floatval($displayVolume) > 0) {
        $statusMessage = "🔄 No flow detected, but total volume is high. Is the water supply currently off?";
        $statusClass = "bg-yellow-100 border-yellow-500 text-yellow-800";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Water Meter Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'water-blue': '#3b82f6',
                        'data-green': '#10b981',
                        'data-red': '#ef4444',
                        'data-purple': '#8b5cf6',
                        'orange-500': '#f97316',
                        'yellow-500': '#eab308',
                        'money-green': '#059669',
                    }
                }
            }
        }
    </script>
    <style>
        details > summary {
            list-style: none;
        }
        details > summary::-webkit-details-marker {
            display: none;
        }
        details > summary {
            position: relative;
            padding-right: 2.5rem; 
        }
        details > summary::after {
            content: '▼';
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%) rotate(0deg);
            transition: transform 0.3s;
            color: #8b5cf6; 
            font-size: 0.75rem;
            line-height: 1;
        }
        details[open] > summary::after {
            transform: translateY(-50%) rotate(180deg);
        }
        details summary .flex {
            align-items: center; 
        }

    </style>
</head>
<body class="bg-gray-50 font-sans min-h-screen p-4 md:p-8">

    <div class="max-w-4xl mx-auto">
        <header class="text-center mb-8">
            <h1 class="text-4xl font-extrabold text-water-blue tracking-tight">
                Smart Meter Dashboard
            </h1>
            <p class="text-gray-500 mt-2">Real-time water usage, quality, and billing overview.</p>
        </header>

        <?php if ($connError): ?>
            <div class="p-4 mb-8 bg-red-100 border-l-4 border-data-red text-red-800 rounded-lg shadow-inner font-medium" role="alert">
                <p class="font-bold">Database Connection Error:</p>
                <p><?php echo htmlspecialchars($connError); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($goalFetchError): ?>
            <div class="p-4 mb-8 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 rounded-lg shadow-inner font-medium" role="alert">
                <p class="font-bold">Goal Configuration Warning:</p>
                <p><?php echo htmlspecialchars($goalFetchError); ?></p>
                <p class="mt-2 text-sm">Using default goal: <?= $displayGoalMonthly ?> L. Click "Set Goal" below to fix this.</p>
            </div>
        <?php endif; ?>

        <?php if (!$connError): ?>
            <div class="p-4 mb-8 border-l-4 rounded-xl shadow-lg font-medium <?= $statusClass ?>" role="alert">
                <p class="font-bold text-lg mb-1">Current Water Status</p>
                <p><?= $statusMessage ?></p>
            </div>
        <?php endif; ?>

        
        <?php if (!$connError): ?>
        <div class="bg-white shadow-xl rounded-xl p-6 mb-8 border border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-money-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v4m0 0l-3 3m3-3l3 3"></path></svg>
                Goal Comparison (<?= date('M Y') ?>)
            </h2>
            
            <div class="p-4 mb-6 border-l-4 rounded-lg font-medium <?= $goalStatusClass ?>" role="alert">
                <p class="font-bold text-lg mb-1">Goal Status</p>
                <p><?= $goalStatusMessage ?></p>
                <p class="text-xs mt-1 text-gray-600 italic"><?= $goalSourceMessage ?></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center mb-6">
                
                <div class="p-5 bg-blue-50 rounded-xl shadow-md border-b-4 border-water-blue">
                    <p class="text-sm font-medium text-water-blue uppercase tracking-wider">Target Goal</p>
                    <p class="text-4xl font-extrabold text-water-blue mt-2">
                        <?= $displayGoalMonthly ?>
                        <span class="text-lg font-semibold text-gray-600">L</span>
                    </p>
                </div>
                
                <div class="p-5 bg-purple-50 rounded-xl shadow-md border-b-4 border-data-purple">
                    <p class="text-sm font-medium text-data-purple uppercase tracking-wider">Current Monthly Usage</p>
                    <p class="text-4xl font-extrabold text-data-purple mt-2">
                        <?= $displayUsageMonth ?>
                        <span class="text-lg font-semibold text-gray-600">L</span>
                    </p>
                </div>
                
                <div class="p-5 rounded-xl shadow-md border-b-4 <?= $isUnderGoal ? 'bg-green-50 border-data-green' : 'bg-red-50 border-data-red' ?>">
                    <p class="text-sm font-medium uppercase tracking-wider <?= $isUnderGoal ? 'text-data-green' : 'text-data-red' ?>">
                        <?= $isUnderGoal ? 'Liters Remaining' : 'Liters Overage' ?>
                    </p>
                    <p class="text-4xl font-extrabold mt-2 <?= $isUnderGoal ? 'text-data-green' : 'text-data-red' ?>">
                        <?= $displayUsageToGoal ?>
                        <span class="text-lg font-semibold text-gray-600">L</span>
                    </p>
                </div>
            </div>

            
            <div class="text-center mt-6">
                <a href="BackendforGoal.php" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-full shadow-lg text-white bg-data-purple hover:bg-purple-700 transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-purple-500 focus:ring-opacity-50">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Set Goal / Review Analysis
                </a>
            </div>
        </div>
        <?php endif; ?>
        


        
        <?php if (!$connError): ?>
        <div class="bg-white shadow-xl rounded-xl p-6 mb-8 border border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-water-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Water Usage History (Liters)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                
                <div class="p-5 bg-blue-50 rounded-xl shadow-md transition hover:shadow-lg border-b-4 border-water-blue">
                    <p class="text-sm font-medium text-water-blue uppercase tracking-wider">Today's Usage</p>
                    <p class="text-5xl font-extrabold text-water-blue mt-2">
                        <?= $displayUsageDay ?>
                        <span class="text-lg font-semibold text-gray-600">L</span>
                    </p>
                </div>
                
                <div class="p-5 bg-green-50 rounded-xl shadow-md transition hover:shadow-lg border-b-4 border-data-green">
                    <p class="text-sm font-medium text-data-green uppercase tracking-wider">Last 7 Days</p>
                    <p class="text-5xl font-extrabold text-data-green mt-2">
                        <?= $displayUsageWeek ?>
                        <span class="text-lg font-semibold text-gray-600">L</span>
                    </p>
                </div>
                
                <div class="p-5 bg-purple-50 rounded-xl shadow-md transition hover:shadow-lg border-b-4 border-data-purple">
                    <p class="text-sm font-medium text-data-purple uppercase tracking-wider">Last 30 Days</p>
                    <p class="text-5xl font-extrabold text-data-purple mt-2">
                        <?= $displayUsageMonth ?>
                        <span class="text-lg font-semibold text-gray-600">L</span>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        


        
        <?php if (!$connError): ?>
        <div class="bg-white shadow-xl rounded-xl p-6 mb-8 border border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-money-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Total Billing Overview
            </h2>
            <div class="grid grid-cols-2 gap-4 text-center">
                
                <div class="p-4 bg-green-50 rounded-lg shadow-md transition hover:shadow-lg border-b-2 border-data-green">
                    <p class="text-sm font-medium text-data-green">Rate</p>
                    <p id="display-rate" class="text-4xl font-extrabold text-money-green mt-1">
                        <?= $displayRatePerM3 ?>
                        <span class="text-lg font-semibold text-data-green">FRW / m³</span>
                    </p>
                </div>
                
                <div class="p-4 bg-money-green text-white rounded-lg shadow-xl transition hover:shadow-2xl">
                    <p class="text-sm font-medium opacity-90">Total Estimated Cost</p>
                    <p id="display-total-cost" class="text-4xl font-extrabold mt-1">
                        <?= $displayTotalCost ?>
                        <span class="text-lg font-semibold">FRW</span>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white shadow-xl rounded-xl p-6 mb-8 border border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Last Reading Overview</h2>

            <div class="mb-6 pb-4 border-b">
                <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-water-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v18m6-9l-6 6-6-6"></path></svg>
                    Total Volume & Flow
                </h3>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="p-4 bg-blue-50 rounded-lg shadow-md transition hover:shadow-lg">
                        <p class="text-sm font-medium text-water-blue">Lifetime Volume (L)</p>
                        <p id="display-volume" class="text-4xl font-extrabold text-water-blue mt-1"><?= $displayVolume ?></p>
                    </div>
                    <div class="p-4 bg-green-50 rounded-lg shadow-md transition hover:shadow-lg">
                        <p class="text-sm font-medium text-data-green">Flow Rate (L/min)</p>
                        <p id="display-flow" class="text-4xl font-extrabold text-data-green mt-1"><?= $displayFlow ?></p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-data-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-9.618 3.04A12.001 12.001 0 0012 21.056A12.001 12.001 0 0021.618 6.984z"></path></svg>
                    Water Quality Parameters
                </h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-center">
                    
                    <div class="p-3 bg-purple-50 rounded-lg shadow-md transition hover:shadow-lg">
                        <p class="text-xs font-medium text-data-purple">Turbidity (NTU)</p>
                        <p id="display-turbidity" class="text-2xl font-bold text-data-purple mt-1"><?= $displayTurbidity ?></p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg shadow-md transition hover:shadow-lg">
                        <p class="text-xs font-medium text-yellow-700">pH Level</p>
                        <p id="display-ph" class="text-2xl font-bold text-yellow-700 mt-1"><?= $displayPh ?></p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg shadow-md transition hover:shadow-lg">
                        <p class="text-xs font-medium text-data-red">TDS (ppm)</p>
                        <p id="display-tds" class="text-2xl font-bold text-data-red mt-1"><?= $displayTds ?></p>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-lg shadow-md transition hover:shadow-lg">
                        <p class="text-xs font-medium text-indigo-700">Temp (°C)</p>
                        <p id="display-temp" class="text-2xl font-bold text-indigo-700 mt-1"><?= $displayTemp ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-4 border-t text-sm text-gray-500 text-center" id="display-timestamp">Last meter reading recorded: <?= $displayTimestamp ?></div>
        </div>

        <details class="bg-white shadow-xl rounded-xl p-6 mb-8 border border-gray-200">
            <summary class="text-2xl font-bold text-gray-800 flex items-center cursor-pointer relative list-none">
                <svg class="w-6 h-6 mr-2 text-water-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Water Status Guidelines
            </summary>
            
            <div class="space-y-4 pt-4 border-t border-gray-100 mt-4">
                <div class="p-3 bg-green-100 border-l-4 border-data-green rounded">
                    <p class="font-semibold text-green-800">✅ Excellent Quality (TDS < 150 ppm, Turbidity $\le$ 1.0 NTU)</p>
                    <p class="text-sm text-green-700">Water is safe, clean, and meets high drinking standards. No action required.</p>
                </div>
                <div class="p-3 bg-blue-100 border-l-4 border-water-blue rounded">
                    <p class="font-semibold text-blue-800">🔸 Elevated TDS (TDS 150 - 300 ppm)</p>
                    <p class="text-sm text-blue-700">Total Dissolved Solids are elevated. The water is typically safe but may have a noticeable taste. Monitor closely and consider basic filtration.</p>
                </div>
                <div class="p-3 bg-orange-100 border-l-4 border-orange-500 rounded">
                    <p class="font-semibold text-orange-800">❗ High TDS Alert (TDS > 300 ppm)</p>
                    <p class="text-sm text-orange-700">The high level of solids suggests poor water quality. Consumption is not recommended without further testing or advanced purification (e.g., Reverse Osmosis).</p>
                </div>
                <div class="p-3 bg-red-100 border-l-4 border-data-red rounded">
                    <p class="font-semibold text-red-800">🛑 High Turbidity Warning (Turbidity > 5.0 NTU)</p>
                    <p class="text-sm text-red-700">The water is very cloudy. This indicates contamination (solids, sediment, or biological matter). **Consumption is strongly NOT recommended.**</p>
                </div>
                <div class="p-3 bg-yellow-100 border-l-4 border-yellow-500 rounded">
                    <p class="font-semibold text-yellow-800">🔄 No Flow Detected (Flow = 0 L/min, Volume > 0 L)</p>
                    <p class="text-sm text-yellow-700">The meter indicates total volume used is non-zero, but no water is currently flowing. This usually means the main water supply is turned off.</p>
                </div>
            </div>
        </details>
 
        <?php if (!$lastReading && !$connError): ?>
            <div class="p-4 text-center bg-yellow-100 text-yellow-800 rounded-lg font-medium">
                No data records found. Ensure your meter is connected and sending data to the database.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
