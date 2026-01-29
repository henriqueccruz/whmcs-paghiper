<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\BuilderHelpers;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

use Illuminate\Database\Capsule\Manager as Capsule;

class PaghiperPdfInvoiceIntegrator {

    private $cacheManager,
            $version,
            $activeTemplate,
            $parentTemplate = NULL,
            $tplPath = NULL;

    function __construct() {
        // Require WHMCS to be initialized
        if (!defined('ROOTDIR')) {
            // Attempt to locate init.php assuming we are in modules/gateways/paghiper/inc/helpers/
            $initPath = __DIR__ . '/../../../../../init.php';
            if (file_exists($initPath)) {
                require_once($initPath);
            }
        }

        // Initialize cache
        $cacheMethod = \WHMCS\Config\Setting::getValue('Cache_Driver');
        $this->cacheManager = \WHMCS\Cache\Manager::factory($cacheMethod);

        // Check and update template
        $this->updatePdfInvoiceTpl();
    }

    function isTplOutdated() {
        // Get original file hash stored when we last integrated
        $storedHash = \WHMCS\Config\Setting::getValue('Paghiper_InvoicePdf_Origin_TplHash');
        
        if (!$storedHash) {
            return true; // No hash stored, assume outdated or not present
        }

        // We can't easily check the "original" state of the file currently on disk 
        // because it's already modified (potentially).
        // This check would require knowing what the file *should* look like.
        // For now, we rely on isTplIntegrated() to check if our code is present.
        
        return false;
    }

    function isTplIntegrated($tplAST = null) {
        $target_include = [
            'filename'      => '/../../modules/gateways/paghiper/inc/helpers/attach_pdf_slip.php',
            'type'          => 1 // include statement
        ];

        if ($tplAST === null) {
            $tplFilePath = $this->getPdfInvoiceTplPath();
            if (!$tplFilePath || !file_exists($tplFilePath)) {
                return false;
            }

            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
            try {
                $code = file_get_contents($tplFilePath);
                $tplAST = $parser->parse($code);
            } catch (Error $error) {
                // Parse error, assume not integrated or broken
                return false;
            }
        }

        $nodeFinder = new NodeFinder();
        $found = $nodeFinder->findFirst($tplAST, function(Node $node) use ($target_include) {
            if ($node instanceof Expression && $node->expr instanceof Include_) {
                $includeFile = null;
                
                // Handle various node structures for the include path
                if ($node->expr->expr instanceof String_) {
                    $includeFile = $node->expr->expr->value;
                }

                $includeType = $node->expr->type;

                // Check if it matches our target file
                if ($includeFile === $target_include['filename']) {
                    return true;
                }
            }
            return false;
        });

        return $found !== null;
    }

