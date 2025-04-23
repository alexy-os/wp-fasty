<?php

declare(strict_types=1);

use Latte\Runtime as LR;

/** source: D:\xampp\htdocs\alexy-os/wp-content/themes/wp-fasty/wp-fasty-ym/views/page/page.latte */
final class Template_8936697d03 extends Latte\Runtime\Template
{
	public const Source = 'D:\\xampp\\htdocs\\alexy-os/wp-content/themes/wp-fasty/wp-fasty-ym/views/page/page.latte';


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo '<article class="page">
    <header class="page-header">
        <div class="container">
            <h1 class="page-title">';
		echo LR\Filters::escapeHtmlText($page['title']) /* line 11 */;
		echo '</h1>
            
';
		if (isset($page['excerpt']) && $page['excerpt']) /* line 13 */ {
			echo '                <div class="page-excerpt">
                    ';
			echo $page['excerpt'] /* line 15 */;
			echo '
                </div>
';
		}
		echo '        </div>
    </header>

    <div class="page-content">
        <div class="container">
            <div class="content-wrapper">
                ';
		echo $page['content'] /* line 24 */;
		echo '
            </div>
            
';
		if (isset($page['has_sidebar']) && $page['has_sidebar']) /* line 27 */ {
			echo '                <aside class="page-sidebar">
';
			do_action('page_sidebar');
			echo '                </aside>
';
		}
		echo '        </div>
    </div>

    <footer class="page-footer">
        <div class="container">
';
		if (isset($page['modified_date']) && $page['modified_date']) /* line 37 */ {
			echo '                <div class="page-meta">
                    <time datetime="';
			echo LR\Filters::escapeHtmlAttr($page['modified_date']['formatted']) /* line 39 */;
			echo '" class="updated">
                        Last updated: ';
			echo LR\Filters::escapeHtmlText($page['modified_date']['display']) /* line 40 */;
			echo '
                    </time>
                </div>
';
		}
		echo '            
';
		do_action('page_footer');
		echo '        </div>
    </footer>
</article> ';
	}
}
