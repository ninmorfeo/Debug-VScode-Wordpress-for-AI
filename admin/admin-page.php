<?php
// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('debug_vscode_options', []);
$is_enabled = !empty($options['enabled']);
$api_key = $options['api_key'] ?? '';
$is_wp_config = false;
$endpoint_url = home_url('/wp-json/debug-vscode/v1/logs');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php 
    settings_errors(); 
    
    // Mostra messaggi di reset
    if (isset($_GET['reset'])) {
        if ($_GET['reset'] === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Plugin resettato completamente!</strong> Tutti i dati sono stati cancellati e le impostazioni sono state resettate ai valori predefiniti.</p></div>';
        } elseif ($_GET['reset'] === 'error') {
            $error_msg = isset($_GET['error_msg']) ? urldecode($_GET['error_msg']) : 'Errore sconosciuto';
            echo '<div class="notice notice-error is-dismissible"><p><strong>Errore durante il reset:</strong> ' . esc_html($error_msg) . '</p></div>';
        }
    }
    ?>
    
    <div class="debug-vscode-admin">
        <div class="debug-vscode-main">
            <form method="post" action="options.php">
                <?php
                settings_fields('debug_vscode_options');
                do_settings_sections('debug-vscode');
                ?>
                
                <!-- Campo input nascosto per la chiave API, sempre presente -->
                <input type="hidden" id="debug-vscode-api-key-hidden" name="debug_vscode_options[api_key]" value="<?php echo esc_attr($api_key); ?>" />
                
                <div class="debug-vscode-status-card">
                    <h3><?php _e('Stato del Sistema', 'debug-vscode'); ?></h3>
                    <div class="status-indicator <?php echo $is_enabled ? 'enabled' : 'disabled'; ?>">
                        <span class="status-dot"></span>
                        <?php echo $is_enabled ? __('Attivo', 'debug-vscode') : __('Disattivato', 'debug-vscode'); ?>
                    </div>
                    
                    <?php if ($is_enabled): ?>
                        <div class="endpoint-info">
                            <h4><?php _e('Endpoint API', 'debug-vscode'); ?></h4>
                            <code class="endpoint-url click-to-copy" data-copy="<?php echo esc_attr($endpoint_url); ?>"><?php echo esc_html($endpoint_url); ?></code>
                        </div>
                        
                        <div class="api-key-info">
                            <h4><?php _e('Chiave API Attuale', 'debug-vscode'); ?></h4>
                            <code class="api-key click-to-copy" data-copy="<?php echo esc_attr($api_key); ?>"><?php echo esc_html(substr($api_key, 0, 8) . '...' . substr($api_key, -8)); ?></code>
                        </div>
                        
                        
                        
                        <div class="complete-url-info">
                            <h4><?php _e('URL Completo per Accesso Diretto', 'debug-vscode'); ?></h4>
                            <?php $complete_url = $endpoint_url . '?api_key=' . $api_key; ?>
                            <code class="complete-url click-to-copy" data-copy="<?php echo esc_attr($complete_url); ?>"><?php echo esc_html($complete_url); ?></code>
                        </div>
                        
                        <div id="delete-after-read-section">
                            <h4><?php _e('Cancellazione Log', 'debug-vscode'); ?></h4>
                            <label class="delete-after-read-label">
                                <input type="checkbox" id="debug-vscode-delete-after-read" name="debug_vscode_options[delete_after_read]" value="1" <?php checked(!empty($options['delete_after_read'])); ?> />
                                <strong><?php _e('Cancella i log dopo la lettura', 'debug-vscode'); ?></strong>
                            </label>
                            <p class="description"><?php _e('Abilita questa opzione per permettere la cancellazione del file di log tramite endpoint REST.', 'debug-vscode'); ?></p>
                            
                            <?php
                            $delete_url = $endpoint_url . '?cancella=si&api_key=' . $api_key;
                            ?>
                            <div class="delete-url-info <?php echo empty($options['delete_after_read']) ? 'hidden' : ''; ?>">
                                <h4><?php _e('URL per la Cancellazione dei Log', 'debug-vscode'); ?></h4>
                                <code class="delete-url click-to-copy" data-copy="<?php echo esc_attr($delete_url); ?>"><?php echo esc_html($delete_url); ?></code>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <div class="debug-vscode-sidebar">
            <div class="debug-vscode-card">
                <h3><?php _e('Esempi di Utilizzo', 'debug-vscode'); ?></h3>
                <?php if ($is_enabled && !empty($api_key)): ?>
                    <div class="usage-examples">
                        <h4><?php _e('cURL', 'debug-vscode'); ?></h4>
                        <pre><code>curl "<?php echo esc_html($endpoint_url); ?>?api_key=<?php echo esc_html($api_key); ?>"</code></pre>
                        
                        <h4><?php _e('Con Header', 'debug-vscode'); ?></h4>
                        <pre><code>curl -H "X-API-Key: <?php echo esc_html($api_key); ?>" "<?php echo esc_html($endpoint_url); ?>"</code></pre>
                        
                        <h4><?php _e('Parametri Opzionali', 'debug-vscode'); ?></h4>
                        <ul>
                            <li><code>lines=50</code> - <?php _e('Numero di righe da leggere', 'debug-vscode'); ?></li>
                            <li><code>from_bottom=false</code> - <?php _e('Leggi dall\'inizio del file', 'debug-vscode'); ?></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <p><?php _e('Abilita il plugin per vedere gli esempi di utilizzo.', 'debug-vscode'); ?></p>
                <?php endif; ?>
            </div>
            
            
            
            <div class="debug-vscode-card">
                <h3><?php _e('Test Endpoint', 'debug-vscode'); ?></h3>
                <?php if ($is_enabled && !empty($api_key)): ?>
                    <button type="button" class="button button-primary test-endpoint" 
                            data-url="<?php echo esc_attr($endpoint_url); ?>" 
                            data-key="<?php echo esc_attr($api_key); ?>">
                        <?php _e('Testa Endpoint', 'debug-vscode'); ?>
                    </button>
                    <div id="test-result"></div>
                <?php else: ?>
                    <p><?php _e('Abilita il plugin per testare l\'endpoint.', 'debug-vscode'); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="debug-vscode-card">
                <h3 class="reset-section-title"><?php _e('Reset Completo', 'debug-vscode'); ?></h3>
                <p class="reset-section-description"><?php _e('Cancella tutti i dati del plugin e resetta la configurazione.', 'debug-vscode'); ?></p>
                <div class="reset-warning-box">
                    <strong class="reset-warning-title"><?php _e('Attenzione!', 'debug-vscode'); ?></strong>
                    <p class="reset-warning-text"><?php _e('Questa operazione:'); ?></p>
                    <ul class="reset-warning-list">
                        <li><?php _e('Cancellerà la chiave API dal database', 'debug-vscode'); ?></li>
                        <li><?php _e('Rimuoverà la definizione da wp-config.php', 'debug-vscode'); ?></li>
                        <li><?php _e('Resetterà tutte le impostazioni', 'debug-vscode'); ?></li>
                        <li><?php _e('Pulirà i dati di rate limiting', 'debug-vscode'); ?></li>
                    </ul>
                </div>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="reset-form">
                    <input type="hidden" name="action" value="debug_vscode_reset_plugin">
                    <input type="hidden" name="reset_nonce" value="<?php echo wp_create_nonce('debug_vscode_reset_plugin'); ?>">
                    <button type="submit" class="button button-secondary reset-plugin-submit reset-button" 
                            onclick="return confirm('ATTENZIONE: Questa operazione cancellerà TUTTI i dati del plugin.\n\nSei sicuro di voler continuare?') && confirm('Ultima conferma: Tutti i dati saranno persi definitivamente.\n\nConfermi il reset completo?');">
                        <?php _e('Reset Completo Plugin', 'debug-vscode'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function generateNewApiKey() {
    const apiKeyField = document.getElementById('debug-vscode-api-key');
    const isEnabled = document.querySelector('.debug-vscode-status-card .status-indicator.enabled');
    
    if (apiKeyField) {
        const newKey = 'dvsc_' + Math.random().toString(36).substr(2, 16) + '_' + Date.now();
        apiKeyField.value = newKey;
        
        if (isEnabled) {
            alert('<?php _e('Nuova chiave generata! Ricorda di salvare le impostazioni.', 'debug-vscode'); ?>');
        } else {
            alert('<?php _e('Nuova chiave generata! Il plugin è attualmente disattivato, ricorda di attivarlo e salvare le impostazioni.', 'debug-vscode'); ?>');
        }
    }
}


// Gestione checkbox delete_after_read con AJAX
document.addEventListener('DOMContentLoaded', function() {
    const deleteAfterReadCheckbox = document.getElementById('debug-vscode-delete-after-read');
    if (deleteAfterReadCheckbox) {
        deleteAfterReadCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            
            // Effettua la chiamata AJAX
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                method: 'POST',
                data: {
                    action: 'debug_vscode_toggle_delete_after_read',
                    delete_after_read: isChecked ? 1 : 0,
                    nonce: '<?php echo wp_create_nonce('debug_vscode_toggle_delete_after_read'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Aggiorna l'interfaccia utente dinamicamente
                        const deleteUrlInfo = document.querySelector('.delete-url-info');
                        if (deleteUrlInfo) {
                            if (isChecked) {
                                deleteUrlInfo.classList.remove('hidden');
                            } else {
                                deleteUrlInfo.classList.add('hidden');
                            }
                        }
                    } else {
                        alert('Errore durante l\'aggiornamento della checkbox: ' + response.data);
                    }
                },
                error: function() {
                    alert('Errore nella richiesta AJAX');
                }
            });
        });
    }
});
</script>