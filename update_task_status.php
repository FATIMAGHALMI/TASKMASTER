<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $task_status = $_POST['task_status'];

  
    $servername = "localhost";
    $username = "root";
    $passwordDB = "";
    $dbname = "task";

    $conn = new mysqli($servername, $username, $passwordDB, $dbname);

    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

 
    $stmt = $conn->prepare("UPDATE tasks SET task_status = ? WHERE id = ?");
    $stmt->bind_param("si", $task_status, $task_id);

    if ($stmt->execute()) {
        echo "Statut de la tâche mis à jour avec succès";
    } else {
        echo "Erreur lors de la mise à jour du statut de la tâche: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
