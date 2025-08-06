<?php
/**
 * Uninstall script per Debug VSCode Plugin
 * 
 * Questo file viene eseguito quando il plugin viene disinstallato
 * tramite l'interfaccia di amministrazione di WordPress.
 * 
 * @package DebugVSCode
 * @version 1.1.0
 * @author Paolo Battiloro
 * @link https://eyeart.it
 */

// Previeni accesso diretto
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Pulizia completa del plugin Debug VSCode
 * 
 * Rimuove solo i dati effettivamente utilizzati dal plugin
 */

// Rimuovi le opzioni del plugin (salvate nel database)
delete_option('debug_vscode_options');
delete_option('debug_vscode_last_cleanup');

// Rimuovi tutti i transient relativi al rate limiting
global $wpdb;

// Rimuovi i transient di rate limiting
$wpdb->query(
    "DELETE FROM {$wpdb->options} 
     WHERE option_name LIKE '_transient_debug_vscode_attempts_%' 
     OR option_name LIKE '_transient_timeout_debug_vscode_attempts_%'"
);

// Rimuovi eventuali task cron programmati
wp_clear_scheduled_hook('debug_vscode_cleanup_cron');

// Rimuovi backup del wp-config creato dal plugin (ATTENZIONE: SOLO IL BACKUP!)
$backup_file = ABSPATH . 'wp-config-backup-debug-vscode.php';
if (is_file($backup_file) && basename($backup_file) !== 'wp-config.php') {
    unlink($backup_file);
}

// Rimuovi la definizione DEBUG_VSCODE_API_KEY da wp-config.php
$wp_config_path = ABSPATH . 'wp-config.php';
if (file_exists($wp_config_path)) {
    $wp_config_content = file_get_contents($wp_config_path);
    if ($wp_config_content !== false) {
        // Rimuovi tutte le linee che contengono DEBUG_VSCODE_API_KEY (semplice e sicuro)
        $original_content = $wp_config_content;
        
        $lines = explode("\n", $wp_config_content);
        $clean_lines = [];
        
        foreach ($lines as $line) {
            // Se la linea contiene DEBUG_VSCODE_API_KEY, la saltiamo
            if (strpos($line, 'DEBUG_VSCODE_API_KEY') === false) {
                $clean_lines[] = $line;
            }
        }
        
        $wp_config_content = implode("\n", $clean_lines);
        
        // Scrivi solo se ci sono state modifiche
        if ($wp_config_content !== $original_content) {
            file_put_contents($wp_config_path, $wp_config_content);
        }
    }
}

// Flush delle regole di rewrite per pulire eventuali endpoint REST
flush_rewrite_rules();

// Log della disinstallazione
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[INFO] Debug VSCode Plugin: Disinstallazione completata - rimossi: debug_vscode_options, debug_vscode_last_cleanup, transient rate limiting, cron tasks, backup wp-config, definizioni wp-config');
}