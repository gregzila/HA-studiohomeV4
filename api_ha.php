<?php
session_start();
if (!isset($_SESSION['auth_maison'])) { exit("Non autorisé"); }

// --- CONFIGURATION ---
$ha_token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiI3MGExZjE5NjUxNjQ0MDlkOTg5YTQwYjcxYmFiZGFhNyIsImlhdCI6MTc2OTIwNzcwNywiZXhwIjoyMDg0NTY3NzA3fQ.lb_boLkzECcmS1PCwVxzP607Thp8fHXC36jXwTgGdeM"; 
$ha_url = "http://192.168.1.52:8123";

$action = $_GET['action'] ?? null;
$entity = $_GET['entity'] ?? null;

if ($entity) {
    $domain = explode('.', $entity)[0];
    
    // Détermination automatique du service si l'action est générique
    $service = $action;
    
    if ($domain === 'button') {
        $service = 'press';
    } elseif ($domain === 'script') {
        $service = 'turn_on';
    } elseif ($domain === 'vacuum' && $action === 'toggle') {
        // Si on demande toggle sur un aspirateur, on considère que c'est pour démarrer
        $service = 'start';
    }

    $url = $ha_url . "/api/services/$domain/$service";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $ha_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["entity_id" => $entity]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    curl_close($ch);
}

// On retire la redirection automatique pour permettre les appels en arrière-plan (AJAX)
// Si c'est un appel classique, on redirige, sinon on affiche OK
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo "OK";
} else {
    header("Location: index.php");
}
exit;