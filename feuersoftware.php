<?php
date_default_timezone_set('Europe/Berlin');
/*
Plugin Name: Feuer Software Einsätze
Description: Zeigt die aktuellen Einsätze der Feuer Software API in einer Tabelle an.
Version: 1.0.0
Author: Jimmy (Github: JimmyKinzig)
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}
// =====================
// Datenbanktabelle erstellen wenn das Plugin aktiviert wird
// =====================
function feuer_api_einsaetze_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'feuer_einsaetze';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			ort text NOT NULL,
			datum text NOT NULL,
			uhrzeit time NOT NULL,
			stichwort text NOT NULL,
			info text NOT NULL,
			approved tinyint(1) DEFAULT 0 NOT NULL,
			deleted tinyint(1) DEFAULT 0 NOT NULL,
			approved_by varchar(100) DEFAULT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'feuer_api_einsaetze_install');

// =====================
// Menüpunkt in der Admin-Seitenleiste hinzufügen
// =====================
function feuer_api_menu_seitenleiste() {
    add_menu_page(
        'Feuer Software',         // Seitentitel
        'Feuer Software',         // Menütext
        'manage_options',         // Berechtigungen
        'feuer-software',         // Slug
        'feuer_api_einsaetze_render', // Callback-Funktion für Hauptmenüseite (Einsätze)
        'dashicons-feedback',   // Symbol
        6                         // Position
    );


    add_submenu_page(
        'feuer-software',
        'Einsätze',               // Seitentitel
        'Einsätze',               // Menütext
        'manage_options',         // Berechtigungen
        'feuer-software',         // Slug (zeigt auf dieselbe Funktion wie Hauptseite)
        'feuer_api_einsaetze_render'
    );

    add_submenu_page(
        'feuer-software',
        'Einstellungen',          // Seitentitel
        'Einstellungen',          // Menütext
        'manage_options',         // Berechtigungen
        'feuer-software-einstellungen',
        'feuer_api_einstellungen_render'
    );
    add_menu_page(
        'Übersicht über Einsätze',  // Seitentitel
        'Einsätze Übersicht',       // Menütext
        'manage_options',           // Berechtigungen
        'feuer-software-uebersicht',// Slug für die Seite
        'feuer_api_uebersicht_render', // Callback-Funktion
        'dashicons-chart-bar',      // Icon
        5                           // Position im Menü (niedrigere Zahl = weiter oben)
    );
}
add_action('admin_menu', 'feuer_api_menu_seitenleiste');

// =====================
// Render-Funktion für die Übersicht-Seite
// =====================
function feuer_api_uebersicht_render() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'feuer_einsaetze';

    $total_einsaetze = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE deleted = 0");

    $veroeffentlichte_einsaetze = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE approved = 1 AND deleted = 0");

    $ausstehende_einsaetze = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE approved = 3 AND deleted = 0");

    echo '<div class="wrap">';
    echo '<h1>Übersicht über die Einsätze</h1>';

    echo '<div class="einsatz-overview">';

    echo '<div class="einsatz-box total">';
    echo '<h2>Gesamtanzahl der Einsätze</h2>';
    echo '<p>' . esc_html($total_einsaetze) . '</p>';
    echo '</div>';

    echo '<div class="einsatz-box published">';
    echo '<h2>Veröffentlichte Einsätze</h2>';
    echo '<p>' . esc_html($veroeffentlichte_einsaetze) . '</p>';
    echo '</div>';

    echo '<div class="einsatz-box pending">';
    echo '<h2>Ausstehende Einsätze</h2>';
    echo '<p>' . esc_html($ausstehende_einsaetze) . '</p>';
    echo '</div>';

    echo '</div>';

    echo '<style>
        .einsatz-overview {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .einsatz-box {
            background: #f7f7f7;
            border: 1px solid #ddd;
            padding: 20px;
            width: 30%;
            text-align: center;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin: 10px;
            transition: all 0.3s ease;
        }
        .einsatz-box:hover {
            transform: scale(1.05);
        }
        .einsatz-box h2 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        .einsatz-box p {
            font-size: 2em;
            margin: 0;
        }
        .einsatz-box.total { background-color: #007bff; color: #fff; }
        .einsatz-box.published { background-color: #28a745; color: #fff; }
        .einsatz-box.pending { background-color: #ffc107; color: #fff; }

        @media (max-width: 1024px) {
            .einsatz-box {
                width: 45%;
            }
        }
        @media (max-width: 768px) {
            .einsatz-box {
                width: 100%;
            }
        }
    </style>';

    echo '</div>'; // end .wrap
}
// =====================
// Einsätze Seite rendern
// =====================
function feuer_api_einsaetze_render() {
    echo '<div class="wrap">';
    echo '<h1>Feuer Software Einsätze</h1>';

    settings_errors('feuer_api_actions');

    $letzte_sync = get_option('feuer_api_letzte_sync');
    if ($letzte_sync) {
        echo '<p><strong>Letzte Synchronisation:</strong> ' . esc_html($letzte_sync) . '</p>';
    } else {
        echo '<p><strong>Letzte Synchronisation:</strong> Noch nicht synchronisiert.</p>';
    }


    echo '<h2>Alle Einsätze</h2>';
    echo feuer_api_einsaetze_admin_anzeigen();

    echo '<script type="text/javascript">
            function toggleAbgelehnte() {
                var content = document.getElementById("abgelehnte-einsaetze-content");
                content.style.display = content.style.display === "none" ? "block" : "none";
            }
          </script>';

    echo '</div>';
}




// =====================
// API-Einsätze abrufen und anzeigen
// =====================
function feuer_api_einsaetze_anzeigen() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'feuer_einsaetze';

    $api_key = get_option('feuer_api_key');

    if (empty($api_key)) {
        return 'Bitte konfiguriere deinen API-Schlüssel in den Einstellungen.';
    }

    $api_url = 'https://connectapi.feuersoftware.com/interfaces/public/operation';

    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . esc_attr($api_key)
        )
    ));

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return 'Fehler beim Abrufen der Einsätze oder ungültiger API-Schlüssel.';
    }

    $data = wp_remote_retrieve_body($response);
    $einsaetze = json_decode($data, true);

    if (!empty($einsaetze)) {
        foreach ($einsaetze as $einsatz) {
            $ort = isset($einsatz['Address']['City']) ? $einsatz['Address']['City'] : null;
            $datum = isset($einsatz['Start']) ? date('d.m.Y', strtotime($einsatz['Start'])) : null;
            $uhrzeit = isset($einsatz['Start']) ? date('H:i', strtotime($einsatz['Start'])) : null;
            $stichwort = isset($einsatz['Keyword']) ? $einsatz['Keyword'] : null;
            $info = isset($einsatz['Facts']) ? $einsatz['Facts'] : null;

            if ($ort && $datum && $uhrzeit && $stichwort) {
                $deleted = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE ort = %s AND datum = %s AND uhrzeit = %s AND stichwort = %s AND deleted = 1",
                    $ort, $datum, $uhrzeit, $stichwort
                ));

                if ($deleted == 0) {
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_name WHERE ort = %s AND datum = %s AND uhrzeit = %s AND stichwort = %s AND info = %s",
                        $ort, $datum, $uhrzeit, $stichwort, $info
                    ));

                    if ($existing == 0) {
                        $wpdb->insert(
                            $table_name,
                            array(
                                'ort' => $ort,
                                'datum' => $datum,
                                'uhrzeit' => $uhrzeit,
                                'stichwort' => $stichwort,
                                'info' => $info
                            )
                        );
                    }
                }
            }
        }
    }

    $db_einsaetze = $wpdb->get_results("SELECT * FROM $table_name WHERE approved = 1 AND deleted = 0 ORDER BY datum DESC");

    if (empty($db_einsaetze)) {
        return 'Keine Einsätze verfügbar';
    }

    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', array(), '1.13.6');
    wp_enqueue_style('datatables-responsive-css', 'https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css', array(), '2.5.0');
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), '1.13.6', true);
    wp_enqueue_script('datatables-responsive-js', 'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js', array('datatables-js'), '2.5.0', true);

    $output = '<div class="einsatz-table-container">';
    $output .= '<table id="einsatz-table" class="einsatz-table display nowrap" style="width:100%">';
    $output .= '<thead><tr><th>Nr.</th><th>Datum</th><th>Uhrzeit</th><th>Ort</th><th>Stichwort</th><th>Info</th></tr></thead>';
    $output .= '<tbody>';

    $counter = 1;
    foreach ($db_einsaetze as $einsatz) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html($counter) . '</td>';
        $output .= '<td>' . esc_html($einsatz->datum) . '</td>';
        $output .= '<td>' . esc_html($einsatz->uhrzeit) . '</td>';
        $output .= '<td>' . esc_html($einsatz->ort) . '</td>';
        $output .= '<td>' . esc_html($einsatz->stichwort) . '</td>';
        $output .= '<td>' . esc_html($einsatz->info) . '</td>';
        $output .= '</tr>';
        $counter++;
    }

    $output .= '</tbody></table>';
    $output .= '</div>';

    $output .= '<script>
        jQuery(document).ready(function($) {
            $("#einsatz-table").DataTable({
                responsive: true,
                language: {
                    "decimal": ",",
                    "emptyTable": "Keine Daten verfügbar",
                    "info": "Zeige _START_ bis _END_ von _TOTAL_ Einträgen",
                    "infoEmpty": "Zeige 0 bis 0 von 0 Einträgen",
                    "infoFiltered": "(gefiltert von _MAX_ Einträgen insgesamt)",
                    "lengthMenu": "Zeige _MENU_ Einträge",
                    "loadingRecords": "Lade...",
                    "processing": "Verarbeite...",
                    "search": "Suche:",
                    "zeroRecords": "Keine passenden Einträge gefunden",
                    "paginate": {
                        "first": "Erste",
                        "last": "Letzte",
                        "next": "Nächste",
                        "previous": "Vorherige"
                    }
                },
                order: [[1, "desc"]],
                pageLength: 10
            });
        });
    </script>';

    // Zusätzliches CSS für das Frontend
    $output .= '<style>
        .einsatz-table-container {
            width: 100%;
            overflow-x: auto;
            margin: 20px 0;
        }
        #einsatz-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1em;
        }
        #einsatz-table th, #einsatz-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
        }
        #einsatz-table th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        #einsatz-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background-color: #007bff;
            color: #fff;
            border-radius: 5px;
            margin: 2px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #0056b3;
        }
        @media (max-width: 600px) {
            #einsatz-table {
                font-size: 0.9em;
            }
        }
    </style>';

    return $output;
}


// =====================
// Einsätze für Administratoren abrufen und anzeigen
// =====================
function feuer_api_einsaetze_admin_anzeigen() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'feuer_einsaetze';

    $db_einsaetze = $wpdb->get_results("SELECT * FROM $table_name WHERE approved IN (0, 1, 3) AND deleted = 0 ORDER BY FIELD(approved, 3, 1, 0), datum DESC");

    if (empty($db_einsaetze)) {
        return 'Keine Einsätze verfügbar';
    }

    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
    wp_enqueue_style('datatables-responsive-css', 'https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css');
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), null, true);
    wp_enqueue_script('datatables-responsive-js', 'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js', array('datatables-js'), null, true);

    $output = '<table id="admin-einsatz-table" class="wp-list-table widefat fixed striped display nowrap" style="width:100%">';
    $output .= '<thead><tr><th>Nr.</th><th>Datum</th><th>Uhrzeit</th><th>Ort</th><th>Stichwort</th><th>Info</th><th>Veröffentlicht</th><th>Geändert von</th><th>Aktionen</th></tr></thead>';
    $output .= '<tbody>';

    $counter = 1;
    foreach ($db_einsaetze as $einsatz) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html($counter) . '</td>';
        $output .= '<td>' . esc_html($einsatz->datum) . '</td>';
        $output .= '<td>' . esc_html($einsatz->uhrzeit) . '</td>';
        $output .= '<td>' . esc_html($einsatz->ort) . '</td>';
        $output .= '<td>' . esc_html($einsatz->stichwort) . '</td>';
        $output .= '<td>' . esc_html($einsatz->info) . '</td>';
        $output .= '<td>' . ($einsatz->approved == 1 ? '<span style="color: #28a745;">Ja</span>' : ($einsatz->approved == 0 ? '<span style="color: #dc3545;">Nein</span>' : '<span style="color: #007bff;">Ausstehend</span>')) . '</td>';
        if ($einsatz->approved_by) {
            $output .= '<td><a href="' . esc_url(get_edit_user_link($einsatz->approved_by)) . '">' . esc_html($einsatz->approved_by) . '</a></td>';
        } else {
            $output .= '<td></td>';
        }

        // Aktionen
        $output .= '<td>';
        if ($einsatz->approved == 0 || $einsatz->approved == 3) {
            $output .= '<form method="post" style="display:inline;">
                            <input type="hidden" name="einsatz_id" value="' . esc_attr($einsatz->id) . '">
                            <input type="hidden" name="action" value="approve">
                            <input type="submit" class="button button-primary" value="Veröffentlichen">
                        </form>';
        }
        if ($einsatz->approved == 1) {
            $output .= '<form method="post" style="display:inline;">
                            <input type="hidden" name="einsatz_id" value="' . esc_attr($einsatz->id) . '">
                            <input type="hidden" name="action" value="unpublish">
                            <input type="submit" class="button button-secondary" value="Nicht veröffentlichen">
                        </form>';
        }
        if ($einsatz->approved == 3) {
            $output .= '<form method="post" style="display:inline;">
                            <input type="hidden" name="einsatz_id" value="' . esc_attr($einsatz->id) . '">
                            <input type="hidden" name="action" value="reject">
                            <input type="submit" class="button button-secondary" value="Ablehnen">
                        </form>';
        }
        $output .= '</td>';
        $output .= '</tr>';
        $counter++;
    }

    $output .= '</tbody></table>';

    $output .= '<script>
        jQuery(document).ready(function($) {
            $("#admin-einsatz-table").DataTable({
                responsive: true,
                language: {
                    "decimal": ",",
                    "emptyTable": "Keine Daten verfügbar",
                    "info": "Zeige _START_ bis _END_ von _TOTAL_ Einträgen",
                    "infoEmpty": "Zeige 0 bis 0 von 0 Einträgen",
                    "infoFiltered": "(gefiltert von _MAX_ Einträgen insgesamt)",
                    "lengthMenu": "Zeige _MENU_ Einträge",
                    "loadingRecords": "Lade...",
                    "processing": "Verarbeite...",
                    "search": "Suche:",
                    "zeroRecords": "Keine passenden Einträge gefunden",
                    "paginate": {
                        "first": "Erste",
                        "last": "Letzte",
                        "next": "Nächste",
                        "previous": "Vorherige"
                    }
                },
                order: [[1, "desc"]],)
                pageLength: 10,
                columnDefs: [
                    { orderable: false, targets: 8 }
                ]
            });
        });
    </script>';

    // Dropdown für abgelehnte Einsätze
    $output .= '<div id="abgelehnte-einsaetze" style="margin-top: 20px;">';
    $output .= '<button class="button" onclick="toggleAbgelehnte()">Abgelehnte Einsätze anzeigen</button>';
    $output .= '<div id="abgelehnte-einsaetze-content" style="display:none;">';
    $output .= feuer_api_abgelehnte_einsaetze_anzeigen();
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}


// =====================
// Abgelehnte Einsätze abrufen und anzeigen
// =====================

function feuer_api_abgelehnte_einsaetze_anzeigen() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'feuer_einsaetze';

    // Holen Sie sich die abgelehnten Einsätze
    $abgelehnte_einsaetze = $wpdb->get_results("SELECT * FROM $table_name WHERE approved = 0 ORDER BY datum DESC");

    if (empty($abgelehnte_einsaetze)) {
        return '<div class="abgelehnt"><p>Keine abgelehnten Einsätze verfügbar.</p></div>';
    }

    // Tabelle erstellen für abgelehnte Einsätze

    $output = '<div class="abgelehnt"><h3>Nicht veröffentlichte Einsätze</h3>';
    $output = '<table class="wp-list-table widefat fixed striped">';
    $output .= '<tr><th>Datum</th><th>Uhrzeit</th><th>Ort</th><th>Stichwort</th><th>Info</th><th>Genehmigt</th><th>Aktionen</th></tr>';

    foreach ($abgelehnte_einsaetze as $einsatz) {
        $output .= '<br><tr>';
        $output .= '<td>' . esc_html($einsatz->datum) . '</td>';
        $output .= '<td>' . esc_html($einsatz->uhrzeit) . '</td>';
        $output .= '<td>' . esc_html($einsatz->ort) . '</td>';
        $output .= '<td>' . esc_html($einsatz->stichwort) . '</td>';
        $output .= '<td>' . esc_html($einsatz->info) . '</td>';
        $output .= '<td>' . ($einsatz->approved == 1 ? '<span style="color: #28a745;">Ja</span>' : ($einsatz->approved == 0 ? '<span style="color: #dc3545;">Nein</span>' : '<span style="color: #007bff;">Ausstehend</span>')) . '</td>';
        $output .= '<td> <form method="post" style="display:inline-block;">';
        $output .= wp_nonce_field('feuer_api_genehmigen_' . $einsatz->id, 'feuer_api_nonce', true, false);
        $output .= '<input type="hidden" name="einsatz_id" value="' . esc_attr($einsatz->id) . '">';
        $output .= '<input type="submit" name="feuer_api_genehmigen" class="button button-primary" value="Genehmigen">';
        $output .= '</form>';
        $output .= '</td>';
    }

    $output .= '</div>';

    return $output;
}



// =====================
// Genehmigen und Ablehnen von Einsätzen
// =====================
add_action('admin_init', 'feuer_api_handle_actions');

add_action('admin_init', 'feuer_api_handle_actions');

function feuer_api_handle_actions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'feuer_einsaetze';

    if (isset($_POST['action']) && !empty($_POST['einsatz_id'])) {
        $einsatz_id = intval($_POST['einsatz_id']);
        $current_user = wp_get_current_user();

        if ($_POST['action'] === 'approve') {
            $wpdb->update($table_name,
                array(
                    'approved' => 1,
                    'approved_by' => $current_user->user_login
                ),
                array('id' => $einsatz_id)
            );
            add_settings_error('feuer_api_actions', 'genehmigt', 'Einsatz genehmigt und wurde auf die Einsatzseite veröffentlicht.', 'updated');
        } elseif ($_POST['action'] === 'reject') {
            // Ablehnung mit Benutzernamen speichern
            $wpdb->update($table_name,
                array(
                    'approved' => 0,
                    'approved_by' => $current_user->user_login
                ),
                array('id' => $einsatz_id)
            );
            add_settings_error('feuer_api_actions', 'abgelehnt', 'Einsatz abgelehnt und wird nicht auf die Seite veröffentlicht.', 'updated');
        } elseif ($_POST['action'] === 'unpublish') {
            $wpdb->update($table_name,
                array(
                    'approved' => 3,
                    'approved_by' => $current_user->user_login
                ),
                array('id' => $einsatz_id)
            );
            add_settings_error('feuer_api_actions', 'unpublish', 'Veröffentlichung des Einsatzes wurde zurückgezogen und ist nun ausstehend.', 'updated');
        } elseif ($_POST['action'] === 'delete') {
            $wpdb->update($table_name, array('deleted' => 1), array('id' => $einsatz_id));  // Setzt den Einsatz auf "gelöscht"
            add_settings_error('feuer_api_actions', 'deleted', 'Einsatz gelöscht.', 'updated');
        }
    }
}



// =====================
// Einstellungsseite rendern
// =====================
function feuer_api_einstellungen_render() {
    $api_key = get_option('feuer_api_key');
    ?>
    <div class="wrap">
        <h1>Feuer Software API Einstellungen</h1>
        <a href="https://connect.feuersoftware.com/Interface/PublicApi" target="_blank">
            <p>Zum Feuer Software Connect API Dashboard</p>
        </a>
        <form method="post" action="options.php">
            <?php
            settings_fields('feuer_api_einstellungen_gruppe');
            do_settings_sections('feuer-api-einstellungen');
            submit_button();
            ?>

            <?php settings_errors('feuer_api_manuelle_sync'); ?>

        </form>

        <h2>Manuelle Synchronisierung</h2>
        <form method="post">
            <?php wp_nonce_field('feuer_api_manuelle_sync_action'); ?>
            <p><input type="submit" name="feuer_api_manuelle_sync" class="button button-primary" value="Jetzt synchronisieren"></p>
        </form>

        <?php if (!empty($api_key)) :
            $api_test_ergebnis = feuer_api_test_anfrage($api_key);
            if ($api_test_ergebnis) {
                echo '<p style="color: green;"><b>API-Schlüssel ist gültig.</b></p>';
            } else {
                echo '<p style="color: red;"><b>Ungültiger API-Schlüssel. Bitte überprüfe ihn.</b></p>';
            }
        else :
            echo '<p style="color: red;">Kein API-Schlüssel eingegeben.</p>';
        endif; ?>
    </div>
    <?php
}


// =====================
// API-Schlüsseleingabe und Einstellung
// =====================
function feuer_api_einstellungen_init() {
    register_setting('feuer_api_einstellungen_gruppe', 'feuer_api_key');

    add_settings_section(
        'feuer_api_einstellungen_abschnitt',
        'API Einstellungen',
        null,
        'feuer-api-einstellungen'
    );

    add_settings_field(
        'feuer_api_key_feld',
        'API Schlüssel',
        'feuer_api_key_feld_render',
        'feuer-api-einstellungen',
        'feuer_api_einstellungen_abschnitt'
    );
}
add_action('admin_init', 'feuer_api_einstellungen_init');

function feuer_api_key_feld_render() {
    $api_key = get_option('feuer_api_key');
    ?>
    <input type="text" name="feuer_api_key" value="<?php echo esc_attr($api_key); ?>" size="50" />
    <p>Gib deinen API-Schlüssel der Feuer Software API hier ein.</p>
    <?php
}

// =====================
// API-Testanfrage
// =====================
function feuer_api_test_anfrage($api_key) {
    $api_url = 'https://connectapi.feuersoftware.com/interfaces/public/operation';

    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . esc_attr($api_key),
        )
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    return $status_code === 200;
}

// =====================
// Cronjob für Synchronisierung
// =====================
function feuer_api_cronjob_einsaetze() {
    if (!wp_next_scheduled('feuer_api_einsaetze_sync')) {
        wp_schedule_event(time(), 'feuer_api_einsaetze_sync', 'feuer_api_einsaetze_sync');
    }
}
add_action('wp', 'feuer_api_cronjob_einsaetze');


function feuer_api_cron_schedules($schedules) {
    $schedules['feuer_api_einsaetze_sync'] = array(
        'interval' => 300,
        'display' => __('Alle 5 Minuten')
    );
    return $schedules;
}
add_filter('cron_schedules', 'feuer_api_cron_schedules');

function feuer_api_sync_einsaetze() {
    feuer_api_einsaetze_anzeigen();

    update_option('feuer_api_letzte_sync', current_time('mysql'));
}


// =====================
// Manuelle Synchronisierung
// =====================
function feuer_api_manuelle_sync() {
    if (isset($_POST['feuer_api_manuelle_sync']) && check_admin_referer('feuer_api_manuelle_sync_action')) {
        feuer_api_sync_einsaetze();
        add_settings_error('feuer_api_manuelle_sync', 'sync_success', 'Die Einsätze wurden manuell synchronisiert.', 'updated');
    }
}
add_action('admin_init', 'feuer_api_manuelle_sync');

// =====================
// Shortcode für die Anzeige von Einsätzen
// =====================
function feuer_api_einsaetze_shortcode() {
    return feuer_api_einsaetze_anzeigen();
}
add_shortcode('feuer_einsaetze', 'feuer_api_einsaetze_shortcode');
add_action('feuer_api_einsaetze_sync', 'feuer_api_sync_einsaetze');


function feuer_software_made_by_jimmy_footer() {
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/feuersoftware/feuersoftware.php');

    $plugin_version = $plugin_data['Version'];

    echo "\n<!-- Made by Jimmy | Plugin Version: v" . esc_html($plugin_version) . " -->\n";
    echo "\n<!-- Made by Jimmy | Plugin Version: v" . esc_html($plugin_version) . " -->\n";
}
add_action('wp_footer', 'feuer_software_made_by_jimmy_footer');