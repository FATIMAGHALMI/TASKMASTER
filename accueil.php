<?php
session_start();
include "config.php";
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['utilisateurId'])) {
    header('Location: login.php');
    exit();
}

// Fonction pour enregistrer les tâches dans un fichier texte
function enregistrerTachesDansFichier($taches, $username, $userprenom) {
    $fichier = 'taches.txt';
    $fichierHandle = fopen($fichier, 'w');
    if ($fichierHandle === false) {
        echo "Erreur lors de l'ouverture du fichier.";
        return;
    }
    fwrite($fichierHandle, "\t\t---------- $username $userprenom----------\n\n");
    foreach ($taches as $categorie => $tachesCategorie) {
        fwrite($fichierHandle,"\t\t\t Voici votre Taches :\n\n");
        fwrite($fichierHandle, "\t\t\t\tCatégorie: $categorie\n");
        foreach ($tachesCategorie as $tache) {
            $nom = $tache['task_name'];
            $description = $tache['task_description'];
            $dateEcheance = $tache['due_date'];
            $statut = $tache['task_status'];
            fwrite($fichierHandle, "\t\t\t\tNom: $nom\n");
            fwrite($fichierHandle, "\t\t\t\tDescription: $description\n");
            fwrite($fichierHandle, "\t\t\t\tDate d'échéance: $dateEcheance\n");
            fwrite($fichierHandle, "\t\t\t\tStatut: $statut\n\n");
        }
    }
    fclose($fichierHandle);
    
}

// Fonction pour enregistrer les tâches dans un cookie
function enregistrerTachesDansCookie($taches) {
    $tachesJson = json_encode($taches);
    setcookie('taches', $tachesJson, "/"); 
}




if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_task'])) {
    $task_id = $_POST['delete_task'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    if ($stmt->execute()) {
        echo "Tâche supprimée avec succès";
    } else {
        echo "Erreur lors de la suppression de la tâche: " . $stmt->error;
    }
    $stmt->close();
    header('Location: accueil.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category_name'])) {
    $category_name = $_POST['category_name'];
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $category_name);
    if ($stmt->execute()) {
        echo "Catégorie ajoutée avec succès";
    } else {
        echo "Erreur lors de l'ajout de la catégorie: " . $stmt->error;
    }
    $stmt->close();
    header('Location: accueil.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_name'])) {
    $task_name = $_POST['task_name'];
    $task_category = $_POST['task_category'];
    if ($task_category == 'new_category' && isset($_POST['new_category_name'])) {
        $new_category_name = $_POST['new_category_name'];
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $new_category_name);
        if ($stmt->execute()) {
            $task_category = $stmt->insert_id;
        } else {
            echo "Erreur lors de l'ajout de la catégorie: " . $stmt->error;
            exit();
        }
        $stmt->close();
    }
    $task_description = $_POST['task_description'];
    $due_date = $_POST['due_date'];
    $task_status = $_POST['task_status'];
    $stmt = $conn->prepare("INSERT INTO tasks (task_name, categorieId, task_description, due_date, task_status, utilisateurId) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $task_name, $task_category, $task_description, $due_date, $task_status, $_SESSION['utilisateurId']);
    if ($stmt->execute()) {
        echo "Tâche ajoutée avec succès";
    } else {
        echo "Erreur lors de l'ajout de la tâche: " . $stmt->error;
    }
    $stmt->close();
    header('Location: accueil.php');
    exit();
}

$user_id = $_SESSION['utilisateurId'];
$sql_user = "SELECT utilisateurNom, utilisateurPrenom FROM utilisateur WHERE utilisateurId = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($row_user = $result_user->fetch_assoc()) {
    $username = $row_user['utilisateurNom'];
    $userprenom = $row_user['utilisateurPrenom'];
} else {
    $username = "Utilisateur inconnu";
}
$stmt_user->close();

$sql = "SELECT t.*, c.name AS category_name FROM tasks t
        INNER JOIN categories c ON t.categorieId = c.id
        WHERE utilisateurId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks_by_category = array();
while ($row = $result->fetch_assoc()) {
    $category_name = $row["category_name"];
    $tasks_by_category[$category_name][] = $row;
}
$stmt->close();

enregistrerTachesDansFichier($tasks_by_category, $username, $userprenom);

enregistrerTachesDansFichier($tasks_by_category, $username, $userprenom);

