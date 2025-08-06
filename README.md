# Debug VSCode - WordPress Plugin

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/debug-vscode?label=Plugin%20Version)](https://wordpress.org/plugins/debug-vscode/)
[![WordPress Plugin Required PHP Version](https://img.shields.io/wordpress/plugin/required-php/debug-vscode)](https://wordpress.org/plugins/debug-vscode/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/stars/debug-vscode)](https://wordpress.org/support/plugin/debug-vscode/reviews/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg)](https://github.com/ninmorfeo/Debug-VScode-Wordpress-for-AI/blob/main/LICENSE)

Accedi ai log di WordPress tramite API REST sicura. Perfetto per debugging con VSCode e strumenti di sviluppo esterni.

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

## Utilizzo API

### Endpoint Disponibili

**🔍 Lettura Log**
```
GET /wp-json/debug-vscode/v1/logs
```
Parametri opzionali:
- `lines=100` - Numero righe da leggere (default: 100)
- `from_bottom=true` - Leggi dalla fine (true) o inizio (false) 
- `cancella=si` - Cancella dopo lettura (se abilitato)

**📊 Status Sistema**
```
GET /wp-json/debug-vscode/v1/status
```
Ritorna: versioni WordPress/PHP, stato debug, timestamp

**🗑️ Cancellazione Log**
```
DELETE /wp-json/debug-vscode/v1/delete-log
```
Richiede: opzione "cancellazione dopo lettura" abilitata

### Esempi Pratici

**cURL Semplice**
```bash
curl "https://tuosito.com/wp-json/debug-vscode/v1/logs?api_key=dvsc_abc123_1234567890"
```

**Con Header (raccomandato)**  
```bash
curl -H "X-API-Key: dvsc_abc123_1234567890" \
     "https://tuosito.com/wp-json/debug-vscode/v1/logs?lines=50"
```

**JavaScript/Fetch**
```javascript
fetch('/wp-json/debug-vscode/v1/logs', {
    headers: { 'X-API-Key': 'dvsc_abc123_1234567890' }
})
.then(res => res.json())
.then(data => console.log(data.logs));
```

**Python**
```python
import requests
response = requests.get(
    'https://tuosito.com/wp-json/debug-vscode/v1/logs',
    headers={'X-API-Key': 'dvsc_abc123_1234567890'}
)
logs = response.json()['logs']
```

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

## Sviluppo

Per contribuire allo sviluppo del plugin:

1. Clona il repository
2. Crea un nuovo branch per la tua feature (`git checkout -b feature/AmazingFeature`)
3. Fai i tuoi cambiamenti
4. Commit (`git commit -m 'Add some AmazingFeature'`)
5. Push al branch (`git push origin feature/AmazingFeature`)
6. Apri una Pull Request

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