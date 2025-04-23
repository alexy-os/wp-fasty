<?php

declare(strict_types=1);

use Latte\Runtime as LR;

/** source: D:\xampp\htdocs\alexy-os/wp-content/themes/wp-fasty/wp-fasty-ym/views/layout/default.latte */
final class Template_98c995d9dd extends Latte\Runtime\Template
{
	public const Source = 'D:\\xampp\\htdocs\\alexy-os/wp-content/themes/wp-fasty/wp-fasty-ym/views/layout/default.latte';


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo '<!DOCTYPE html>
<html lang="';
		echo LR\Filters::escapeHtmlAttr($site['lang']) /* line 9 */;
		echo '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>';
		echo LR\Filters::escapeHtmlText($site['title']) /* line 13 */;
		echo '</title>
    
    <style>
';
		$this->createTemplate('../page/css/dist/critical.css', $this->params, 'include')->renderToContentType(null) /* line 17 */;
		echo '    </style>
    <link rel="stylesheet" href="http://alexy-os.local/theme.min.css">
    
';
		do_action('head');
		echo '</head>
<body class="theme-light">
    <header class="site-header">
';
		$this->createTemplate('parts/header.latte', $this->params, 'include')->renderToContentType('html') /* line 26 */;
		echo '    </header>

    <main class="site-content">
';
		do_action('before_content');
		echo '        ';
		echo $content /* line 31 */;
		echo "\n";
		do_action('after_content');
		echo '    </main>

    <footer class="site-footer">
';
		$this->createTemplate('parts/footer.latte', $this->params, 'include')->renderToContentType('html') /* line 36 */;
		echo '    </footer>
    
';
		do_action('footer');
		echo '</body>
</html> ';
	}
}
