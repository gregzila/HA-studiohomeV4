<?php
session_start();

// --- CONFIGURATION ---
$password_valide = "rOOt1980*"; 

$message_debug = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $saisie = $_POST['password'] ?? '';
    
    // Diagnostic visuel
    $longueur_valide = strlen($password_valide);
    $longueur_saisie = strlen($saisie);
    
    if ($saisie === $password_valide) {
        $_SESSION['auth'] = true;
        $message_debug = "✅ SUCCÈS ! Le mot de passe correspond.";
    } else {
        $message_debug = "❌ ÉCHEC.<br>";
        $message_debug .= "Attendu : [" . $password_valide . "] (Long: $longueur_valide)<br>";
        $message_debug .= "Reçu : [" . $saisie . "] (Long: $longueur_saisie)";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card p-4 bg-secondary text-center" style="width: 400px;">
        <h4>Test de Connexion</h4>
        <form method="POST">
            <input type="password" name="password" class="form-control mb-3 text-center" placeholder="Tapez le mot de passe">
            <button type="submit" class="btn btn-primary w-100">Vérifier</button>
        </form>
        <div class="mt-4 p-2 bg-black text-warning font-monospace small">
            <?php echo $message_debug; ?>
        </div>
        
        <?php if(isset($_SESSION['auth'])): ?>
            <div class="alert alert-success mt-3">Vous êtes maintenant validé.</div>
        <?php endif; ?>
    </div>
</body>
</html>