<?php

$host = 'localhost';
$db = 'smart water meter'; 
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [

    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
$message_type = 'info';
$message_body = 'Please submit the form to set a new goal.';
$all_goals = [];
$pdo = null; 

try {

    $pdo = new PDO($dsn, $user, $pass, $options);
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $goal_name = filter_input(INPUT_POST, 'goal_name', FILTER_SANITIZE_SPECIAL_CHARS);
        $target_amount = filter_input(INPUT_POST, 'target_amount', FILTER_VALIDATE_FLOAT);
        $target_date = filter_input(INPUT_POST, 'target_date', FILTER_SANITIZE_STRING);
        $goal_period = filter_input(INPUT_POST, 'goal_period', FILTER_SANITIZE_STRING); 
        $valid_periods = ['Day', 'Week', 'Month'];
        $is_period_valid = in_array($goal_period, $valid_periods);

        if (!$goal_name || $target_amount === false || $target_amount <= 0 || !$target_date || !$is_period_valid) {
            $message_type = 'error';
            $message_body = 'Error: Please ensure all fields are filled correctly. Target amount must be a positive number and Goal Period must be selected.';
        } else {
            $sql = "INSERT INTO goals (goal_name, target_amount, goal_period, target_date) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            $stmt->execute([$goal_name, $target_amount, $goal_period, $target_date]);

            $message_type = 'success';
            $message_body = "Success! Goal \"{$goal_name}\" has been saved. Target: **" . number_format($target_amount, 2) . "** per **{$goal_period}**, due **{$target_date}**.";

        }
    }

    $sql_fetch = "SELECT id, goal_name, target_amount, goal_period, target_date FROM goals ORDER BY target_date DESC, id DESC";
    $stmt_fetch = $pdo->query($sql_fetch);
    $all_goals = $stmt_fetch->fetchAll();


} catch (\PDOException $e) {
    $message_type = 'error';
    if ($message_type === 'info' || $_SERVER["REQUEST_METHOD"] !== "POST") {
        $message_body = 'Database Connection/Fetch Error: Could not connect to the database or retrieve goals. Error: ' . $e->getMessage();
    } else {
        $message_body = 'Database Save Error: Could not save the goal. Error: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goal Submission Result & Goal List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7f9fc; }

        @keyframes goalFadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-goal-load {
            animation: goalFadeIn 0.5s ease-out forwards;
            opacity: 0; 
        }

        @media (max-width: 767px) {
            .goal-card {
                display: flex;
                flex-direction: column;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                padding: 1rem;
                margin-bottom: 1rem;
                background-color: white;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
                transition: transform 0.2s, box-shadow 0.2s; 
            }
            .goal-card:hover {
                transform: translateY(-3px); 
                box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2); 
            }
            .goal-card div {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 0;
                border-bottom: 1px dashed #f3f4f6; 
            }
            .goal-card div:last-child { border-bottom: none; }
            .goal-card .label { font-weight: 600; color: #4b5563; font-size: 0.875rem; }
            .goal-card .value { font-weight: 700; color: #1f2937; text-align: right; font-size: 0.875rem; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center p-4 md:p-8">
    <div class="w-full max-w-4xl"> 

        <div class="bg-white p-8 md:p-10 rounded-xl shadow-2xl mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Goal Submission Status</h1>
            
            <?php
            $bg_color = '';
            $icon = '';
            $title = '';

            if ($message_type === 'success') {
                $bg_color = 'bg-green-100 border-green-600 text-green-800';
                $icon = '✅';
                $title = 'Goal Saved!';
            } elseif ($message_type === 'error') {
                $bg_color = 'bg-red-100 border-red-600 text-red-800';
                $icon = '❌';
                $title = 'Submission Error';
            } else { 
                $bg_color = 'bg-blue-100 border-blue-600 text-blue-800';
                $icon = 'ℹ️';
                $title = 'Awaiting Submission';
            }
            ?>

            <div class="p-4 rounded-lg border-l-4 border-r-4 <?= $bg_color ?> transition duration-300 shadow-md" role="alert">
                <p class="font-bold text-lg mb-2"><?= $icon ?> <?= $title ?></p>
                <p class="text-sm mt-1">
                    <?= nl2br(htmlspecialchars(str_replace(['**', '*'], ['<b>', '</b>'], $message_body))) ?>
                </p>
            </div>

            <div class="mt-8 text-center">
                <a href="Goal.html"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 transform hover:scale-[1.03]">
                    &larr; Go Back to Set Another Goal
                </a>
            </div>
        </div>

        <div class="mt-12 bg-white p-8 md:p-10 rounded-xl shadow-2xl">
            <h2 class="text-2xl font-bold text-white mb-6 text-center p-4 rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 shadow-lg">
                Advanced: Goal History
            </h2>

            <?php if (empty($all_goals)): ?>
                <p class="text-center text-gray-500 p-4 bg-gray-50 rounded-lg">No goals have been saved yet.</p>
            <?php else: ?>
                
                <?php $delay_counter = 0; ?>

                <div class="overflow-x-auto hidden md:block rounded-xl border border-gray-200 shadow-lg">
                    <table class="min-w-full divide-y divide-indigo-200">
                        <thead class="bg-indigo-50 border-b-2 border-indigo-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-indigo-700 uppercase tracking-wider w-1/4">Goal Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-indigo-700 uppercase tracking-wider">Target Amount (L)</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-indigo-700 uppercase tracking-wider">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-indigo-700 uppercase tracking-wider">Target Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($all_goals as $goal): ?>
                            <?php $delay_counter += 0.05; ?>
                            <tr class="animate-goal-load hover:bg-purple-50 transition duration-200 cursor-pointer" 
                                style="animation-delay: <?= $delay_counter ?>s; ">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($goal['goal_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 font-bold"><?= number_format($goal['target_amount'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($goal['goal_period']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($goal['target_date']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="md:hidden mt-4">
                    <?php $delay_counter = 0; ?>
                    <?php foreach ($all_goals as $goal): ?>
                    <?php $delay_counter += 0.1; ?>
                    <div class="goal-card animate-goal-load" style="animation-delay: <?= $delay_counter ?>s;">
                        <div><span class="label">Goal Name</span> <span class="value text-right font-semibold text-indigo-800"><?= htmlspecialchars($goal['goal_name']) ?></span></div>
                        <div><span class="label">Target Amount</span> <span class="value text-purple-600"><?= number_format($goal['target_amount'], 2) ?> L</span></div>
                        <div><span class="label">Period</span> <span class="value"><?= htmlspecialchars($goal['goal_period']) ?></span></div>
                        <div><span class="label">Target Date</span> <span class="value"><?= htmlspecialchars($goal['target_date']) ?></span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
