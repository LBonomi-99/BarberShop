<?php
// admin.php

// PROTEZIONE SEMPLICE (Cambia la password!)
$password_segreta = "Matteo2025"; 
session_start();

if (isset($_POST['login']) && $_POST['pass'] == $password_segreta) {
    $_SESSION['logged_in'] = true;
}

if (!isset($_SESSION['logged_in'])) {
    echo '<form method="POST" style="text-align:center; margin-top:100px; font-family:sans-serif;">
            <h2>Area Riservata Matteo Cavallara</h2>
            <input type="password" name="pass" placeholder="Password" required style="padding:10px;">
            <button type="submit" name="login" style="padding:10px;">Entra</button>
          </form>';
    exit;
}

// CONNESSIONE DB
$conn = new mysqli('localhost', 'root', '', 'barber_shop'); // Configura come sopra

// GESTIONE CAMBIO STATO
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stato = $_GET['action']; // 'accettato' o 'rifiutato'
    $conn->query("UPDATE prenotazioni SET stato='$stato' WHERE id=$id");
    header("Location: admin.php"); // Ricarica pulita
}

// RECUPERO PRENOTAZIONI (Ordina per data più recente)
$result = $conn->query("SELECT * FROM prenotazioni ORDER BY data_richiesta DESC");
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Admin - Prenotazioni</title>
    <link href="https://fonts.googleapis.com/css2?family=Lato&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Lato', sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #1C1C1C; border-bottom: 2px solid #B8860B; padding-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #A1B59C; color: white; }
        
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .in_attesa { background: #ffd700; color: #333; }
        .accettato { background: #28a745; color: white; }
        .rifiutato { background: #dc3545; color: white; }

        .btn { text-decoration: none; padding: 5px 10px; border-radius: 3px; color: white; font-size: 0.8rem; margin-right: 5px; display: inline-block;}
        .btn-wa { background-color: #25D366; } /* WhatsApp Green */
        .btn-cal { background-color: #4285F4; } /* Google Blue */
        .btn-ok { background-color: #1C1C1C; }
        .btn-ko { background-color: #999; }

        .actions { display: flex; gap: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestione Appuntamenti</h1>
        
        <table>
            <thead>
                <tr>
                    <th>Data/Ora</th>
                    <th>Cliente</th>
                    <th>Servizio</th>
                    <th>Stato</th>
                    <th>Azioni Rapide</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo date('d/m/Y', strtotime($row['data_appuntamento'])); ?></strong><br>
                            <?php echo ucfirst($row['ora_appuntamento']); ?>
                        </td>
                        <td>
                            <?php echo $row['nome']; ?><br>
                            <small><?php echo $row['telefono']; ?></small>
                        </td>
                        <td><?php echo $row['servizio']; ?></td>
                        <td>
                            <span class="badge <?php echo $row['stato']; ?>">
                                <?php echo $row['stato']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if($row['stato'] == 'in_attesa'): ?>
                                    <a href="admin.php?action=accettato&id=<?php echo $row['id']; ?>" class="btn btn-ok">✔ Accetta</a>
                                    <a href="admin.php?action=rifiutato&id=<?php echo $row['id']; ?>" class="btn btn-ko">✖ Rifiuta</a>
                                <?php endif; ?>

                                <?php 
                                    // Pulisce il numero (toglie spazi)
                                    $tel_clean = preg_replace('/[^0-9]/', '', $row['telefono']); 
                                    // Messaggio dinamico
                                    $msg_ok = "Ciao " . $row['nome'] . ", confermo il tuo appuntamento per il " . date('d/m', strtotime($row['data_appuntamento'])) . " " . $row['ora_appuntamento'] . ". A presto, Matteo.";
                                    $msg_ko = "Ciao " . $row['nome'] . ", purtroppo per l'orario richiesto sono pieno. Possiamo spostare?";
                                    
                                    $wa_link_ok = "https://wa.me/39$tel_clean?text=" . urlencode($msg_ok);
                                    $wa_link_ko = "https://wa.me/39$tel_clean?text=" . urlencode($msg_ko);
                                ?>

                                <?php if($row['stato'] == 'accettato'): ?>
                                    <a href="<?php echo $wa_link_ok; ?>" target="_blank" class="btn btn-wa"><i class="fab fa-whatsapp"></i> Invia Conferma WA</a>
                                    <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=Taglio+<?php echo urlencode($row['nome']); ?>&dates=<?php echo date('Ymd', strtotime($row['data_appuntamento'])); ?>T090000/<?php echo date('Ymd', strtotime($row['data_appuntamento'])); ?>T100000&details=Tel:+<?php echo $tel_clean;?>" target="_blank" class="btn btn-cal">Salva in Agenda</a>
                                <?php elseif($row['stato'] == 'rifiutato'): ?>
                                    <a href="<?php echo $wa_link_ko; ?>" target="_blank" class="btn btn-wa">Invia Declino WA</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>