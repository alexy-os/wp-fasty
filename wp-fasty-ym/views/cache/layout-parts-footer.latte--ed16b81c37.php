<?php

declare(strict_types=1);

use Latte\Runtime as LR;

/** source: D:\xampp\htdocs\alexy-os\wp-content\themes\wp-fasty\wp-fasty-ym\views\layout\parts\footer.latte */
final class Template_ed16b81c37 extends Latte\Runtime\Template
{
	public const Source = 'D:\\xampp\\htdocs\\alexy-os\\wp-content\\themes\\wp-fasty\\wp-fasty-ym\\views\\layout\\parts\\footer.latte';


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo '<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-branding">
                <a href="';
		echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($site['url'])) /* line 13 */;
		echo '" class="footer-logo">
                    ';
		echo LR\Filters::escapeHtmlText($site['title']) /* line 14 */;
		echo '
                </a>
';
		if (isset($site['description']) && $site['description']) /* line 16 */ {
			echo '                    <p class="footer-description">';
			echo LR\Filters::escapeHtmlText($site['description']) /* line 17 */;
			echo '</p>
';
		}
		echo '            </div>
            
';
		if (isset($menu['footer'])) /* line 21 */ {
			echo '                <nav class="footer-nav">
                    <ul class="footer-menu">
';
			foreach ($menu['footer']['items'] as $item) /* line 24 */ {
				echo '                            <li class="footer-item">
                                <a href="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($item['url'])) /* line 26 */;
				echo '" class="footer-link">
                                    ';
				echo LR\Filters::escapeHtmlText($item['title']) /* line 27 */;
				echo '
                                </a>
                            </li>
';

			}

			echo '                    </ul>
                </nav>
';
		}
		echo '            
            <div class="footer-bottom">
                <p class="copyright">
                    &copy; ';
		echo LR\Filters::escapeHtmlText(date('Y')) /* line 37 */;
		echo ' ';
		echo LR\Filters::escapeHtmlText($site['title']) /* line 37 */;
		echo '. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer> ';
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['item' => '24'], $this->params) as $ʟ_v => $ʟ_l) {
				trigger_error("Variable \$$ʟ_v overwritten in foreach on line $ʟ_l");
			}
		}
		return get_defined_vars();
	}
}
