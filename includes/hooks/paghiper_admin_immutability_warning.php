<?php
/**
 * Alerta sobre a Imutabilidade das Faturas no WHMCS v9
 * 
 * @package    PagHiper para WHMCS
 * @version    2.5.4
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Henrique Cruz
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2026, PagHiper
 * @link       https://www.paghiper.com/
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

use WHMCS\Database\Capsule;

function paghiper_admin_immutability_warning($vars) {
    // Carrega o arquivo de configuração do WHMCS diretamente para garantir o acesso à variável
    $config_file = __DIR__ . '/../../configuration.php';
    if (file_exists($config_file)) {
        include $config_file;
    }

    // Se a mutação estiver ativada, não precisamos mostrar o aviso
    if (isset($allow_adminarea_invoice_mutation) && $allow_adminarea_invoice_mutation === true) {
        return '';
    }

    // Só mostramos o aviso nas páginas relevantes (Dashboard, Portais de Pagamento e Faturas)
    // Suporta tanto os arquivos diretos quanto as rotas do WHMCS v9
    $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

    $isTargetPage = false;
    
    // Dashboard (index.php sem rotas ou com rotas de dashboard)
    if (basename($scriptName) === 'index.php') {
        $isTargetPage = true;
    }
    // Portais de Pagamento (arquivo legado ou rota moderna)
    if (strpos($requestUri, 'configgateways') !== false || strpos($requestUri, 'gateways') !== false) {
        $isTargetPage = true;
    }
    // Faturas (arquivo legado ou rota moderna)
    if (strpos($requestUri, 'invoices') !== false) {
        $isTargetPage = true;
    }

    if (!$isTargetPage) {
        return;
    }

    // Checamos se o PagHiper ou PagHiper PIX está ativo no sistema
    try {
        $active_gateways = Capsule::table('tblpaymentgateways')
            ->whereIn('gateway', ['paghiper', 'paghiper_pix'])
            ->count();
        
        if ($active_gateways === 0) {
            return '';
        }
    } catch (\Exception $e) {
        return '';
    }

    $message = '<strong>Atenção (Módulo PagHiper):</strong> O WHMCS v9 introduziu a imutabilidade de faturas. ';
    $message .= 'Para que o desconto de pagamento antecipado e juros/multas por atraso funcionem corretamente com o PagHiper, ';
    $message .= 'você precisa adicionar a seguinte linha ao seu arquivo <code>configuration.php</code>: ';
    $message .= '<br><pre style="margin-top: 5px; background: #fff; padding: 5px; display: inline-block; border: 1px solid #ccc; font-family: monospace;">$allow_adminarea_invoice_mutation = true;</pre>';

    return <<<HTML
<div id="paghiper_immutability_warning" class="alert alert-warning" style="margin: 15px 0; font-size: 13px; display: none;">
    {$message}
</div>
<script type="text/javascript">
    jQuery(document).ready(function() {
        var container = jQuery('#contentarea');
        if (container.length > 0) {
            jQuery('#paghiper_immutability_warning').prependTo(container).show();
        }
    });
</script>
HTML;
}

add_hook('AdminAreaFooterOutput', 1, 'paghiper_admin_immutability_warning');