$sql_categories = "SELECT * FROM categories";
$result_categories = $conn->query($sql_categories);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Usuarios</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kalam&display=swap">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="./js/accueilscript.js"></script>
</head>
<body class="bg-gray-300">
    <header class="h-11">
        <div id="header-nav" class="fixed top-0 w-full bg-gray-800 transform transition-transform duration-700 ease-in-out">
            <div class="container mx-auto flex justify-between items-center p-4">
                <div class="brand flex gap-11 text-sm">
                    <a href="/" class="text-white text-2xl">
                        <h1 class="font-serif">Time To Plan</h1>
                    </a>
                  
                </div>
                <nav id="menu" role="navigation">
                <ul class="flex gap-6 p-4">
    <li class="relative inline-block">
        <a href="#" methode="POST" id="exporter-link" class="text-white hover:bg-gray-700 rounded px-4 py-2 focus:outline-none">Exporter</a>
        <div id="export-options" class="hidden absolute left-1/2 transform -translate-x-1/2 mt-1 bg-gray-600 rounded shadow-lg">
            <a href="exporterpdf.php" target="_blank" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">PDF</a>
            <a href="exportercsv.php" target="_blank" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">CSV</a>
        </div>
    </li>
    <li>
        <a href="?logout=true" class="text-white p-2 hover:bg-gray-700 rounded">Logout</a>
    </li>
</ul>
                </nav>
            </div>
        </div>
    </header>
    <section class="p-24 flex">
        <div class="flex-1 p-8 text-center mb-12">
            <?php
            echo '<h1 class="text-3xl font-bold mb-8">' . $username . " " . $userprenom . '!</h1>';
            foreach ($tasks_by_category as $category_name => $tasks) {
                echo '<h2 class="text-2xl font-mono mb-5">' . $category_name . '</h2>';
                echo '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">';
                foreach ($tasks as $task) {
                    echo '<div class="bg-white border-2 border-gray-300 p-2 rounded-lg shadow w-auto">';
                    echo '<div class="flex justify-between items-center">';
                    echo '<h3 id="task" class="text-gray-700 font-serif text-2xl mb-2">' . $task["task_name"] . '</h3>';
                    echo '<div class="flex items-center">';
                    echo '<input type="checkbox" id="taskStatus-' . $task["id"] . '" class="mr-2" data-task-id="' . $task["id"] . '"' . ($task["task_status"] == "complété" ? " checked" : "") . '>';
                    echo '<p id="etat-' . $task["id"] . '" class="text-gray-500 text-xs">' . $task["task_status"] . '</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '<p id="description" class="text-gray-600 mb-0 text-xl">' . $task["task_description"] . '</p>';
                    echo '<p id="dateEchenace" class="text-gray-500 mb-2 text-sm">' . $task["due_date"] . '</p>';
                    echo '<div class="flex justify-end space-x-4 mt-6">';
                    echo '<div>';
                    echo '<button id="modifierTask" type="submit" class="bg-green-800 text-white px-3 py-1 rounded">Modifier</button>';
                    echo '</div>';
                    echo '<form class="delete-task-form" action="accueil.php" method="POST">';
                    echo '<input type="hidden" name="delete_task" value="' . $task["id"] . '">';
                    echo '<button type="button" class="bg-red-500 text-white delete-task-button px-3 py-1 rounded">Supprimer</button>';
                    echo '</form>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
            ?>
        </div>

        <div class="w-1/4 bg-gray-400 border-2 border-gray-300 p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-customFontBold mb-5">Ajouter une Tâche</h2>
            <form action="accueil.php" method="POST">
                <div class="mb-4">
                    <label for="task_name" class="block text-gray-700">Nom de la tâche:</label>
                    <input type="text" id="task_name" name="task_name" class="w-full p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label for="task_category" class="block text-gray-700">Catégories:</label>
                    <select id="task_category" name="task_category" class="w-full p-2 border rounded" required onchange="toggleNewCategoryField()">
                        <?php
                        if ($result_categories->num_rows > 0) {
                            while ($row = $result_categories->fetch_assoc()) {
                                echo '<option value="' . $row["id"] . '">' . $row["name"] . '</option>';
                            }
                        }
                        ?>
                        <option value="new_category">Ajouter</option>
                    </select>
                </div>
                <div class="mb-4" id="new_category_field" style="display: none;">
                    <label for="new_category_name" class="block text-gray-700">Nouvelle catégorie:</label>
                    <input type="text" id="new_category_name" name="new_category_name" class="w-full p-2 border rounded">
                </div>
                <div class="mb-4">
                    <label for="task_description" class="block text-gray-700">Description:</label>
                    <textarea id="task_description" name="task_description" class="w-full p-2 border rounded" required></textarea>
                </div>
                <div class="mb-4">
                    <label for="due_date" class="block text-gray-700">Date d'échéance:</label>
                    <input type="date" id="due_date" name="due_date" class="w-full p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label for="task_status" class="block text-gray-700">Statut:</label>
                    <select id="task_status" name="task_status" class="w-full p-2 border rounded" required>
                        <option value="en cours">En cours</option>
                        <option value="complété">Complété</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Ajouter</button>
            </form>
        </div>
    </section>
    
</body>
</html>
