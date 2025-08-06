<?php
/**
 * Plugin Name: Debug VSCode
 * Plugin URI: https://eyeart.it
 * Description: Plugin per esporre i log di WordPress tramite API REST sicura per l'integrazione con VSCode e altri strumenti di sviluppo.
 * Version: 1.1.0
 * Author: Paolo Battiloro
 * Author URI: https://eyeart.it
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: debug-vscode
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti del plugin
define('DEBUG_VSCODE_VERSION', '1.1.0');
define('DEBUG_VSCODE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEBUG_VSCODE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DEBUG_VSCODE_PLUGIN_FILE', __FILE__);

/**
 * Classe principale del plugin Debug VSCode
 */
class DebugVSCode {
    
    /**
     * Istanza singleton
     */
    private static $instance = null;
    
    /**
     * Opzioni del plugin
     */
    private $options;
    
    /**
     * Ottieni istanza singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Costruttore privato per singleton
     */
    private function __construct() {
        $this->options = get_option('debug_vscode_options', []);
        $this->maybeUpdateOptions();
        $this->init();
    }
    
    /**
     * Inizializza il plugin
     */
    private function init() {
        // Hook di attivazione e disattivazione
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Hook di inizializzazione
        add_action('init', [$this, 'loadTextDomain']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        
        // Hook per gestione wp-config.php
        add_action('admin_notices', [$this, 'showAdminNotices']);
        
        // Stili e script admin
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Task di pulizia automatica
        add_action('debug_vscode_cleanup_cron', [$this, 'cleanupRateLimiting']);
        
        
        // AJAX per gestire la checkbox delete_after_read
        add_action('wp_ajax_debug_vscode_toggle_delete_after_read', [$this, 'toggleDeleteAfterReadAjax']);
        
        // Hook per gestire il reset del plugin via form submit
        add_action('admin_post_debug_vscode_reset_plugin', [$this, 'handleResetPlugin']);
    }
    
    /**
     * Attivazione del plugin
     */
    public function activate() {
        
        // Ottieni opzioni esistenti PRIMA
        $existing_options = get_option('debug_vscode_options', []);
        
        // Crea opzioni predefinite
        $default_options = [
            'enabled' => false,
            'api_key' => '', // Verrà generata dopo se necessario
            'max_attempts' => 10,
            'lockout_duration' => 300,
            'log_access_attempts' => true,
            'auto_cleanup' => true,
            'delete_after_read' => false
        ];
        
        // Unisci con i valori predefiniti per i campi mancanti, ma se non esiste api_key la generiamo
        $updated_options = array_merge($default_options, $existing_options);
        
        // Genera API key solo se è la prima attivazione (nessuna opzione esistente)
        if (empty($existing_options) && empty($updated_options['api_key'])) {
            $updated_options['api_key'] = $this->generateSecureApiKey();
        } else {
        }
        
        // Aggiorna le opzioni
        update_option('debug_vscode_options', $updated_options);
        
        // Programma task di pulizia se abilitato
        if ($updated_options['auto_cleanup']) {
            $this->scheduleCronJob();
        }
        
        // Flush rewrite rules per REST API
        flush_rewrite_rules();
    }
    
    /**
     * Disattivazione del plugin
     */
    public function deactivate() {
        // Rimuovi task cron
        wp_clear_scheduled_hook('debug_vscode_cleanup_cron');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Carica traduzioni
     */
    public function loadTextDomain() {
        load_plugin_textdomain('debug-vscode', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Aggiunge menu amministrazione
     */
    public function addAdminMenu() {
        add_management_page(
            __('Debug VSCode', 'debug-vscode'),
            __('Debug VSCode', 'debug-vscode'),
            'manage_options',
            'debug-vscode',
            [$this, 'adminPage']
        );
    }
    
    /**
     * Registra impostazioni
     */
    public function registerSettings() {
        register_setting('debug_vscode_options', 'debug_vscode_options', [
            'sanitize_callback' => [$this, 'sanitizeOptions']
        ]);
        
        // Sezione principale
        add_settings_section(
            'debug_vscode_main',
            __('Configurazione API Log', 'debug-vscode'),
            [$this, 'sectionCallback'],
            'debug-vscode'
        );
        
        // Campo abilitazione
        add_settings_field(
            'enabled',
            __('Abilita API Log', 'debug-vscode'),
            [$this, 'enabledFieldCallback'],
            'debug-vscode',
            'debug_vscode_main'
        );
        
        // Campo chiave API
        add_settings_field(
            'api_key',
            __('Chiave API', 'debug-vscode'),
            [$this, 'apiKeyFieldCallback'],
            'debug-vscode',
            'debug_vscode_main'
        );
        
        // Campo nome file log
        add_settings_field(
            'log_filename',
            __('Nome File Log', 'debug-vscode'),
            [$this, 'logFilenameFieldCallback'],
            'debug-vscode',
            'debug_vscode_main'
        );
        
        
        // Sezione sicurezza
        add_settings_section(
            'debug_vscode_security',
            __('Impostazioni Sicurezza', 'debug-vscode'),
            [$this, 'securitySectionCallback'],
            'debug-vscode'
        );
        
        // Campo tentativi massimi
        add_settings_field(
            'max_attempts',
            __('Tentativi Massimi', 'debug-vscode'),
            [$this, 'maxAttemptsFieldCallback'],
            'debug-vscode',
            'debug_vscode_security'
        );
        
        // Campo durata blocco
        add_settings_field(
            'lockout_duration',
            __('Durata Blocco (secondi)', 'debug-vscode'),
            [$this, 'lockoutDurationFieldCallback'],
            'debug-vscode',
            'debug_vscode_security'
        );
    }
    
    /**
     * Registra route REST API
     */
    public function registerRestRoutes() {
        if (!$this->isEnabled()) {
            return;
        }
        
        register_rest_route('debug-vscode/v1', '/logs', [
            'methods' => 'GET',
            'callback' => [$this, 'getLogsCallback'],
            'permission_callback' => [$this, 'checkApiPermissions']
        ]);
        
        register_rest_route('debug-vscode/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'getStatusCallback'],
            'permission_callback' => [$this, 'checkApiPermissions']
        ]);
        
        // Registra endpoint di cancellazione
        register_rest_route('debug-vscode/v1', '/delete-log', [
            'methods' => 'DELETE',
            'callback' => [$this, 'deleteLogCallback'],
            'permission_callback' => [$this, 'checkApiPermissions']
        ]);
    }
    
    /**
     * Genera chiave API sicura
     */
    private function generateSecureApiKey() {
        return 'dvsc_' . bin2hex(random_bytes(16)) . '_' . time();
    }
    
    /**
     * Verifica se il plugin è abilitato
     */
    private function isEnabled() {
        return !empty($this->options['enabled']);
    }
    
    /**
     * Ottieni chiave API
     */
    private function getApiKey() {
        // Leggi sempre la chiave dal database
        return $this->options['api_key'] ?? '';
    }
    
    /**
     * Programma task cron di pulizia
     */
    private function scheduleCronJob() {
        if (!wp_next_scheduled('debug_vscode_cleanup_cron')) {
            wp_schedule_event(time(), 'daily', 'debug_vscode_cleanup_cron');
        }
    }
    
    /**
     * Aggiorna le opzioni se mancano nuovi campi
     */
    private function maybeUpdateOptions() {
        $default_options = [
            'enabled' => false,
            'api_key' => '',
            'max_attempts' => 10,
            'lockout_duration' => 300,
            'log_access_attempts' => true,
            'auto_cleanup' => true,
            'delete_after_read' => false
        ];
        
        $needs_update = false;
        $current_options = $this->options;
        
        // Verifica se mancano nuovi campi
        foreach ($default_options as $key => $default_value) {
            if (!array_key_exists($key, $current_options)) {
                $current_options[$key] = $default_value;
                $needs_update = true;
            }
        }
        
        // Aggiorna le opzioni se necessario
        if ($needs_update) {
            update_option('debug_vscode_options', $current_options);
            $this->options = $current_options;
        }
    }
    
    /**
     * Callback per sezione principale
     */
    public function sectionCallback() {
        echo '<p>' . __('Configura l\'accesso sicuro ai log di WordPress tramite API REST.', 'debug-vscode') . '</p>';
    }
    
    /**
     * Callback per sezione sicurezza
     */
    public function securitySectionCallback() {
        echo '<p>' . __('Impostazioni per la protezione contro attacchi brute force.', 'debug-vscode') . '</p>';
    }
    
    /**
     * Callback campo abilitazione
     */
    public function enabledFieldCallback() {
        $enabled = $this->options['enabled'] ?? false;
        echo '<input type="checkbox" id="debug-vscode-enabled" name="debug_vscode_options[enabled]" value="1" ' . checked(1, $enabled, false) . ' />';
        echo '<p class="description">' . __('Abilita l\'endpoint API per la lettura dei log.', 'debug-vscode') . '</p>';
    }
    
    /**
     * Callback campo chiave API
     */
    public function apiKeyFieldCallback() {
        $api_key = $this->options['api_key'] ?? '';
        echo '<div style="display: flex; align-items: flex-end; gap: 10px;">';
        echo '<div style="flex: 1;">';
        echo '<input type="text" id="debug-vscode-api-key" name="debug_vscode_options[api_key]" value="' . esc_attr($api_key) . '" class="regular-text" readonly />';
        echo '</div>';
        echo '<button type="button" id="generate-new-api-key-btn" class="button button-primary" onclick="generateNewApiKey()">';
        echo __('Genera Nuova Chiave API', 'debug-vscode');
        echo '</button>';
        echo '</div>';
        echo '<p class="description">' . __('Chiave per autenticare le richieste API.', 'debug-vscode') . '</p>';
    }
    
    /**
     * Callback campo tentativi massimi
     */
    public function maxAttemptsFieldCallback() {
        $max_attempts = $this->options['max_attempts'] ?? 10;
        echo '<input type="number" id="debug-vscode-max-attempts" name="debug_vscode_options[max_attempts]" value="' . esc_attr($max_attempts) . '" min="1" max="100" />';
        echo '<p class="description">' . __('Numero massimo di tentativi falliti prima del blocco IP.', 'debug-vscode') . '</p>';
    }
    
    /**
     * Callback campo durata blocco
     */
    public function lockoutDurationFieldCallback() {
        $lockout_duration = $this->options['lockout_duration'] ?? 300;
        echo '<input type="number" id="debug-vscode-lockout-duration" name="debug_vscode_options[lockout_duration]" value="' . esc_attr($lockout_duration) . '" min="60" max="3600" />';
        echo '<p class="description">' . __('Durata del blocco in secondi (300 = 5 minuti).', 'debug-vscode') . '</p>';
    }
    
    /**
     * Callback campo nome file log
     */
    public function logFilenameFieldCallback() {
        $log_filename = $this->options['log_filename'] ?? 'debug.log';
        echo '<input type="text" id="debug-vscode-log-filename" name="debug_vscode_options[log_filename]" value="' . esc_attr($log_filename) . '" class="regular-text" />';
        echo '<p class="description">' . __('Nome del file di log da leggere (default: debug.log). Deve terminare con .log', 'debug-vscode') . '</p>';
    }
    
    
    /**
     * Sanitizza opzioni
     */
    public function sanitizeOptions($input) {
        $sanitized = [];
        
        $sanitized['enabled'] = !empty($input['enabled']);
        $sanitized['api_key'] = sanitize_text_field($input['api_key'] ?? '');
        $sanitized['log_filename'] = sanitize_file_name($input['log_filename'] ?? 'debug.log');
        $sanitized['max_attempts'] = absint($input['max_attempts'] ?? 10);
        $sanitized['lockout_duration'] = absint($input['lockout_duration'] ?? 300);
        $sanitized['delete_after_read'] = !empty($input['delete_after_read']);
        
        // La chiave API può essere vuota - non rigenerare automaticamente
        
        // Valida nome file log
        if (empty($sanitized['log_filename']) || !preg_match('/\.log$/', $sanitized['log_filename'])) {
            $sanitized['log_filename'] = 'debug.log';
        }
        
        // Aggiorna automaticamente wp-config.php al salvataggio
        $this->updateWpConfigAuto($sanitized['api_key']);
        
        // Aggiorna opzioni
        $this->options = $sanitized;
        
        return $sanitized;
    }
    
    /**
     * Aggiunge la chiave API al wp-config.php in modo sicuro
     */
    private function updateWpConfigAuto($api_key) {
        // Se la chiave API è vuota, non fare nulla (utile durante il reset)
        if (empty($api_key)) {
            return true;
        }
        
        $wp_config_path = ABSPATH . 'wp-config.php';
        
        // Verifica che il file esista
        if (!file_exists($wp_config_path)) {
            add_settings_error(
                'debug_vscode_options',
                'wp_config_not_found',
                __('File wp-config.php non trovato.', 'debug-vscode'),
                'error'
            );
            return false;
        }
        
        // Leggi il contenuto corrente
        $wp_config_content = file_get_contents($wp_config_path);
        if ($wp_config_content === false) {
            add_settings_error(
                'debug_vscode_options',
                'wp_config_read_error',
                __('Impossibile leggere wp-config.php.', 'debug-vscode'),
                'error'
            );
            return false;
        }
        
        // Crea backup semplice (sempre stesso nome, sovrascrive)
        $backup_path = ABSPATH . 'wp-config-backup-debug-vscode.php';
        copy($wp_config_path, $backup_path);
        
        // Rimuovi eventuali definizioni esistenti della chiave API (semplice ricerca stringa)
        // Cerca e rimuovi tutte le linee che contengono DEBUG_VSCODE_API_KEY
        $lines = explode("\n", $wp_config_content);
        $clean_lines = [];
        
        foreach ($lines as $line) {
            // Se la linea contiene DEBUG_VSCODE_API_KEY, la saltiamo
            if (strpos($line, 'DEBUG_VSCODE_API_KEY') === false) {
                $clean_lines[] = $line;
            }
        }
        
        $wp_config_content = implode("\n", $clean_lines);
        
        // Trova dove inserire la nuova definizione (prima di "That's all, stop editing")
        $insert_marker = "/* That's all, stop editing!";
        $insert_position = strpos($wp_config_content, $insert_marker);
        
        if ($insert_position === false) {
            // Se non trova il marker, inserisci prima della chiusura PHP o alla fine
            $insert_position = strrpos($wp_config_content, '?>');
            if ($insert_position === false) {
                $insert_position = strlen($wp_config_content);
            }
        }
        
        // Prepara la nuova definizione
        $new_line = "define('DEBUG_VSCODE_API_KEY', '" . addslashes($api_key) . "');\n";
        
        // Inserisci la nuova definizione
        $new_content = substr_replace($wp_config_content, $new_line, $insert_position, 0);
        
        // Scrivi il file aggiornato
        $result = file_put_contents($wp_config_path, $new_content);
        
        if ($result === false) {
            add_settings_error(
                'debug_vscode_options',
                'wp_config_write_error',
                __('Impossibile scrivere wp-config.php.', 'debug-vscode'),
                'error'
            );
            return false;
        }
        
        add_settings_error(
            'debug_vscode_options',
            'wp_config_updated',
            __('Chiave API aggiunta a wp-config.php con successo.', 'debug-vscode'),
            'success'
        );
        
        return true;
    }
    
    
    /**
     * Pagina amministrazione
     */
    public function adminPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'debug-vscode'));
        }
        
