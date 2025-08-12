<?php
include 'db.php';
$result = $conn->query("SELECT * FROM transactions ORDER BY date DESC");

$totalIncome = 0;
$totalExpense = 0;
$chartLabels = [];
$chartIncome = [];
$chartExpense = [];

while ($row = $result->fetch_assoc()) {
    if ($row['type'] == 'income') {
        $totalIncome += $row['amount'];
    } else {
        $totalExpense += $row['amount'];
    }

    $chartLabels[] = $row['date'];
    $chartIncome[] = ($row['type'] == 'income') ? $row['amount'] : 0;
    $chartExpense[] = ($row['type'] == 'expense') ? $row['amount'] : 0;
}

$balance = $totalIncome - $totalExpense;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Budget Tracker</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        // Fallback if primary CDN fails
        if (typeof Chart === 'undefined') {
            console.log('Primary CDN failed, trying fallback...');
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.js';
            script.onload = function() {
                console.log('Fallback CDN loaded successfully');
            };
            script.onerror = function() {
                console.error('Both CDNs failed to load Chart.js');
            };
            document.head.appendChild(script);
        }
    </script>
</head>
<body>
<h1>Budget Tracker</h1>

<div class="summary">
    <p>Total Income: $<?= number_format($totalIncome, 2) ?></p>
    <p>Total Expenses: $<?= number_format($totalExpense, 2) ?></p>
    <p><strong>Balance: $<?= number_format($balance, 2) ?></strong></p>
</div>

<h3>Add Transaction</h3>
<form method="POST" action="add.php">
    <select name="type">
        <option value="income">Income</option>
        <option value="expense">Expense</option>
    </select>
    <input type="text" name="description" placeholder="Description" required>
    <input type="number" name="amount" step="0.01" placeholder="Amount" required>
    <input type="date" name="date" required>
    <button type="submit">Add</button>
</form>

<h3>Income vs Expense Chart</h3>
<canvas id="budgetChart"></canvas>

<h3>All Transactions</h3>
<table>
    <tr>
        <th>Date</th>
        <th>Type</th>
        <th>Description</th>
        <th>Amount</th>
    </tr>
    <?php
    $result = $conn->query("SELECT * FROM transactions ORDER BY date DESC");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['date']}</td>
                <td>{$row['type']}</td>
                <td>{$row['description']}</td>
                <td>$" . number_format($row['amount'], 2) . "</td>
              </tr>";
    }
    ?>
</table>

<script>
    // Debug: Check if Chart.js loaded
    console.log('Script loaded, Chart object:', typeof Chart);
    
    // Wait for Chart.js to load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, Chart object:', typeof Chart);
        
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.error('Chart.js failed to load');
            document.getElementById('budgetChart').innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Chart.js failed to load. Please refresh the page.</p>';
            return;
        }

        try {
            const ctx = document.getElementById('budgetChart').getContext('2d');
            
            // Check if we have data to display
            const labels = <?= json_encode(array_reverse($chartLabels)) ?>;
            const incomeData = <?= json_encode(array_reverse($chartIncome)) ?>.map(Number);
            const expenseData = <?= json_encode(array_reverse($chartExpense)) ?>.map(Number);
            
            if (labels.length === 0) {
                document.getElementById('budgetChart').innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No data available for chart. Add some transactions to see your financial overview!</p>';
                return;
            }

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: incomeData,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#10B981',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Expenses',
                            data: expenseData,
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            pointBackgroundColor: '#EF4444',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    },
                    elements: {
                        point: {
                            radius: 4,
                            hoverRadius: 6
                        }
                    }
                }
            });
            
            console.log('Chart created successfully');
            console.log('Chart data:', { labels, incomeData, expenseData });
        } catch (error) {
            console.error('Error creating chart:', error);
            document.getElementById('budgetChart').innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Error creating chart: ' + error.message + '</p>';
        }
    });
</script>

</body>
</html>
