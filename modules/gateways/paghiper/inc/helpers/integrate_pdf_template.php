<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class PaghiperPdfInvoiceIntegrator {

    private $version,
            $activeTemplate,
            $parentTemplate = NULL,
            $tplPath = NULL;

    function __construct() {
        if (!defined('ROOTDIR')) {
            $initPath = __DIR__ . '/../../../../../init.php';
            if (file_exists($initPath)) {
                require_once($initPath);
            }
        }
    }

    public function autoHeal() {
        $this->updatePdfInvoiceTpl();
    }

    public function isTplIntegrated($tplFilePath = null) {
        $target_include = '/../../modules/gateways/paghiper/inc/helpers/attach_pdf_slip.php';

        if ($tplFilePath === null) {
            $tplFilePath = $this->getPdfInvoiceTplPath();
        }

        if (!$tplFilePath || !file_exists($tplFilePath)) {
            return false;
        }

        $code = file_get_contents($tplFilePath);
        
        // Verifica se a string do include existe no código
        if (strpos($code, $target_include) !== false) {
            return true;
        }

        return false;
    }

    public function getPdfInvoiceTplPath($forceTemplateName = null) {
        if($this->tplPath && !$forceTemplateName)
            return $this->tplPath;

        $paths = [];

        if ($forceTemplateName) {
            $paths[] = ROOTDIR . "/templates/{$forceTemplateName}/invoicepdf.tpl";
        } else {
            $this->version = Capsule::table('tblconfiguration')->where('setting', 'Version')->value('value');
            $this->activeTemplate = Capsule::table('tblconfiguration')->where('setting', 'Template')->value('value');

            $paths[] = ROOTDIR . "/templates/{$this->activeTemplate}/invoicepdf.tpl";

            $templateConfig = ROOTDIR . "/templates/{$this->activeTemplate}/theme.yaml";
            if (file_exists($templateConfig)) {
                $yamlContent = file_get_contents($templateConfig);
                if (preg_match('/parent:\s*["\\]?([^"\\]+)["\\]?/', $yamlContent, $matches)) {
                    $this->parentTemplate = trim($matches[1]);
                    $paths[] = ROOTDIR . "/templates/{$this->parentTemplate}/invoicepdf.tpl";
                }
            }

            $isModern = version_compare($this->version, '8.1.0', '>=');
            $paths[] = ROOTDIR . ($isModern ? "/templates/twenty-one/invoicepdf.tpl" : "/templates/six/invoicepdf.tpl");
            $paths[] = ROOTDIR . "/templates/six/invoicepdf.tpl"; 
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $this->tplPath = $path;
                return $path;
            }
        }

        return null;
    }

    public function generateFileHash($file) {
        if (file_exists($file)) {
            return md5_file($file);
        }
        return md5($file);
    }

    public function updatePdfInvoiceTpl($forceTemplateName = null) {
        $tplFilePath = $this->getPdfInvoiceTplPath($forceTemplateName);

        if (!$tplFilePath || !file_exists($tplFilePath)) {
            return false;
        }

        if ($this->isTplIntegrated($tplFilePath)) {
            return true;
        }

        $localTime = time();
        $tplBackupPath = dirname($tplFilePath) . "/invoicepdf_backup_{$localTime}.tpl";

        if (!copy($tplFilePath, $tplBackupPath)) {
             logActivity("PagHiper: Erro ao realizar backup de invoicepdf.tpl antes da integração. Integração abortada.");
             return false; 
        }

        if (!is_writable($tplFilePath)) {
             logActivity("PagHiper: O arquivo invoicepdf.tpl não tem permissão de escrita. Integração abortada.");
             return false;
        }

        $code = file_get_contents($tplFilePath);
        $full_path = '/../../modules/gateways/paghiper/inc/helpers/attach_pdf_slip.php';
        
        // Inserção Limpa: Encontra a primeira tag <?php e insere o include logo após ela.
        // Isso preserva 100% da formatação e comentários originais.
        $includeStmt = "\n    // PagHiper - Anexo de Boleto e PIX\n    include(__DIR__ . '" . $full_path . "');\n";
        
        $newCode = preg_replace('/<\?php\s*/', "<?php" . $includeStmt, $code, 1);

        if ($newCode === $code) {
             // Regex falhou, talvez a tag <?php esteja escrita de forma diferente ou não exista.
             logActivity("PagHiper: Não foi possível injetar o código no invoicepdf.tpl. Tag <?php não encontrada.");
             return false;
        }

        $originalFileHash = $this->generateFileHash($tplBackupPath);

        try {
            error_clear_last();
            $tplUpdate = file_put_contents($tplFilePath, $newCode);

            if ($tplUpdate === false) {
                $error = error_get_last();
                logActivity("PagHiper: Falha ao escrever arquivo invoicepdf.tpl. Erro: " . ($error['message'] ?? 'Desconhecido'));
                return false;
            } else {
                \WHMCS\Config\Setting::setValue('Paghiper_InvoicePdf_Origin_TplHash', $originalFileHash);
                $customFileHash = $this->generateFileHash($tplFilePath);
                \WHMCS\Config\Setting::setValue('Paghiper_InvoicePdf_Custom_TplHash', $customFileHash);
                
                $smarty = new \WHMCS\Smarty();
                $smarty->clearCompiledTemplate();
                
                logActivity("PagHiper: Integração ao arquivo invoicepdf.tpl realizada com sucesso.");
                return true;
            }
        } catch (Exception $e) {
            logActivity("PagHiper: Erro inesperado ao integrar invoicepdf.tpl: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna todos os templates disponíveis que possuem o arquivo invoicepdf.tpl
     */
    public function getAvailableTemplates() {
        $templatesDir = ROOTDIR . '/templates/';
        $available = [];
        if (is_dir($templatesDir)) {
            $dirs = array_diff(scandir($templatesDir), array('.', '..'));
            foreach ($dirs as $dir) {
                if (is_dir($templatesDir . $dir) && file_exists($templatesDir . $dir . '/invoicepdf.tpl')) {
                    $available[] = $dir;
                }
            }
        }
        return $available;
    }

    /**
     * Retorna lista de backups disponíveis para um template específico
     */
    public function getBackups($templateName) {
        $templateDir = ROOTDIR . "/templates/{$templateName}/";
        $backups = [];
        if (is_dir($templateDir)) {
            $files = glob($templateDir . "invoicepdf_backup_*.tpl");
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'date' => date("Y-m-d H:i:s", filemtime($file)),
                    'size' => filesize($file)
                ];
            }
        }
        // Ordena do mais recente pro mais antigo
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        return $backups;
    }

    /**
     * Restaura um backup específico
     */
    public function restoreBackup($templateName, $backupFilename) {
        $templateDir = ROOTDIR . "/templates/{$templateName}/";
        $backupPath = $templateDir . $backupFilename;
        $originalPath = $templateDir . "invoicepdf.tpl";

        if (file_exists($backupPath) && is_writable($originalPath)) {
            if (copy($backupPath, $originalPath)) {
                $smarty = new \WHMCS\Smarty();
                $smarty->clearCompiledTemplate();
                return true;
            }
        }
        return false;
    }
}

