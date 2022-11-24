<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Expr\Include_;
use PhpParser\NodeTraverser;
use PhpParser\NodeFinder;
use PhpParser\NodeDumper;
use PhpParser\NodeVisitorAbstract;
use PhpParser\BuilderFactory;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

function paghiperUpdateInvoiceTpl() {

	require_once('../../../../../init.php');

	try {
		$whmcs = \DI::make("app");
	} catch (Error $error) {
		echo "Failed to initialize DI Class: {$error->getMessage()}\n";
		return;
	}

	$templateName = $whmcs->getClientAreaTemplate()->getName();
	if (!isValidforPath($templateName)) {
		throw new Exception\Fatal("Invalid System Template Name");
	}
	
	$tplfile = "invoicepdf.tpl";
	$tplFilePath = ROOTDIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . $templateName . DIRECTORY_SEPARATOR . $tplfile;

    $localTime = time();
    $tplBackupPath = "invoicepdf_{$localTime}.tpl";

	if ( file_exists ($tplFilePath)){
        // Parse file and check if we're integrated already
		$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
		try {
			$tplAST = $parser->parse(file_get_contents($tplFilePath) );
		} catch (Error $error) {
			echo "Parse error: {$error->getMessage()}\n";
			return false;
		}

        // Check if include is installed
        if(paghiperCheckInvoiceTpl($tplAST)) {
            return true;
        } 

        // If not, install it
        if( !is_writable ($tplBackupPath) ) {
            //return false;
        }

        if( !is_writable ($tplFilePath) ) {
            return false;
        } else {
            // Check if one of compatible templates is active

            // $
			$full_path = '/../../modules/gateways/paghiper/inc/helpers/attach_pdf_slip.php';

			$include_node = new Node\Stmt\Expression(
                new Node\Expr\Include_(
                    new Node\Scalar\String_($full_path),Node\Expr\Include_::TYPE_INCLUDE
                )
            );

			//$factory = new BuilderFactory;
			//$node = $factory->addStmt($include_node)->getNode();

			
			$stmts = array_merge([[$include_node], $tplAST]);
			$prettyPrinter = new PrettyPrinter\Standard();
			echo sprintf('<pre>%s</pre>', $prettyPrinter->prettyPrintFile($stmts));

			$initialSearchString = '<?php';
			preg_replace($search, $replace, $subject, 1);

			$original_content = file_get_contents($tplFilePath);
			$new_update = preg_replace('/\$page_menu_transparent\s*=\s*1;/','$page_menu_transparent = 0;', $original_content, 1);

			if(file_put_contents ($file, $new_update)){

			}
        }
	}
}

$target_include = [
	'filename' 	=> '/../../modules/gateways/paghiper/inc/helpers/attach_pdf_slip.php',
	'type'		=> 1
];

$isInstalled = false;

function paghiperCheckInvoiceTpl($tplAST) {

	global $target_include, $isInstalled;

	$traverser = new NodeTraverser();
	$traverser->addVisitor(new class extends NodeVisitorAbstract {
		public function enterNode(Node $node) {

			global $target_include, $isInstalled;

			if ($node instanceof Expression && $node->expr instanceof Include_) {
				
				$includeFile = $node->expr->expr->right->value;
				$includeType = $node->expr->type;

				if($includeFile == $target_include['filename'] && $includeType == $target_include['type']) {
					$isInstalled = true;
				}
			}
		}
	});

	$traverser->traverse($tplAST);
	return $isInstalled;
}


echo sprintf('<pre>%s</pre>', var_export(paghiperUpdateInvoiceTpl(), TRUE));