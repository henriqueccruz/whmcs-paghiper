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
    // Só rodamos o safeguard de imutabilidade se for WHMCS v9 ou superior
    try {
        $whmcsVersion = Capsule::table('tblconfiguration')->where('setting', 'Version')->value('value');
        $majorVersion = (int) explode('.', $whmcsVersion)[0];
        if ($majorVersion < 9) {
            return;
        }
    } catch (\Exception $e) {
        logActivity("PagHiper Warning Hook: Erro ao obter a versão do WHMCS no banco de dados: " . $e->getMessage());
        return;
    }

    // Carrega o arquivo de configuração do WHMCS diretamente para garantir o acesso à variável
    $config_file = __DIR__ . '/../../configuration.php';
    if (file_exists($config_file)) {
        include $config_file;
    }

    // Se a mutação estiver ativada (true), não precisamos mostrar o aviso
    if (isset($allow_adminarea_invoice_mutation) && $allow_adminarea_invoice_mutation === false) {
        return;
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
            return;
        }
    } catch (\Exception $e) {
        return;
    }

    return <<<HTML
<link rel="stylesheet" type="text/css" href="../modules/gateways/paghiper/assets/css/paghiper_admin.css">
<div id="paghiper_immutability_warning" class="paghiper-admin-alert paghiper-admin-alert-warning" style="display: none;">
    <div class="paghiper-alert-logo">
        <svg id="Camada_1" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 41.3 28.9">
            <defs>
                <style>
                    .st0 { fill: #26d07c; }
                    .st1 { fill: #0957c3; }
                </style>
            </defs>
            <path class="st0" d="M24.2,14.3h-3.4c-.1,0-.2,0-.3-.2l-3.4-6.7c0-.1,0-.2.2-.2h3.1c.1,0,.2,0,.3.2l1.7,3.4,1.8,3.6h0Z"/>
            <path class="st1" d="M24.2,0h-12.2c-.1,0-.2,0-.3.2-1.7,2.8-4.2,11.2,3.2,17.9,0,0,.2,0,.3,0l1.4-2.9c0-.1,0-.3,0-.4-2.9-3-3.9-7.3-2.5-11.1h10.1c1.8,0,3.6,1.4,3.6,3.6,0,4.3-5.4,3.6-5.4,3.6l1.8,3.6c4.1,0,7.2-3.4,7.2-7.1S28.4,0,24.2,0"/>
            <path class="st0" d="M25,21c-.2,0-.4.2-.4.4s.2.4.4.4.4-.2.4-.4-.2-.4-.4-.4"/>
            <path class="st1" d="M38.7,23.7v2.6h.9v-2.6c0-1,.8-1.8,1.8-1.8v-.9c-1.4,0-2.6,1.2-2.6,2.6"/>
            <path class="st1" d="M35.2,21c-1.4,0-2.6,1.2-2.6,2.6s1.2,2.6,2.6,2.6h1.8v-.9h-1.8c-.6,0-1.2-.3-1.5-.9h4c0-.3.2-.6.2-.9,0-1.4-1.2-2.6-2.6-2.6M33.4,23.7c0-1,.8-1.8,1.8-1.8s1.8.8,1.8,1.8h-3.5Z"/>
            <path class="st1" d="M29,21c-1.4,0-2.6,1.2-2.6,2.6v5.3h.9v-3.3c.5.4,1.1.7,1.8.7,1.4,0,2.6-1.2,2.6-2.6s-1.2-2.6-2.6-2.6M29,25.4c-1,0-1.8-.8-1.8-1.8s.8-1.8,1.8-1.8,1.8.8,1.8,1.8-.8,1.8-1.8,1.8"/>
            <rect class="st1" x="24.6" y="22.8" width=".9" height="3.5"/>
            <polygon class="st1" points="22.9 22.8 19.3 22.8 19.3 20.1 18.5 20.1 18.5 26.3 19.3 26.3 19.3 23.7 22.9 23.7 22.9 26.3 23.7 26.3 23.7 20.1 22.9 20.1 22.9 22.8"/>
            <path class="st1" d="M17.4,22.8c-.4-1-1.4-1.8-2.5-1.8s-2.6,1.2-2.6,2.6.3,1.5.9,2c.5.4,1.1.7,1.8.7s1.3-.2,1.8-.7c0,.3,0,.8,0,1.1-.2.8-.9,1.3-1.7,1.3v.9c.7,0,1.3-.2,1.8-.7.3-.3.6-.7.7-1.1,0-.3.2-.6.2-.9v-2.6c0-.3,0-.6-.1-.9M14.9,25.4c-1,0-1.8-.8-1.8-1.8s.8-1.8,1.8-1.8,1.8.8,1.8,1.8-.8,1.8-1.8,1.8"/>
            <path class="st1" d="M8.8,21c-1.5,0-2.6,1.2-2.6,2.6s.3,1.5.9,2c.5.4,1.1.7,1.8.7s1.3-.2,1.8-.7v.7h.9v-2.6c0-1.4-1.2-2.6-2.6-2.6M8.8,25.4c-1,0-1.8-.8-1.8-1.8s.8-1.8,1.8-1.8,1.8.8,1.8,1.8-.8,1.8-1.8,1.8"/>
            <path class="st1" d="M4.4,20.8c-.2-.2-.5-.4-.9-.5-.3-.1-.6-.1-.9-.1H0v6.2h.9v-.9h1.8c.3,0,.6,0,.9-.1.3-.1.6-.3.9-.5.3-.3.6-.7.7-1.1,0-.3.2-.6.2-.9s0-.6-.2-.9c-.2-.4-.4-.8-.7-1.1M4.2,23.7c-.3.5-.9.9-1.5.9H.9v-3.5h1.8c.6,0,1.2.3,1.5.9.2.3.2.6.2.9s0,.6-.2.9"/>
        </svg>
    </div>
    <div class="paghiper-alert-body">
        <div class="paghiper-alert-title">Atenção: Imutabilidade de Faturas (WHMCS v9)</div>
        <div class="paghiper-alert-text">
            O WHMCS v9 introduziu o conceito de faturas imutáveis. Para que descontos por pagamento antecipado e juros/multas por atraso calculados pelo portal funcionem corretamente, é necessário habilitar a mutação manual de faturas na área administrativa.
        </div>
        <div class="paghiper-alert-action">
            <span>Adicione a seguinte linha de configuração ao seu arquivo <code>configuration.php</code>:</span>
            <pre>\$allow_adminarea_invoice_mutation = true;</pre>
        </div>
    </div>
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
