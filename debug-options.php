<?php
/**
 * Script di debug per verificare le opzioni del plugin Debug VSCode
 * Eseguire questo file temporaneamente per verificare lo stato delle opzioni
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Ottieni le opzioni correnti
$options = get_option('debug_vscode_options', []);

echo '<h2>Debug Opzioni Plugin Debug VSCode</h2>';
echo '<pre>';
print_r($options);
echo '</pre>';

// Verifica se il campo delete_after_read esiste
if (array_key_exists('delete_after_read', $options)) {
    echo '<p style="color: green;">✓ Campo "delete_after_read" presente nelle opzioni</p>';
    echo '<p>Valore: ' . ($options['delete_after_read'] ? 'true' : 'false') . '</p>';
} else {
    echo '<p style="color: red;">✗ Campo "delete_after_read" NON presente nelle opzioni</p>';
    echo '<p>Disattiva e riattiva il plugin per aggiornare le opzioni.</p>';
}