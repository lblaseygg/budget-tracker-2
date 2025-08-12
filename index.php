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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <div class="header-content">
                <h1 class="app-title">Budget Tracker</h1>
                <p class="app-subtitle"></p>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Summary Cards -->
            <section class="summary-section">
                <div class="summary-grid">
                    <div class="summary-card income-card">

                        <div class="card-content">
                            <h3>Total Income</h3>
                            <p class="amount">$<?= number_format($totalIncome, 2) ?></p>
                        </div>
                    </div>
                    
                    <div class="summary-card expense-card">
                        
                        <div class="card-content">
                            <h3>Total Expenses</h3>
                            <p class="amount">$<?= number_format($totalExpense, 2) ?></p>
                        </div>
                    </div>
                    
                    <div class="summary-card balance-card <?= $balance >= 0 ? 'positive' : 'negative' ?>">
                        
                        <div class="card-content">
                            <h3>Balance</h3>
                            <p class="amount">$<?= number_format($balance, 2) ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Add Transaction Form -->
            <section class="form-section">
                <div class="section-header">
                    <h2>Add Transaction</h2>
                    <p>Record your income and expenses</p>
                </div>
                
                <form method="POST" action="add.php" class="transaction-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="type">Transaction Type</label>
                            <select name="type" id="type" required>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" name="amount" id="amount" step="0.01" placeholder="0.00" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text" name="description" id="description" placeholder="What's this for?" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" name="date" id="date" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <span>Add Transaction</span>
                        <span class="btn-icon">→</span>
                    </button>
                </form>
            </section>

            <!-- Chart Section -->
            <section class="chart-section">
                <div class="section-header">
                    <h2>Financial Overview</h2>
                    <p>Visualize your income vs expenses</p>
                </div>
                
                <div class="chart-container">
                    <canvas id="budgetChart"></canvas>
                </div>
            </section>

            <!-- Transactions Table -->
            <section class="transactions-section">
                <div class="section-header">
                    <h2>Recent Transactions</h2>
                    <p>Your latest financial activity</p>
                </div>
                
                <div class="table-wrapper">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM transactions ORDER BY date DESC LIMIT 10");
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $typeClass = $row['type'] == 'income' ? 'income-row' : 'expense-row';
                                    echo "<tr class='{$typeClass}'>
                                            <td>" . date('M j', strtotime($row['date'])) . "</td>
                                            <td>{$row['description']}</td>
                                            <td><span class='type-badge {$row['type']}'>{$row['type']}</span></td>
                                            <td class='amount-cell'>$" . number_format($row['amount'], 2) . "</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='no-data'>
                                        <div class='empty-state'>
                                            <span class='empty-icon'>📝</span>
                                            <p>No transactions yet. Add your first one above!</p>
                                        </div>
                                      </td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Set default date to today
        document.getElementById('date').valueAsDate = new Date();
        
        // Chart configuration
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js failed to load');
                document.getElementById('budgetChart').innerHTML = '<div class="chart-error"><p>Chart.js failed to load. Please refresh the page.</p></div>';
                return;
            }

            try {
                const ctx = document.getElementById('budgetChart').getContext('2d');
                
                const labels = <?= json_encode(array_reverse($chartLabels)) ?>;
                const incomeData = <?= json_encode(array_reverse($chartIncome)) ?>.map(Number);
                const expenseData = <?= json_encode(array_reverse($chartExpense)) ?>.map(Number);
                
                if (labels.length === 0) {
                    document.getElementById('budgetChart').innerHTML = '<div class="chart-empty"><p>No data available. Add some transactions to see your financial overview!</p></div>';
                    return;
                }

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Income',
                                data: incomeData,
                                backgroundColor: '#10B981',
                                borderColor: '#10B981',
                                borderWidth: 0,
                                borderRadius: 6,
                                borderSkipped: false
                            },
                            {
                                label: 'Expenses',
                                data: expenseData,
                                backgroundColor: '#EF4444',
                                borderColor: '#EF4444',
                                borderWidth: 0,
                                borderRadius: 6,
                                borderSkipped: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                align: 'center',
                                labels: {
                                    boxWidth: 20,
                                    boxHeight: 12,
                                    padding: 20,
                                    font: {
                                        size: 14,
                                        family: 'Inter, sans-serif',
                                        weight: '500'
                                    },
                                    usePointStyle: false
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f1f5f9',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 12,
                                        family: 'Inter, sans-serif'
                                    },
                                    padding: 10,
                                    color: '#64748b'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 12,
                                        family: 'Inter, sans-serif'
                                    },
                                    padding: 10,
                                    color: '#64748b'
                                }
                            }
                        },
                        layout: {
                            padding: {
                                top: 30,
                                bottom: 20,
                                left: 20,
                                right: 20
                            }
                        }
                    }
                });
                
                console.log('Chart created successfully');
            } catch (error) {
                console.error('Error creating chart:', error);
                document.getElementById('budgetChart').innerHTML = '<div class="chart-error"><p>Error creating chart. Please refresh the page.</p></div>';
            }
        });
    </script>
</body>
</html>