        include DEBUG_VSCODE_PLUGIN_DIR . 'admin/admin-page.php';
    }
    
    /**
     * Carica asset amministrazione
     */
    public function enqueueAdminAssets($hook) {
        if ($hook !== 'tools_page_debug-vscode') {
            return;
        }
        
        wp_enqueue_script(
            'debug-vscode-admin',
            DEBUG_VSCODE_PLUGIN_URL . 'admin/admin.js',
            ['jquery'],
            DEBUG_VSCODE_VERSION,
            true
        );
        
        wp_enqueue_style(
            'debug-vscode-admin',
            DEBUG_VSCODE_PLUGIN_URL . 'admin/admin.css',
            [],
            DEBUG_VSCODE_VERSION
        );
        
        wp_localize_script('debug-vscode-admin', 'debugVSCodeAdmin', [
            'nonce' => wp_create_nonce('debug_vscode_admin'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'strings' => [
                'keyGenerated' => __('Nuova chiave generata!', 'debug-vscode')
            ]
        ]);
    }
    
    /**
     * Mostra notice amministrative
     */
    public function showAdminNotices() {
        if (!$this->isEnabled()) {
            return;
        }
        
        $api_key = $this->getApiKey();
        if (empty($api_key)) {
            echo '<div class="notice notice-error"><p>';
            echo __('Debug VSCode: Chiave API non configurata. Vai alle impostazioni per configurarla.', 'debug-vscode');
            echo '</p></div>';
        }
    }
    
    /**
     * Verifica permessi API
     */
    public function checkApiPermissions($request) {
        // Cache statica per evitare controlli ripetuti della configurazione
        static $config_cache = null;
        
        // Inizializza cache configurazione al primo accesso
        if ($config_cache === null) {
            $api_key = $this->getApiKey();
            $config_cache = [
                'api_key_valid' => !empty($api_key) && is_string($api_key),
                'rate_limit_enabled' => true,
                'max_attempts' => $this->options['max_attempts'] ?? 10,
                'lockout_duration' => $this->options['lockout_duration'] ?? 300
            ];
            
            // Log configurazione se API key non è configurata correttamente
            if (!$config_cache['api_key_valid']) {
                error_log('[SECURITY] Debug VSCode API key non è definita correttamente - accesso ai log disabilitato');
            }
        }
        
        // Verifica configurazione API key
        if (!$config_cache['api_key_valid']) {
            return false;
        }
        
        // Ottieni informazioni client per rate limiting e logging
        $client_info = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => current_time('mysql'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        // Rate limiting per IP
        if ($config_cache['rate_limit_enabled']) {
            $rate_limit_result = $this->checkRateLimit($client_info['ip'], $config_cache);
            if (is_wp_error($rate_limit_result)) {
                // Log tentativo bloccato da rate limiting
                error_log(sprintf(
                    '[SECURITY] Rate limit superato per accesso log API - IP: %s, User-Agent: %s, URI: %s',
                    $client_info['ip'],
                    $client_info['user_agent'],
                    $client_info['request_uri']
                ));
                return $rate_limit_result;
            }
        }
        
        // Estrazione e validazione API key con fallback multipli
        $api_key = $this->extractApiKey($request);
        
        // Validazione robusta dell'API key
        $validation_result = $this->validateApiKey($api_key);
        if ($validation_result !== true) {
            // Incrementa contatore fallimenti per rate limiting
            if ($config_cache['rate_limit_enabled']) {
                $this->incrementFailedAttempts($client_info['ip'], $config_cache);
            }
            
            // Log tentativo con chiave non valida
            error_log(sprintf(
                '[SECURITY] Tentativo accesso log API con chiave non valida - IP: %s, Errore: %s, User-Agent: %s',
                $client_info['ip'],
                $validation_result,
                $client_info['user_agent']
            ));
            
            return false;
        }
        
        // Confronto sicuro con protezione timing attack
        $is_authorized = hash_equals($this->getApiKey(), $api_key);
        
        if ($is_authorized) {
            // Reset contatore tentativi falliti per IP autorizzato
            if ($config_cache['rate_limit_enabled']) {
                $this->resetFailedAttempts($client_info['ip']);
            }
            
            return true;
        } else {
            // Incrementa contatore per chiave errata
            if ($config_cache['rate_limit_enabled']) {
                $this->incrementFailedAttempts($client_info['ip'], $config_cache);
            }
            
            // Log tentativo con chiave errata
            error_log(sprintf(
                '[SECURITY] Tentativo accesso log API con chiave errata - IP: %s, User-Agent: %s',
                $client_info['ip'],
                $client_info['user_agent']
            ));
            
            return false;
        }
    }
    
    /**
     * Estrae l'API key dalla richiesta con fallback multipli
     */
    private function extractApiKey($request) {
        // Priorità: Header X-API-Key > Header Authorization Bearer > Parametro GET/POST
        $api_key = $request->get_header('X-API-Key');
        
        if (empty($api_key)) {
            // Prova header Authorization con Bearer token
            $auth_header = $request->get_header('Authorization');
            if (!empty($auth_header) && preg_match('/^Bearer\s+(.+)$/i', $auth_header, $matches)) {
                $api_key = $matches[1];
            }
        }
        
        if (empty($api_key)) {
            // Fallback su parametro della richiesta
            $api_key = $request->get_param('api_key');
        }
        
        return $api_key;
    }
    
    /**
     * Valida l'API key estratta
     */
    private function validateApiKey($api_key) {
        if ($api_key === null) {
            return 'API key mancante';
        }
        
        if (!is_string($api_key)) {
            return 'API key deve essere una stringa';
        }
        
        if (empty(trim($api_key))) {
            return 'API key vuota';
        }
        
        if (strlen($api_key) < 8) {
            return 'API key troppo corta';
        }
        
        if (strlen($api_key) > 255) {
            return 'API key troppo lunga';
        }
        
        // Verifica caratteri validi (alfanumerici + alcuni simboli comuni)
        if (!preg_match('/^[a-zA-Z0-9\-_\.]+$/', $api_key)) {
            return 'API key contiene caratteri non validi';
        }
        
        return true;
    }
    
    /**
     * Verifica il rate limiting per un IP specifico
     */
    private function checkRateLimit($ip, $config) {
        $transient_key = 'debug_vscode_attempts_' . md5($ip);
        $attempts = get_transient($transient_key) ?: 0;
        
        if ($attempts >= $config['max_attempts']) {
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    'Troppi tentativi di accesso. Riprova tra %d minuti.',
                    ceil($config['lockout_duration'] / 60)
                ),
                [
                    'status' => 429,
                    'retry_after' => $config['lockout_duration']
                ]
            );
        }
        
        return true;
    }
    
    /**
     * Incrementa il contatore dei tentativi falliti per un IP
     */
    private function incrementFailedAttempts($ip, $config) {
        $transient_key = 'debug_vscode_attempts_' . md5($ip);
        $attempts = get_transient($transient_key) ?: 0;
        set_transient($transient_key, $attempts + 1, $config['lockout_duration']);
    }
    
    /**
     * Reset del contatore tentativi falliti per un IP autorizzato
     */
    private function resetFailedAttempts($ip) {
        $transient_key = 'debug_vscode_attempts_' . md5($ip);
        delete_transient($transient_key);
    }
    
    /**
     * Callback per endpoint logs
     */
    public function getLogsCallback($request) {
        $log_filename = $this->options['log_filename'] ?? 'debug.log';
        $log_file = WP_CONTENT_DIR . '/' . $log_filename;
        
        if (!file_exists($log_file)) {
            return new WP_Error('no_log_file', 'File di log non trovato', array('status' => 404));
        }
        
        // Parametri opzionali
        $lines = intval($request->get_param('lines')) ?: 100; // Default 100 righe
        $from_bottom = $request->get_param('from_bottom') !== 'false'; // Default true
        $delete_param = $request->get_param('cancella') ?? 'no';
        
        try {
            if ($from_bottom) {
                // Leggi le ultime N righe
                $logs = $this->tailFile($log_file, $lines);
            } else {
                // Leggi le prime N righe
                $logs = $this->headFile($log_file, $lines);
            }
            
            $response = array(
                'success' => true,
                'logs' => $logs,
                'file_size' => filesize($log_file),
                'last_modified' => date('Y-m-d H:i:s', filemtime($log_file)),
                'lines_returned' => substr_count($logs, "\n"),
                'plugin_version' => DEBUG_VSCODE_VERSION
            );
            
            // Gestisci cancellazione se richiesto e abilitato
            $deleted = false;
            if ($delete_param === 'si' && !empty($this->options['delete_after_read'])) {
                if (is_writable($log_file)) {
                    $deleted = unlink($log_file);
                    if (!$deleted) {
                        // Se non riesce a cancellare, prova a svuotare il file
                        $deleted = file_put_contents($log_file, '') !== false;
                    }
                    $response['deleted'] = $deleted;
                    if ($deleted) {
                        $response['message'] = 'File di log cancellato con successo';
                    } else {
                        $response['message'] = 'Impossibile cancellare il file di log';
                    }
                }
            }
            
            return $response;
            
        } catch (Exception $e) {
            return new WP_Error('read_error', 'Errore nella lettura del file: ' . $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Callback per endpoint status
     */
    public function getStatusCallback($request) {
        return [
            'success' => true,
            'status' => 'active',
            'plugin_version' => DEBUG_VSCODE_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'debug_enabled' => defined('WP_DEBUG') && WP_DEBUG,
            'debug_log_enabled' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Callback per endpoint delete-log
     */
    public function deleteLogCallback($request) {
        // Verifica se la funzione di cancellazione è abilitata
        if (empty($this->options['delete_after_read'])) {
            return new WP_Error('delete_disabled', 'La funzione di cancellazione è disabilitata', array('status' => 403));
        }
        
        $log_filename = $this->options['log_filename'] ?? 'debug.log';
        $log_file = WP_CONTENT_DIR . '/' . $log_filename;
        
        if (!file_exists($log_file)) {
            return new WP_Error('no_log_file', 'File di log non trovato', array('status' => 404));
        }
        
        // Verifica se il file è scrivibile
        if (!is_writable($log_file)) {
            return new WP_Error('file_not_writable', 'Il file di log non è scrivibile', array('status' => 403));
        }
        
        try {
            // Cancella il file
            $deleted = unlink($log_file);
            
            if (!$deleted) {
                // Se non riesce a cancellare, prova a svuotare il file
                $deleted = file_put_contents($log_file, '') !== false;
            }
            
            if ($deleted) {
                return array(
                    'success' => true,
                    'message' => 'File di log cancellato con successo'
                );
            } else {
                return new WP_Error('delete_failed', 'Impossibile cancellare il file di log', array('status' => 500));
            }
        } catch (Exception $e) {
            return new WP_Error('delete_error', 'Errore durante la cancellazione del file: ' . $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Funzione per leggere le ultime righe del file
     */
    private function tailFile($file, $lines = 100) {
        $handle = fopen($file, "r");
        $buffer = array();
        
        if (!$handle) {
            throw new Exception("Impossibile aprire il file di log");
        }
        
        while (($line = fgets($handle)) !== false) {
            array_push($buffer, $line);
            if (count($buffer) > $lines) {
                array_shift($buffer);
            }
        }
        fclose($handle);
        
        return implode('', $buffer);
    }
    
    /**
     * Funzione per leggere le prime righe del file
     */
    private function headFile($file, $lines = 100) {
        $handle = fopen($file, "r");
        $buffer = array();
        $count = 0;
        
        if (!$handle) {
            throw new Exception("Impossibile aprire il file di log");
        }
        
        while (($line = fgets($handle)) !== false && $count < $lines) {
            array_push($buffer, $line);
            $count++;
        }
        fclose($handle);
        
        return implode('', $buffer);
    }
    
    /**
     * Pulizia automatica dei dati di rate limiting scaduti
     */
    public function cleanupRateLimiting() {
        global $wpdb;
        
        // Pulisce i transient scaduti relativi al rate limiting
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                 AND option_name LIKE %s",
                '_transient_timeout_debug_vscode_attempts_%',
                '%' . $wpdb->esc_like('debug_vscode_attempts_') . '%'
            )
        );
        
        if ($deleted > 0) {
            error_log("[INFO] Debug VSCode: Pulizia rate limiting completata, rimossi {$deleted} record scaduti");
        }
        
        // Aggiorna timestamp ultima pulizia
        update_option('debug_vscode_last_cleanup', current_time('mysql'));
        
        return $deleted;
    }
    
    
    
    /**
     * AJAX callback per gestire la checkbox delete_after_read
     */
    public function toggleDeleteAfterReadAjax() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'debug_vscode_toggle_delete_after_read')) {
            wp_die('Nonce verification failed');
        }
        
        // Verifica permessi
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Ottieni lo stato della checkbox
        $delete_after_read = !empty($_POST['delete_after_read']);
        
        // Aggiorna l'opzione nel database
        $options = get_option('debug_vscode_options', []);
        $options['delete_after_read'] = $delete_after_read;
        update_option('debug_vscode_options', $options);
        
        // Prepara l'URL di cancellazione se la checkbox è attivata
        $delete_url = '';
        if ($delete_after_read) {
            $api_key = $this->getApiKey();
            $endpoint_url = home_url('/wp-json/debug-vscode/v1/logs');
            $delete_url = $endpoint_url . '?cancella=si&api_key=' . $api_key;
        }
        
        // Restituisci la risposta
        wp_send_json_success([
            'delete_after_read' => $delete_after_read,
            'delete_url' => $delete_url,
            'message' => $delete_after_read ? 'Checkbox abilitata' : 'Checkbox disabilitata'
        ]);
    }
    
    /**
     * Gestisce il reset del plugin tramite form submit
     */
    public function handleResetPlugin() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['reset_nonce'] ?? '', 'debug_vscode_reset_plugin')) {
            wp_die('Nonce verification failed');
        }
        
        // Verifica permessi
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi per eseguire questa operazione');
        }
        
        try {
            // 1. Pulisci wp-config.php dalla definizione DEBUG_VSCODE_API_KEY
            $result = $this->removeFromWpConfig();
            
            // 2. Cancella tutte le opzioni dal database
            delete_option('debug_vscode_options');
            delete_option('debug_vscode_last_cleanup');
            
            // 3. Rimuovi tutti i transient di rate limiting
            global $wpdb;
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_debug_vscode_attempts_%' 
                 OR option_name LIKE '_transient_timeout_debug_vscode_attempts_%'"
            );
            
            // 4. Rimuovi task cron
            wp_clear_scheduled_hook('debug_vscode_cleanup_cron');
            
            // 5. Ricrea opzioni default (SENZA api_key per evitare riscrittura wp-config)
            $default_options = [
                'enabled' => false,
                'api_key' => '', // VUOTA - così non riscrive il wp-config
                'max_attempts' => 10,
                'lockout_duration' => 300,
                'log_access_attempts' => true,
                'auto_cleanup' => true,
                'delete_after_read' => false,
                'log_filename' => 'debug.log'
            ];
            update_option('debug_vscode_options', $default_options);
            
            // Redirect alla stessa pagina con messaggio di successo
            $redirect_url = add_query_arg([
                'page' => 'debug-vscode',
                'reset' => 'success'
            ], admin_url('tools.php'));
            
            wp_redirect($redirect_url);
            exit;
            
        } catch (Exception $e) {
            // Redirect con messaggio di errore
            $redirect_url = add_query_arg([
                'page' => 'debug-vscode',
                'reset' => 'error',
                'error_msg' => urlencode($e->getMessage())
            ], admin_url('tools.php'));
            
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Rimuove la definizione DEBUG_VSCODE_API_KEY da wp-config.php
     */
    private function removeFromWpConfig() {
        $wp_config_path = ABSPATH . 'wp-config.php';
        
        if (!file_exists($wp_config_path)) {
            return false;
        }
        
        $wp_config_content = file_get_contents($wp_config_path);
        if ($wp_config_content === false) {
            throw new Exception('Impossibile leggere wp-config.php');
        }
        
        
        // Crea backup prima della modifica (sempre stesso nome)
        $backup_path = ABSPATH . 'wp-config-backup-debug-vscode.php';
        copy($wp_config_path, $backup_path);
        
        // Rimuovi tutte le linee che contengono DEBUG_VSCODE_API_KEY (semplice e sicuro)
        $lines = explode("\n", $wp_config_content);
        $clean_lines = [];
        $removed_lines = 0;
        
        foreach ($lines as $line) {
            // Se la linea contiene DEBUG_VSCODE_API_KEY, la saltiamo
            if (strpos($line, 'DEBUG_VSCODE_API_KEY') !== false) {
                $removed_lines++;
            } else {
                $clean_lines[] = $line;
            }
        }
        
        
        if ($removed_lines > 0) {
            $wp_config_content = implode("\n", $clean_lines);
            
            // Scrivi il file pulito
            $result = file_put_contents($wp_config_path, $wp_config_content);
            
            if ($result === false) {
                throw new Exception('Impossibile scrivere wp-config.php');
            }
            
            return true;
        } else {
            return true; // Nessuna modifica necessaria
        }
    }
    
}

// Inizializza il plugin
DebugVSCode::getInstance();