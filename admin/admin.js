jQuery(document).ready(function($) {
    'use strict';
    
    // Variabili globali
    const adminData = window.debugVSCodeAdmin || {};
    
    /**
     * Copia testo negli appunti
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).then(() => {
                showNotice('Copiato negli appunti!', 'success');
            }).catch(() => {
                fallbackCopyTextToClipboard(text);
            });
        } else {
            fallbackCopyTextToClipboard(text);
        }
    }
    
    /**
     * Fallback per copia negli appunti
     */
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.position = 'fixed';
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showNotice('Copiato negli appunti!', 'success');
        } catch (err) {
            showNotice('Errore nella copia', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    /**
     * Mostra notifica
     */
    function showNotice(message, type = 'info') {
        const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notice);
        
        setTimeout(() => {
            notice.fadeOut(() => notice.remove());
        }, 3000);
    }
    
    /**
     * Genera nuova chiave API
     */
    window.generateNewApiKey = function() {
        const apiKeyField = $('input[name="debug_vscode_options[api_key]"]');
        if (apiKeyField.length) {
            const newKey = 'dvsc_' + Math.random().toString(36).substr(2, 16) + '_' + Date.now();
            apiKeyField.val(newKey);
            showNotice(adminData.strings?.keyGenerated || 'Nuova chiave generata!', 'success');
        }
    };
    
    /**
     * Click-to-copy moderno per tutti gli elementi con classe click-to-copy
     */
    $('.click-to-copy').on('click', function() {
        const $element = $(this);
        const textToCopy = $element.data('copy');
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                showCopyFeedback($element);
            }).catch(() => {
                fallbackCopy(textToCopy, $element);
            });
        } else {
            fallbackCopy(textToCopy, $element);
        }
    });
    
    /**
     * Mostra feedback visivo dopo la copia
     */
    function showCopyFeedback($element) {
        $element.addClass('copied');
        setTimeout(() => {
            $element.removeClass('copied');
        }, 2000);
    }
    
    /**
     * Fallback per browser che non supportano clipboard API
     */
    function fallbackCopy(text, $element) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopyFeedback($element);
        } catch (err) {
            showNotice('Errore nella copia', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    /**
     * Test endpoint
     */
    $('.test-endpoint').on('click', function() {
        const $button = $(this);
        const $result = $('#test-result');
        const url = $button.data('url');
        const apiKey = $button.data('key');
        
        $button.prop('disabled', true).text('Testing...');
        $result.removeClass('success error').text('Esecuzione test...');
        
        // Test con parametro GET
        $.ajax({
            url: url + '?api_key=' + encodeURIComponent(apiKey) + '&lines=5',
            method: 'GET',
            timeout: 10000
        }).done(function(response) {
            $result.addClass('success').text(
                'Test riuscito!\n\n' +
                'Status: ' + (response.success ? 'Success' : 'Error') + '\n' +
                'Lines returned: ' + (response.lines_returned || 0) + '\n' +
                'File size: ' + (response.file_size || 0) + ' bytes\n' +
                'Last modified: ' + (response.last_modified || 'N/A') + '\n\n' +
                'Sample logs:\n' + (response.logs ? response.logs.substring(0, 500) + '...' : 'No logs')
            );
        }).fail(function(xhr, status, error) {
            let errorMessage = 'Test fallito!\n\n';
            errorMessage += 'Status: ' + xhr.status + '\n';
            errorMessage += 'Error: ' + error + '\n';
            
            if (xhr.responseJSON) {
                errorMessage += 'Response: ' + JSON.stringify(xhr.responseJSON, null, 2);
            } else if (xhr.responseText) {
                errorMessage += 'Response: ' + xhr.responseText.substring(0, 500);
            }
            
            $result.addClass('error').text(errorMessage);
        }).always(function() {
            $button.prop('disabled', false).text('Testa Endpoint');
        });
    });
    
    
    /**
     * Validazione form
     */
    $('form').on('submit', function() {
        const apiKey = $('input[name="debug_vscode_options[api_key]"]').val();
        const enabled = $('input[name="debug_vscode_options[enabled]"]').is(':checked');
        
        if (enabled && (!apiKey || apiKey.length < 8)) {
            showNotice('La chiave API deve essere di almeno 8 caratteri', 'error');
            return false;
        }
        
        return true;
    });
    
    /**
     * Auto-save delle impostazioni (opzionale)
     */
    let autoSaveTimeout;
    $('input[name^="debug_vscode_options"]').on('change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            showNotice('Ricorda di salvare le modifiche', 'warning');
        }, 2000);
    });
    
    
    /**
     * Tooltip per elementi con data-tooltip
     */
    $('[data-tooltip]').hover(
        function() {
            const tooltip = $('<div class="debug-vscode-tooltip">' + $(this).data('tooltip') + '</div>');
            $('body').append(tooltip);
            
            const pos = $(this).offset();
            tooltip.css({
                position: 'absolute',
                top: pos.top - tooltip.outerHeight() - 5,
                left: pos.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2),
                zIndex: 9999
            });
        },
        function() {
            $('.debug-vscode-tooltip').remove();
        }
    );
    
    /**
     * Gestione responsive
     */
    function handleResize() {
        const $admin = $('.debug-vscode-admin');
        if ($(window).width() < 1200) {
            $admin.addClass('mobile-layout');
        } else {
            $admin.removeClass('mobile-layout');
        }
    }
    
    $(window).on('resize', handleResize);
    handleResize();
    
    /**
     * Evidenzia campi modificati
     */
    $('input[name^="debug_vscode_options"]').on('change', function() {
        $(this).addClass('modified');
    });
    
    /**
     * Rimuovi evidenziazione dopo salvataggio
     */
    $('form').on('submit', function() {
        $('input.modified').removeClass('modified');
    });
    
    /**
     * Controllo stato endpoint in tempo reale
     */
    function checkEndpointStatus() {
        const $statusIndicator = $('.status-indicator');
        const enabled = $('input[name="debug_vscode_options[enabled]"]').is(':checked');
        
        if (enabled) {
            $statusIndicator.removeClass('disabled').addClass('enabled');
            $statusIndicator.find('span:not(.status-dot)').text('Attivo');
        } else {
            $statusIndicator.removeClass('enabled').addClass('disabled');
            $statusIndicator.find('span:not(.status-dot)').text('Disattivato');
        }
    }
    
    $('input[name="debug_vscode_options[enabled]"]').on('change', checkEndpointStatus);
    
    /**
     * Inizializzazione completata
     */
    console.log('Debug VSCode Admin initialized');
});