<?php
session_start();
if (!isset($_SESSION['auth_maison'])) { header("Location: index.php"); exit; }

// --- CONFIGURATION ---
$ha_token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiI3MGExZjE5NjUxNjQ0MDlkOTg5YTQwYjcxYmFiZGFhNyIsImlhdCI6MTc2OTIwNzcwNywiZXhwIjoyMDg0NTY3NzA3fQ.lb_boLkzECcmS1PCwVxzP607Thp8fHXC36jXwTgGdeM"; 
$ha_url = "http://192.168.1.52:8123";

function getHaState($entity_id, $url, $token) {
    $ch = curl_init($url . "/api/states/" . $entity_id);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Liste des pièces et leurs entités
$rooms = [
    'parents' => ['name' => 'Chambre Parents', 'img' => 'Chambreparents.jpg', 'light' => 'light.lampe_chevet_chambre_parentale', 'temp' => 'climate.thermostat_chambre_parents_2', 'eve' => 'button.eve_shortcut_1', 'scapin' => '12'],
    'elisa'   => ['name' => 'Chambre Elisa', 'img' => 'Elisa.jpg', 'light' => null, 'temp' => 'climate.thermostat_chambre_elisa_2', 'eve' => 'button.eve_shortcut_2', 'scapin' => '9'],
    'justine' => ['name' => 'Chambre Justine', 'img' => 'Justine.jpg', 'light' => 'switch.0x048727fffe7b5724', 'temp' => 'climate.thermostat_bureau', 'eve' => 'button.eve_shortcut_3', 'scapin' => '2'],
    'salon'   => ['name' => 'Salon', 'img' => 'Salon.jpg', 'light' => 'light.lampe_salon', 'temp' => null, 'eve' => 'button.eve_shortcut_6', 'scapin' => '15'],
    'cuisine' => ['name' => 'Cuisine', 'img' => 'Cuisine.jpg', 'light' => 'light.lumiere_cuisine', 'temp' => null, 'eve' => 'button.eve_shortcut_5', 'scapin' => '11'],
    'sdb'     => ['name' => 'Salle de Bain', 'img' => 'SDB.jpg', 'light' => 'light.0xa81710b7b7e50000', 'temp' => 'climate.thermostat_salle_de_bain_2', 'eve' => 'button.eve_shortcut_4', 'scapin' => '10'],
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Contrôle par Pièces</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    
    <style>
        body { background: #000; color: white; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; overflow: hidden; }
        
        /* Conteneur Plein Écran pour Swiper */
        .swiper { width: 100%; height: 100vh; }
        
        .room-slide { position: relative; width: 100%; height: 100%; background-size: cover; background-position: center; }
        
        /* Overlay sombre pour lire les textes */
        .room-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.3); }

        /* Titre de la pièce */
        .room-title { position: absolute; top: 20px; width: 100%; text-align: center; font-size: 1.5rem; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.8); z-index: 10; }

        /* Widgets (Style Translucide) */
        .widget { position: absolute; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); 
                   padding: 10px; border-radius: 15px; color: white; text-align: center; min-width: 110px; z-index: 20; }
        
        /* Positions */
        .pos-light { top: 80px; left: 15px; }
        .pos-scapin { bottom: 120px; left: 15px; }
        .pos-eve { bottom: 20px; left: 15px; }
        .pos-temp { top: 80px; right: 15px; min-width: 140px; }

        .btn-action { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; width: 100%; 
                      padding: 8px; border-radius: 10px; margin-top: 5px; font-size: 0.85rem; }
        .btn-on { background: #ffc107 !important; color: black !important; font-weight: bold; }

        /* Bouton Retour */
        .btn-back { position: absolute; top: 15px; left: 15px; z-index: 100; background: rgba(0,0,0,0.5); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; border: 1px solid rgba(255,255,255,0.3); }
        
        /* Indicateur de Swipe */
        .swipe-hint { position: absolute; bottom: 10px; width: 100%; text-align: center; font-size: 0.7rem; opacity: 0.5; pointer-events: none; }
    </style>
</head>
<body>

    <a href="index.php" class="btn-back"><i class="bi bi-arrow-left"></i></a>

    <div class="swiper">
        <div class="swiper-wrapper">
            
            <?php foreach ($rooms as $id => $data): 
                // Récupération des états en temps réel
                $light_state = $data['light'] ? getHaState($data['light'], $ha_url, $ha_token) : null;
                $temp_state = $data['temp'] ? getHaState($data['temp'], $ha_url, $ha_token) : null;
            ?>
            <div class="swiper-slide">
                <div class="room-slide" style="background-image: url('<?php echo $data['img']; ?>');">
                    <div class="room-overlay"></div>
                    <div class="room-title"><?php echo $data['name']; ?></div>

                    <?php if($data['light']): ?>
                    <div class="widget pos-light">
                        <i class="bi bi-lightbulb-fill fs-4 <?php echo ($light_state['state'] == 'on') ? 'text-warning' : ''; ?>"></i><br>
                        <button onclick="toggleLight('<?php echo $data['light']; ?>')" class="btn-action <?php echo ($light_state['state'] == 'on') ? 'btn-on' : ''; ?>">
                            <?php echo ($light_state['state'] == 'on') ? 'Éteindre' : 'Allumer'; ?>
                        </button>
                    </div>
                    <?php endif; ?>

                    <div class="widget pos-scapin">
                        <small>Aspirateur</small><br><strong>SCAPIN</strong>
                        <button onclick="sendVacuumSegment('<?php echo $data['scapin']; ?>')" class="btn-action">Nettoyer</button>
                    </div>

                    <div class="widget pos-eve">
                        <small>Laveur</small><br><strong>EVE</strong>
                        <button onclick="callHA('<?php echo $data['eve']; ?>', 'press')" class="btn-action">Laver</button>
                    </div>

                    <?php if($data['temp']): ?>
                    <div class="widget pos-temp">
                        <small>Thermostat</small><br>
                        <div class="fs-3 fw-bold"><?php echo $temp_state['attributes']['current_temperature'] ?? '--'; ?>°</div>
                        <div class="small opacity-75">Cible: <?php echo $temp_state['attributes']['temperature'] ?? '--'; ?>°</div>
                        <div class="d-flex gap-1 mt-2">
                            <button onclick="callHA('<?php echo $data['temp']; ?>', 'set_temperature', 21)" class="btn btn-sm btn-outline-danger w-50">21°</button>
                            <button onclick="callHA('<?php echo $data['temp']; ?>', 'set_temperature', 18)" class="btn btn-sm btn-outline-info w-50">18°</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="swipe-hint"><i class="bi bi-chevron-left"></i> Swiper pour changer de pièce <i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
        <div class="swiper-pagination"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        // Initialisation de Swiper
        const swiper = new Swiper('.swiper', {
            loop: true, // Permet de revenir à la première pièce après la dernière
            pagination: { el: '.swiper-pagination', clickable: true },
            grabCursor: true,
        });

        function callHA(entity, action, val = null) {
            let url = `api_ha.php?action=${action}&entity=${entity}`;
            if(val) url += `&val=${val}`;
            fetch(url).then(() => {
                // On attend un peu pour que HA traite la commande avant de recharger l'état
                setTimeout(() => location.reload(), 300);
            });
        }

        function toggleLight(entity) {
            fetch(`api_ha.php?action=toggle&entity=${entity}`).then(() => location.reload());
        }

        function sendVacuumSegment(segmentId) {
            fetch(`api_ha.php?action=segment&entity=vacuum.rockrobo_vacuum_v1&segment=${segmentId}`)
            .then(() => alert('Scapin démarre !'));
        }
    </script>
</body>
</html>