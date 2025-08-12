<?php 
//Add transactions
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("INSERT INTO transactions (type, description, amount, date) VALUES (?, ?, ?,?)");
    $stmt->bind_param("ssds", $type, $description, $amount, $date);

    if ($stmt->execute()) {
        // Redirect back to main page with success
        header("Location: index.php?success=1");
        exit();
    } else {
        // Redirect back with error
        header("Location: index.php?error=1");
        exit();
    }
}
?>
