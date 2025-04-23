<?php

declare(strict_types=1);

use Latte\Runtime as LR;

/** source: D:\xampp\htdocs\alexy-os\wp-content\themes\wp-fasty\wp-fasty-ym\views\layout\parts\header.latte */
final class Template_3f6ae03bfd extends Latte\Runtime\Template
{
	public const Source = 'D:\\xampp\\htdocs\\alexy-os\\wp-content\\themes\\wp-fasty\\wp-fasty-ym\\views\\layout\\parts\\header.latte';


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo '<header class="site-header">
    <div class="container">
        <div class="header-content">
            <a href="';
		echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($site['url'])) /* line 12 */;
		echo '" class="site-logo">
                ';
		echo LR\Filters::escapeHtmlText($site['title']) /* line 13 */;
		echo '
            </a>
            
';
		if (isset($menu['primary'])) /* line 16 */ {
			echo '                <nav class="site-nav">
                    <ul class="nav-menu">
';
			foreach ($menu['primary']['items'] as $item) /* line 19 */ {
				echo '                            <li class="nav-item ';
				if ($item['current']) /* line 20 */ {
					echo 'is-active';
				}
				echo '">
                                <a href="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($item['url'])) /* line 21 */;
				echo '" class="nav-link">
                                    ';
				echo LR\Filters::escapeHtmlText($item['title']) /* line 22 */;
				echo '
                                </a>
                            </li>
';

			}

			echo '                    </ul>
                </nav>
';
		}
		echo '        </div>
    </div>
</header> ';
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['item' => '19'], $this->params) as $ʟ_v => $ʟ_l) {
				trigger_error("Variable \$$ʟ_v overwritten in foreach on line $ʟ_l");
			}
		}
		return get_defined_vars();
	}
}
