<?php
// upload.php

// 1. CONFIGURATION DES ERREURS
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. CHEMINS VERS TON LECTEUR RÉSEAU Z:
$paths = [
    "films"        => "Z:/Films/", 
    "films_elisa"  => "Z:/Films Elisa/", 
    "series"       => "Z:/serietv/",
    "series_elisa" => "Z:/SerietvElisa/",
    "divers"       => "Z:/Divers Vidéo/" // <-- Nouveau dossier ajouté ici
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    
    if ($_FILES['fileToUpload']['error'] !== UPLOAD_ERR_OK) {
        die("<div class='alert alert-danger'>Erreur PHP Code : " . $_FILES['fileToUpload']['error'] . "</div>");
    }

    $targetFolder = $_POST['targetDir'];
    
    // Sécurité : vérifie si le dossier cible existe dans la liste
    if (!isset($paths[$targetFolder])) {
        die("<div class='alert alert-danger'>Dossier de destination invalide.</div>");
    }

    $fileName = basename($_FILES["fileToUpload"]["name"]);
    $destination = $paths[$targetFolder] . $fileName;

    // 3. DÉPLACEMENT DU FICHIER VERS LE NAS
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $destination)) {
        echo "OK"; 
    } else {
        echo "<div class='alert alert-danger'>❌ Erreur : Impossible de copier le fichier sur le lecteur Z:. Vérifiez la connexion du NAS ou les droits d'écriture.</div>";
    }
} else {
    header("Location: index.php");
    exit;
}
?>