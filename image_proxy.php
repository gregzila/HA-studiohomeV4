<?php
// image_proxy.php
$url = $_GET['url'] ?? '';
$token = $_GET['token'] ?? '';

if ($url && $token) {
    // On reconstruit l'URL complète vers Plex
    $full_url = $url . "?X-Plex-Token=" . $token;
    
    // On définit le header pour dire au navigateur que c'est une image
    header('Content-Type: image/jpeg');
    
    // On télécharge et on affiche l'image
    echo file_get_contents($full_url);
}
?>