    function getPdfInvoiceTplPath() {
        // Retornamos o dado, caso ja o tenhamos na classe
        if($this->tplPath)
            return $this->tplPath;

        // 1. Dados que precisamos setar para fazer qualquer operação primeiro
        $this->version = Capsule::table('tblconfiguration')->where('setting', 'Version')->value('value');
        $this->activeTemplate = Capsule::table('tblconfiguration')->where('setting', 'Template')->value('value');

        $paths = [
            ROOTDIR . "/templates/{$this->activeTemplate}/invoicepdf.tpl"
        ];

        // 2. Lógica de Child Theme (Suporte para WHMCS 8.x + Temas como Lagom)
        $templateConfig = ROOTDIR . "/templates/{$this->activeTemplate}/theme.yaml";
        if (file_exists($templateConfig)) {
            $yamlContent = file_get_contents($templateConfig);
            if (preg_match('/parent:\s*["\\]?([^"\\]+)["\\]?/', $yamlContent, $matches)) {
                $this->parentTemplate = trim($matches[1]);
                $paths[] = ROOTDIR . "/templates/{$this->parentTemplate}/invoicepdf.tpl";
            }
        }

        // 3. Adiciona os Fallbacks do Sistema por versão
        $isModern = version_compare($this->version, '8.1.0', '>=');
        $paths[] = ROOTDIR . ($isModern ? "/templates/twenty-one/invoicepdf.tpl" : "/templates/six/invoicepdf.tpl");
        $paths[] = ROOTDIR . "/templates/six/invoicepdf.tpl"; // Fallback final universal

        // 4. Retorna o PRIMEIRO arquivo que fisicamente existir na hierarquia
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $this->cacheManager->delete('paghiper_pdf_int_nopath');
                $this->tplPath = $path;
                return $path;
            }
        }

        $this->cacheManager->set('paghiper_pdf_int_nopath', true, 3600);
        return null;
    }

    function generateFileHash($file) {
        if (file_exists($file)) {
            return md5_file($file);
        }
        return md5($file);
    }

    function updatePdfInvoiceTpl() {
        $tplFilePath = $this->getPdfInvoiceTplPath();

        if (!$tplFilePath || !file_exists($tplFilePath)) {
            return false;
        }

        $localTime = time();
        $tplBackupPath = dirname($tplFilePath) . "/invoicepdf_backup_{$localTime}.tpl";

        // Parse file and check if we're integrated already
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            $code = file_get_contents($tplFilePath);
            $tplAST = $parser->parse($code);
        } catch (Error $error) {
            $this->cacheManager->set('paghiper_pdf_int_parse_err', $error->getMessage(), 3600);
            return false;
        }

        // Check if include is installed
        if ($this->isTplIntegrated($tplAST)) {
            return true;
        }

        // Backup existing file
        if (!copy($tplFilePath, $tplBackupPath)) {
             $this->cacheManager->set('paghiper_pdf_int_backup_err', "Could not create backup at $tplBackupPath", 3600);
             // Proceeding with caution or return false?
             // Ideally we should stop if backup fails to prevent data loss.
             // return false; 
        }

        if (!is_writable($tplFilePath)) {
             $this->cacheManager->set('paghiper_pdf_int_perms', "File not writable", 3600);
             return false;
        }

        // Prepare the include node
        $full_path = '/../../modules/gateways/paghiper/inc/helpers/attach_pdf_slip.php';

        $include_node = new Expr\FuncCall(
            new Name('include'),
            [new Arg(new String_($full_path))]
        );

        // Add to the beginning of the AST
        array_unshift($tplAST, BuilderHelpers::normalizeStmt($include_node));

        $formattedTplCode = (new PrettyPrinter\Standard())->prettyPrintFile($tplAST);

        $originalFileHash = $this->generateFileHash($tplFilePath);

        try {
            error_clear_last();
            $tplUpdate = file_put_contents($tplFilePath, $formattedTplCode);

            if ($tplUpdate === false) {
                $error = error_get_last();
                $this->cacheManager->set('paghiper_pdf_int_cant_update', ($error['message'] ?? 'Erro desconhecido'), 3600);
                return false;
            } else {
                // Update Hash Configuration
                \WHMCS\Config\Setting::setValue('Paghiper_InvoicePdf_Origin_TplHash', $originalFileHash);
                
                $customFileHash = $this->generateFileHash($tplFilePath);
                \WHMCS\Config\Setting::setValue('Paghiper_InvoicePdf_Custom_TplHash', $customFileHash);
                
                // Clear Smarty Cache
                $smarty = new \WHMCS\Smarty();
                $smarty->clearCompiledTemplate();

                // Clear related errors
                $this->cacheManager->delete('paghiper_pdf_int_cant_update');
                
                return true;
            }
        } catch (Exception $e) {
            $this->cacheManager->set('paghiper_pdf_int_cant_update', $e->getMessage(), 3600);
            return false;
        }
    }
}

// Instantiate if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    // Only run if not being included
    new PaghiperPdfInvoiceIntegrator();
    echo "Integration check/update execution completed.";
}
