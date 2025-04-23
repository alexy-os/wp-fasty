<?php

declare(strict_types=1);

use Latte\Runtime as LR;

/** source: D:\xampp\htdocs\alexy-os/wp-content/themes/wp-fasty/wp-fasty-ym/views/single/single.latte */
final class Template_e9cc3f9859 extends Latte\Runtime\Template
{
	public const Source = 'D:\\xampp\\htdocs\\alexy-os/wp-content/themes/wp-fasty/wp-fasty-ym/views/single/single.latte';


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo '<article class="post">
    <header class="post-header">
        <div class="container">
';
		if (isset($page['thumbnail']) && $page['thumbnail']) /* line 11 */ {
			echo '                <figure class="post-thumbnail">
                    <img src="';
			echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($page['thumbnail']['url'])) /* line 13 */;
			echo '" alt="';
			echo LR\Filters::escapeHtmlAttr($page['thumbnail']['alt']) /* line 13 */;
			echo '" class="post-thumbnail-image">
                </figure>
';
		}
		echo '            
            <h1 class="post-title">';
		echo LR\Filters::escapeHtmlText($page['title']) /* line 17 */;
		echo '</h1>
            
            <div class="post-meta">
';
		if (isset($page['author']) && $page['author']) /* line 20 */ {
			echo '                    <div class="post-author">
                        <span class="author-avatar">
';
			if (isset($page['author']['avatar']) && $page['author']['avatar']) /* line 23 */ {
				echo '                                <img src="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($page['author']['avatar'])) /* line 24 */;
				echo '" alt="';
				echo LR\Filters::escapeHtmlAttr($page['author']['name']) /* line 24 */;
				echo '" class="author-avatar-image">
';
			}
			echo '                        </span>
                        <span class="author-name">
                            By <a href="';
			echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($page['author']['url'])) /* line 28 */;
			echo '" class="author-link">';
			echo LR\Filters::escapeHtmlText($page['author']['name']) /* line 28 */;
			echo '</a>
                        </span>
                    </div>
';
		}
		echo '                
';
		if (isset($page['date']) && $page['date']) /* line 33 */ {
			echo '                    <time datetime="';
			echo LR\Filters::escapeHtmlAttr($page['date']['formatted']) /* line 34 */;
			echo '" class="post-date">
                        ';
			echo LR\Filters::escapeHtmlText($page['date']['display']) /* line 35 */;
			echo '
                    </time>
';
		}
		echo '                
';
		if (isset($page['categories']) && $page['categories']) /* line 39 */ {
			echo '                    <div class="post-categories">
                        <span class="label">Categories:</span>
                        <ul class="category-list">
';
			foreach ($page['categories'] as $category) /* line 43 */ {
				echo '                                <li class="category-item">
                                    <a href="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($category['url'])) /* line 45 */;
				echo '" class="category-link">';
				echo LR\Filters::escapeHtmlText($category['name']) /* line 45 */;
				echo '</a>
                                </li>
';

			}

			echo '                        </ul>
                    </div>
';
		}
		echo '            </div>
        </div>
    </header>

    <div class="post-content">
        <div class="container">
            <div class="content-wrapper">
                ';
		echo $page['content'] /* line 58 */;
		echo '
                
';
		if (isset($page['tags']) && $page['tags']) /* line 60 */ {
			echo '                    <div class="post-tags">
                        <span class="label">Tags:</span>
                        <ul class="tag-list">
';
			foreach ($page['tags'] as $tag) /* line 64 */ {
				echo '                                <li class="tag-item">
                                    <a href="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($tag['url'])) /* line 66 */;
				echo '" class="tag-link">';
				echo LR\Filters::escapeHtmlText($tag['name']) /* line 66 */;
				echo '</a>
                                </li>
';

			}

			echo '                        </ul>
                    </div>
';
		}
		echo '            </div>
            
';
		if (isset($page['has_sidebar']) && $page['has_sidebar']) /* line 74 */ {
			echo '                <aside class="post-sidebar">
';
			do_action('post_sidebar');
			echo '                </aside>
';
		}
		echo '        </div>
    </div>

    <footer class="post-footer">
        <div class="container">
            <nav class="post-navigation">
';
		if (isset($page['prev_post']) && $page['prev_post']) /* line 85 */ {
			echo '                    <a href="';
			echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($page['prev_post']['url'])) /* line 86 */;
			echo '" class="post-nav-link post-nav-prev">
                        <span class="post-nav-label">Previous</span>
                        <span class="post-nav-title">';
			echo LR\Filters::escapeHtmlText($page['prev_post']['title']) /* line 88 */;
			echo '</span>
                    </a>
';
		}
		echo '                
';
		if (isset($page['next_post']) && $page['next_post']) /* line 92 */ {
			echo '                    <a href="';
			echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($page['next_post']['url'])) /* line 93 */;
			echo '" class="post-nav-link post-nav-next">
                        <span class="post-nav-label">Next</span>
                        <span class="post-nav-title">';
			echo LR\Filters::escapeHtmlText($page['next_post']['title']) /* line 95 */;
			echo '</span>
                    </a>
';
		}
		echo '            </nav>
            
';
		do_action('post_footer');
		echo '        </div>
    </footer>
</article> ';
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['category' => '43', 'tag' => '64'], $this->params) as $ʟ_v => $ʟ_l) {
				trigger_error("Variable \$$ʟ_v overwritten in foreach on line $ʟ_l");
			}
		}
		return get_defined_vars();
	}
}
