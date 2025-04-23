<?php

declare(strict_types=1);

use Latte\Runtime as LR;

/** source: D:\xampp\htdocs\alexy-os/wp-content/themes/wp-fasty/wp-fasty-ym/views/archive/archive.latte */
final class Template_5594c0d0dd extends Latte\Runtime\Template
{
	public const Source = 'D:\\xampp\\htdocs\\alexy-os/wp-content/themes/wp-fasty/wp-fasty-ym/views/archive/archive.latte';


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo '<section class="archive">
    <header class="archive-header">
        <div class="container">
            <h1 class="archive-title">';
		echo LR\Filters::escapeHtmlText($archive['title']) /* line 12 */;
		echo '</h1>
            
';
		if (isset($archive['description']) && $archive['description']) /* line 14 */ {
			echo '                <div class="archive-description">
                    ';
			echo $archive['description'] /* line 16 */;
			echo '
                </div>
';
		}
		echo '        </div>
    </header>

    <div class="archive-content">
        <div class="container">
';
		if (!empty($posts)) /* line 24 */ {
			echo '                <div class="posts-grid">
';
			foreach ($posts as $post) /* line 26 */ {
				echo '                        <article class="post-card">
                            <a href="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($post['url'])) /* line 28 */;
				echo '" class="post-card-link">
';
				if (isset($post['thumbnail']) && $post['thumbnail']) /* line 29 */ {
					echo '                                    <figure class="post-card-thumbnail">
                                        <img src="';
					echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($post['thumbnail']['url'])) /* line 31 */;
					echo '" alt="';
					echo LR\Filters::escapeHtmlAttr($post['thumbnail']['alt']) /* line 31 */;
					echo '" class="post-card-image">
                                    </figure>
';
				}
				echo '                            </a>
                            
                            <div class="post-card-content">
                                <header class="post-card-header">
';
				if (isset($post['categories']) && $post['categories']) /* line 38 */ {
					echo '                                        <div class="post-card-categories">
';
					foreach ($post['categories'] as $category) /* line 40 */ {
						echo '                                                <a href="';
						echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($category['url'])) /* line 41 */;
						echo '" class="post-card-category">';
						echo LR\Filters::escapeHtmlText($category['name']) /* line 41 */;
						echo '</a>
';

					}

					echo '                                        </div>
';
				}
				echo '                                    
                                    <h2 class="post-card-title">
                                        <a href="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($post['url'])) /* line 47 */;
				echo '">';
				echo LR\Filters::escapeHtmlText($post['title']) /* line 47 */;
				echo '</a>
                                    </h2>
                                </header>
                                
';
				if (isset($post['excerpt']) && $post['excerpt']) /* line 51 */ {
					echo '                                    <div class="post-card-excerpt">
                                        ';
					echo $post['excerpt'] /* line 53 */;
					echo '
                                    </div>
';
				}
				echo '                                
                                <footer class="post-card-footer">
                                    <div class="post-card-meta">
';
				if (isset($post['date']) && $post['date']) /* line 59 */ {
					echo '                                            <time datetime="';
					echo LR\Filters::escapeHtmlAttr($post['date']['formatted']) /* line 60 */;
					echo '" class="post-card-date">
                                                ';
					echo LR\Filters::escapeHtmlText($post['date']['display']) /* line 61 */;
					echo '
                                            </time>
';
				}
				echo '                                        
';
				if (isset($post['author']) && $post['author']) /* line 65 */ {
					echo '                                            <span class="post-card-author">
                                                By <a href="';
					echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($post['author']['url'])) /* line 67 */;
					echo '">';
					echo LR\Filters::escapeHtmlText($post['author']['name']) /* line 67 */;
					echo '</a>
                                            </span>
';
				}
				echo '                                    </div>
                                    
                                    <a href="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($post['url'])) /* line 72 */;
				echo '" class="post-card-read-more">
                                        Read More <span class="read-more-icon">→</span>
                                    </a>
                                </footer>
                            </div>
                        </article>
';

			}

			echo '                </div>
                
';
			if (isset($pagination)) /* line 81 */ {
				echo '                    <nav class="pagination">
';
				if (isset($pagination['prev_url']) && $pagination['prev_url']) /* line 83 */ {
					echo '                            <a href="';
					echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($pagination['prev_url'])) /* line 84 */;
					echo '" class="pagination-link pagination-prev">
                                <span class="pagination-arrow">←</span> Previous
                            </a>
';
				}
				echo '                        
                        <div class="pagination-numbers">
';
				foreach ($pagination['pages'] as $page) /* line 90 */ {
					if ($page['is_current']) /* line 91 */ {
						echo '                                    <span class="pagination-number pagination-current">';
						echo LR\Filters::escapeHtmlText($page['number']) /* line 92 */;
						echo '</span>
';
					} else /* line 93 */ {
						echo '                                    <a href="';
						echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($page['url'])) /* line 94 */;
						echo '" class="pagination-number">';
						echo LR\Filters::escapeHtmlText($page['number']) /* line 94 */;
						echo '</a>
';
					}

				}

				echo '                        </div>
                        
';
				if (isset($pagination['next_url']) && $pagination['next_url']) /* line 99 */ {
					echo '                            <a href="';
					echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($pagination['next_url'])) /* line 100 */;
					echo '" class="pagination-link pagination-next">
                                Next <span class="pagination-arrow">→</span>
                            </a>
';
				}
				echo '                    </nav>
';
			}
		} else /* line 106 */ {
			echo '                <div class="no-posts">
                    <p>No posts found.</p>
                </div>
';
		}
		echo '        </div>
    </div>
</section> ';
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['post' => '26', 'category' => '40', 'page' => '90'], $this->params) as $ʟ_v => $ʟ_l) {
				trigger_error("Variable \$$ʟ_v overwritten in foreach on line $ʟ_l");
			}
		}
		return get_defined_vars();
	}
}
