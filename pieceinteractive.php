<?php
session_start();
if (!isset($_SESSION['auth_maison'])) { header("Location: index.php"); exit; }

$liste_pieces = [
    'salon' => ['nom' => 'Salon', 'img' => 'Salon.jpg'],
    'cuisine' => ['nom' => 'Cuisine', 'img' => 'Cuisine.jpg'],
    'parents' => ['nom' => 'Chambre Parents', 'img' => 'Chambreparents.jpg'],
    'elisa' => ['nom' => 'Chambre Elisa', 'img' => 'Elisa.jpg'],
    'justine' => ['nom' => 'Chambre Justine', 'img' => 'Justine.jpg'],
    'sdb' => ['nom' => 'Salle de Bain', 'img' => 'SDB.jpg']
];

$p = $_GET['p'] ?? 'salon';
$current = $liste_pieces[$p];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Éditeur de Zones - <?php echo $current['nom']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body { background: #1a1a1a; color: white; user-select: none; }
        .room-container { position: relative; display: inline-block; cursor: crosshair; border: 2px solid #444; }
        .room-img { width: 100%; max-width: 1000px; display: block; -webkit-user-drag: none; }
        
        /* Le rectangle qu'on est en train de dessiner */
        #selection-box {
            position: absolute;
            border: 2px solid #0d6efd;
            background: rgba(13, 110, 253, 0.2);
            display: none;
            pointer-events: none;
        }

        /* Les boutons déjà existants (pour les voir) */
        .hotspot-preview {
            position: absolute;
            border: 1px solid rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #code-output { background: #000; color: #0f0; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 13px; min-height: 100px; border: 1px solid #333; }
        .step-card { background: #2d2d2d; padding: 15px; border-radius: 10px; height: 100%; }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 text-center">
            <div class="d-flex justify-content-between mb-3">
                <h3><i class="bi bi-pencil-square"></i> Dessine ta zone sur : <?php echo $current['nom']; ?></h3>
                <a href="index.php" class="btn btn-sm btn-outline-light">Quitter l'éditeur</a>
            </div>

            <div class="room-container" id="container">
                <img src="<?php echo $current['img']; ?>" class="room-img" id="room-img">
                <div id="selection-box"></div>
                </div>
            
            <div class="mt-3 text-muted">
                <i class="bi bi-mouse"></i> <strong>Clic gauche maintenu</strong> pour tracer un rectangle.
            </div>
        </div>

        <div class="col-lg-4">
            <div class="step-card">
                <h5>1. Choisir une pièce</h5>
                <select class="form-select mb-4" onchange="window.location.href='?p='+this.value">
                    <?php foreach($liste_pieces as $key => $val): ?>
                        <option value="<?php echo $key; ?>" <?php echo ($p == $key) ? 'selected' : ''; ?>><?php echo $val['nom']; ?></option>
                    <?php endforeach; ?>
                </select>

                <h5>2. Code généré</h5>
                <p class="small text-muted">Copie ce code dans ton fichier après avoir tracé la zone.</p>
                <div id="code-output">Tracer une zone pour voir le code...</div>
                
                <button class="btn btn-primary w-100 mt-3" onclick="copyCode()"><i class="bi bi-clipboard"></i> Copier le code</button>

                <div class="alert alert-warning mt-4 small">
                    <i class="bi bi-info-circle"></i> <strong>Astuce :</strong> Une fois le code copié, colle-le dans la section <code>&lt;style&gt;</code> (pour le CSS) et dans le <code>&lt;body&gt;</code> (pour le bouton) de ta page finale.
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    const container = document.getElementById('container');
    const box = document.getElementById('selection-box');
    const img = document.getElementById('room-img');
    const output = document.getElementById('code-output');

    let startX, startY, isDrawing = false;

    container.addEventListener('mousedown', (e) => {
        isDrawing = true;
        const rect = container.getBoundingClientRect();
        startX = e.clientX - rect.left;
        startY = e.clientY - rect.top;

        box.style.left = startX + 'px';
        box.style.top = startY + 'px';
        box.style.width = '0px';
        box.style.height = '0px';
        box.style.display = 'block';
    });

    window.addEventListener('mousemove', (e) => {
        if (!isDrawing) return;
        const rect = container.getBoundingClientRect();
        let currentX = e.clientX - rect.left;
        let currentY = e.clientY - rect.top;

        // Empêcher de sortir de l'image
        currentX = Math.max(0, Math.min(currentX, rect.width));
        currentY = Math.max(0, Math.min(currentY, rect.height));

        const width = currentX - startX;
        const height = currentY - startY;

        box.style.width = Math.abs(width) + 'px';
        box.style.height = Math.abs(height) + 'px';
        box.style.left = (width > 0 ? startX : currentX) + 'px';
        box.style.top = (height > 0 ? startY : currentY) + 'px';
    });

    window.addEventListener('mouseup', () => {
        if (!isDrawing) return;
        isDrawing = false;

        const rect = container.getBoundingClientRect();
        
        // Calcul en pourcentage
        const topPc = (parseFloat(box.style.top) / rect.height) * 100;
        const leftPc = (parseFloat(box.style.left) / rect.width) * 100;
        const widthPc = (parseFloat(box.style.width) / rect.width) * 100;
        const heightPc = (parseFloat(box.style.height) / rect.height) * 100;

        // Génération du code
        const className = "zone-" + Math.floor(Math.random() * 1000);
        const code = `/* --- COPIE CE CSS --- */\n` +
                     `.${className} { \n  top: ${topPc.toFixed(1)}%; \n  left: ${leftPc.toFixed(1)}%; \n  width: ${widthPc.toFixed(1)}%; \n  height: ${heightPc.toFixed(1)}%; \n}\n\n` +
                     `/* --- COPIE CE HTML --- */\n` +
                     `<div class="hotspot ${className}" onclick="action('ENTITY_ID', 'toggle')"></div>`;
        
        output.innerText = code;
    });

    function copyCode() {
        const text = output.innerText;
        navigator.clipboard.writeText(text);
        alert("Code copié !");
    }
</script>

</body>
</html>