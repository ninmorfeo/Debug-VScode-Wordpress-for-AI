# Debug VSCode - WordPress Plugin

## ✅ Versione Italiana

Accedi ai log di WordPress tramite API REST sicura. Perfetto per debugging con VSCode e strumenti di sviluppo esterni.

![WordPress Plugin Version](https://img.shields.io/badge/Plugin%20Version-1.1.0-blue.svg)
![WordPress Plugin Required PHP Version](https://img.shields.io/badge/Requires%20PHP-7.4%2B-purple.svg)
![WordPress Tested Up To](https://img.shields.io/badge/Tested%20up%20to-6.4-green.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg)


## Descrizione

**Debug VSCode** trasforma WordPress in un endpoint API per i tuoi log di debug, rendendo lo sviluppo più efficiente e professionale.

### 🚀 Caratteristiche Principali

* **3 Endpoint REST API Sicuri**
  - `GET /logs` - Legge i file di log con opzioni avanzate
  - `GET /status` - Informazioni sistema e configurazione  
  - `DELETE /delete-log` - Cancellazione controllata dei log

* **Autenticazione Multi-Livello**
  - Chiave API generata automaticamente (`dvsc_[random]_[timestamp]`)
  - Supporto header `X-API-Key` e `Authorization Bearer`
  - Integrazione automatica con wp-config.php per massima sicurezza

* **Protezione Anti-Brute Force**
  - Rate limiting configurabile per IP
  - Blocchi temporanei automatici (default: 10 tentativi, 5 minuti)
  - Pulizia automatica dei dati scaduti via cron job

* **Lettura Log Personalizzabile**
  - File log configurabile (debug.log, error.log, custom.log)
  - Controllo righe da leggere (`lines` parameter)
  - Lettura dall'inizio o fine file (`from_bottom` parameter)
  - Opzione cancellazione sicura dopo lettura

* **Interfaccia Admin Professionale**
  - Pannello intuitivo in **Strumenti → Debug VSCode**
  - Test endpoint integrato con response preview
  - Copia URL e chiavi API con un click
  - Gestione real-time delle impostazioni via AJAX

### 💡 Casi d'Uso Ideali

✅ **Sviluppo con VSCode** - Estensioni che leggono log WordPress  
✅ **Monitoraggio Remoto** - Sistemi di allerta e notifiche  
✅ **Debug Avanzato** - Analisi log in tempo reale  
✅ **CI/CD Pipeline** - Automazione test e deploy  
✅ **Strumenti DevOps** - Integrazione con dashboard personalizzate

## Installazione

1. **Carica** il plugin in `/wp-content/plugins/debug-vscode/`
2. **Attiva** tramite WordPress Admin → Plugin
3. **Configura** in **Strumenti → Debug VSCode**
4. **Abilita** l'API e salva per generare la chiave automaticamente

Il plugin aggiornerà automaticamente `wp-config.php` con:
```php
define('DEBUG_VSCODE_API_KEY', 'your_generated_key');
```

## Esempi di Utilizzo

Una volta configurato correttamente il plugin, potrai accedere facilmente ai log di WordPress attraverso l'endpoint REST API dedicato. Di seguito alcuni esempi pratici per iniziare immediatamente:

**� Accesso Standard ai Log**
```
https://TUO-DOMINIO/wp-json/debug-vscode/v1/logs?api_key=Value
```
Questo endpoint ti permette di leggere i log di debug in formato JSON, ideale per l'integrazione con strumenti di sviluppo e monitoraggio.

**🗑️ Lettura con Cancellazione Automatica**
```
https://TUO-DOMINIO/wp-json/debug-vscode/v1/logs?cancella=si&api_key=Value
```
Utilizza questo endpoint quando desideri cancellare automaticamente i log dopo ogni lettura, mantenendo il sistema pulito e ottimizzato per le prestazioni.

## Sicurezza

### 🛡️ Funzionalità di Sicurezza

* **Autenticazione Obbligatoria** - Nessun accesso anonimo
* **Rate Limiting Intelligente** - Protezione automatica IP-based  
* **Validazione Input Robusta** - Controllo lunghezza e formato chiavi
* **Logging Accessi** - Tracciamento tentativi non autorizzati
* **Gestione Errori Sicura** - Nessuna information disclosure
* **Pulizia Automatica** - Rimozione dati temporanei scaduti

### ⚙️ Configurazione Rate Limiting

- **Tentativi Massimi**: 1-100 (default: 10)
- **Durata Blocco**: 60-3600 secondi (default: 300 = 5 min)
- **Reset Automatico**: Su autenticazione corretta
- **Pulizia Cron**: Giornaliera per prestazioni ottimali

## Changelog

### 1.1.0 - Gennaio 2025
* **🆕 Nuovo**: Endpoint `DELETE /delete-log` per cancellazione controllata
* **🆕 Nuovo**: Opzione "cancella dopo lettura" configurabile  
* **🔧 Migliorato**: Gestione wp-config.php più robusta e sicura
* **🔧 Migliorato**: Interfaccia admin con test endpoint integrato
* **🔧 Migliorato**: Validazione input più rigorosa
* **🔧 Migliorato**: Rate limiting più efficiente con pulizia automatica
* **🛡️ Sicurezza**: Protezione timing attack nell'autenticazione
* **🛡️ Sicurezza**: Backup automatico wp-config.php prima modifiche
* **📚 Docs**: Documentazione completamente riscritta

### 1.0.0 - Dicembre 2024
* 🚀 **Rilascio Iniziale**
* ✅ Endpoint API REST base (`/logs`, `/status`)
* ✅ Autenticazione tramite chiave API  
* ✅ Rate limiting configurabile
* ✅ Interfaccia amministrativa
* ✅ Integrazione wp-config.php automatica

## Autori

* **Paolo Battiloro** - *Sviluppo iniziale* - [EyeArt](https://eyeart.it)

## Licenza

Questo progetto è sotto licenza GPL v2 o successiva - vedi il file [LICENSE](LICENSE) per i dettagli.

## Ringraziamenti

* Grazie alla comunità WordPress per il supporto continuo  
* A tutti gli sviluppatori che contribuiscono al testing e al feedback

---


## Description

**Debug VSCode** transforms WordPress into an API endpoint for your debug logs, making development more efficient and professional.

### 🚀 Key Features

* **3 Secure REST API Endpoints**
  - `GET /logs` - Read log files with advanced options
  - `GET /status` - System and configuration information  
  - `DELETE /delete-log` - Controlled log deletion

* **Multi-Level Authentication**
  - Automatically generated API key (`dvsc_[random]_[timestamp]`)
  - Support for `X-API-Key` and `Authorization Bearer` headers
  - Automatic integration with wp-config.php for maximum security

* **Anti-Brute Force Protection**
  - Configurable rate limiting per IP
  - Automatic temporary blocks (default: 10 attempts, 5 minutes)
  - Automatic cleanup of expired data via cron job

* **Customizable Log Reading**
  - Configurable log file (debug.log, error.log, custom.log)
  - Control of lines to read (`lines` parameter)
  - Reading from beginning or end of file (`from_bottom` parameter)
  - Secure deletion after reading option

* **Professional Admin Interface**
  - Intuitive panel in **Tools → Debug VSCode**
  - Integrated endpoint testing with response preview
  - Copy URLs and API keys with one click
  - Real-time settings management via AJAX

### 💡 Ideal Use Cases

✅ **VSCode Development** - Extensions that read WordPress logs  
✅ **Remote Monitoring** - Alert and notification systems  
✅ **Advanced Debugging** - Real-time log analysis  
✅ **CI/CD Pipeline** - Test and deployment automation  
✅ **DevOps Tools** - Integration with custom dashboards

## Installation

1. **Upload** the plugin to `/wp-content/plugins/debug-vscode/`
2. **Activate** via WordPress Admin → Plugins
3. **Configure** in **Tools → Debug VSCode**
4. **Enable** the API and save to automatically generate the key

The plugin will automatically update `wp-config.php` with:
```php
define('DEBUG_VSCODE_API_KEY', 'your_generated_key');
```

## Usage Examples

Once the plugin is properly configured, you can easily access WordPress logs through the dedicated REST API endpoint. Here are some practical examples to get you started immediately:

**� Standard Log Access**
```
https://DOMAIN/wp-json/debug-vscode/v1/logs?api_key=Value
```
This endpoint allows you to read debug logs in JSON format, perfect for integration with development and monitoring tools.

**🗑️ Read with Automatic Deletion**
```
https://DOMAIN/wp-json/debug-vscode/v1/logs?cancella=si&api_key=Value
```
Use this endpoint when you want to automatically delete logs after each read, keeping your system clean and optimized for performance.

## Security

### 🛡️ Security Features

* **Mandatory Authentication** - No anonymous access
* **Intelligent Rate Limiting** - Automatic IP-based protection  
* **Robust Input Validation** - Key length and format checking
* **Access Logging** - Tracking of unauthorized attempts
* **Secure Error Handling** - No information disclosure
* **Automatic Cleanup** - Removal of expired temporary data

### ⚙️ Rate Limiting Configuration

- **Maximum Attempts**: 1-100 (default: 10)
- **Lock Duration**: 60-3600 seconds (default: 300 = 5 min)
- **Automatic Reset**: On correct authentication
- **Cron Cleanup**: Daily for optimal performance

## Changelog

### 1.1.0 - January 2025
* **🆕 New**: `DELETE /delete-log` endpoint for controlled deletion
* **🆕 New**: Configurable "delete after reading" option  
* **🔧 Improved**: More robust and secure wp-config.php management
* **🔧 Improved**: Admin interface with integrated endpoint testing
* **🔧 Improved**: More rigorous input validation
* **🔧 Improved**: More efficient rate limiting with automatic cleanup
* **🛡️ Security**: Timing attack protection in authentication
* **🛡️ Security**: Automatic wp-config.php backup before changes
* **📚 Docs**: Completely rewritten documentation

### 1.0.0 - December 2024
* 🚀 **Initial Release**
* ✅ Basic REST API endpoints (`/logs`, `/status`)
* ✅ Authentication via API key  
* ✅ Configurable rate limiting
* ✅ Administrative interface
* ✅ Automatic wp-config.php integration

## Authors

* **Paolo Battiloro** - *Initial development* - [EyeArt](https://eyeart.it)

## License

This project is licensed under GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Acknowledgements

* Thanks to the WordPress community for continued support
* To all developers who contribute to testing and feedback
