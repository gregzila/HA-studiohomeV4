<?php
session_start();
if (!isset($_SESSION['auth_maison'])) { header("Location: index.php"); exit; }

$piece_id = "salon"; // Identifiant de la pièce
$config_file = "config_$piece_id.json";

// Sauvegarde automatique via AJAX
if (isset($_POST['save_hotspot'])) {
    $current_data = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
    $current_data[] = [
        'name' => $_POST['name'],
        'entity' => $_POST['entity'],
        'top' => $_POST['top'],
        'left' => $_POST['left'],
        'width' => $_POST['width'],
        'height' => $_POST['height']
    ];
    file_put_contents($config_file, json_encode($current_data));
    exit("success");
}

// Chargement des boutons existants
$hotspots = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Salon Interactif</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #121212; color: white; overflow-x: hidden; }
        .canvas-container { position: relative; display: inline-block; margin: 0 auto; }
        .room-img { width: 100%; max-width: 1100px; display: block; border-radius: 15px; }
        
        /* Style des zones créées */
        .hotspot {
            position: absolute;
            border: 2px solid transparent;
            background: rgba(255, 255, 255, 0.05);
            cursor: pointer;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }
        .hotspot:hover { background: rgba(13, 110, 253, 0.2); border-color: #0d6efd; }
        .hotspot span { display: none; font-size: 10px; background: rgba(0,0,0,0.7); padding: 2px 5px; border-radius: 4px; }
        .hotspot:hover span { display: block; }

        /* Mode Édition */
        .edit-mode .hotspot { border: 1px dashed #ffc107; background: rgba(255, 193, 7, 0.1); }
        #selection-box { position: absolute; border: 2px solid #ffc107; background: rgba(255, 193, 7, 0.2); display: none; z-index: 100; }
        
        .admin-panel { position: fixed; bottom: 20px; right: 20px; z-index: 1000; background: rgba(0,0,0,0.8); padding: 15px; border-radius: 10px; border: 1px solid #333; }
    </style>
</head>
<body>

<div class="container py-4 text-center">
    <div class="d-flex justify-content-between mb-3">
        <h2><i class="bi bi-display"></i> Salon Interactif</h2>
        <button class="btn btn-warning" onclick="toggleEditMode()" id="btn-edit">
            <i class="bi bi-pencil"></i> Mode Édition
        </button>
    </div>

    <div class="canvas-container" id="canvas">
        <img src="Salon.jpg" class="room-img" id="room-img" draggable="false">
        
        <?php foreach ($hotspots as $h): ?>
            <div class="hotspot" 
                 style="top:<?=$h['top']?>%; left:<?=$h['left']?>%; width:<?=$h['width']?>%; height:<?=$h['height']?>%;"
                 onclick="callHA('<?=$h['entity']?>')">
                 <span><?=$h['name']?></span>
            </div>
        <?php endforeach; ?>

        <div id="selection-box"></div>
    </div>
</div>

<div class="modal fade" id="hotspotModal" tabindex="-1" style="color: black;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5>Nouveau bouton</h5></div>
            <div class="modal-body">
                <input type="text" id="btn-name" class="form-control mb-2" placeholder="Nom (ex: Allumer TV)">
                <input type="text" id="btn-entity" class="form-control" placeholder="Entité HA (ex: switch.tv)">
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="saveNewHotspot()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let isEditMode = false;
let isDrawing = false;
let startX, startY, currentCoords = {};

function toggleEditMode() {
    isEditMode = !isEditMode;
    document.body.classList.toggle('edit-mode');
    document.getElementById('btn-edit').className = isEditMode ? "btn btn-danger" : "btn btn-warning";
    alert(isEditMode ? "Mode édition activé : dessinez une zone !" : "Mode édition désactivé.");
}

const canvas = document.getElementById('canvas');
const box = document.getElementById('selection-box');

canvas.onmousedown = (e) => {
    if (!isEditMode) return;
    isDrawing = true;
    const rect = canvas.getBoundingClientRect();
    startX = e.clientX - rect.left;
    startY = e.clientY - rect.top;
    box.style.display = 'block';
};

window.onmousemove = (e) => {
    if (!isDrawing) return;
    const rect = canvas.getBoundingClientRect();
    let x = e.clientX - rect.left;
    let y = e.clientY - rect.top;
    
    currentCoords = {
        top: (Math.min(startY, y) / rect.height) * 100,
        left: (Math.min(startX, x) / rect.width) * 100,
        width: (Math.abs(x - startX) / rect.width) * 100,
        height: (Math.abs(y - startY) / rect.height) * 100
    };

    box.style.top = Math.min(startY, y) + "px";
    box.style.left = Math.min(startX, x) + "px";
    box.style.width = Math.abs(x - startX) + "px";
    box.style.height = Math.abs(y - startY) + "px";
};

window.onmouseup = () => {
    if (!isDrawing) return;
    isDrawing = false;
    new bootstrap.Modal(document.getElementById('hotspotModal')).show();
};

function saveNewHotspot() {
    const data = new FormData();
    data.append('save_hotspot', true);
    data.append('name', document.getElementById('btn-name').value);
    data.append('entity', document.getElementById('btn-entity').value);
    data.append('top', currentCoords.top);
    data.append('left', currentCoords.left);
    data.append('width', currentCoords.width);
    data.append('height', currentCoords.height);

    fetch(window.location.href, { method: 'POST', body: data })
    .then(() => location.reload());
}

function callHA(entity) {
    if (isEditMode) return;
    fetch(`api_ha.php?action=toggle&entity=${entity}`)
    .then(() => alert("Action envoyée !"));
}
</script>
</body>
</html>