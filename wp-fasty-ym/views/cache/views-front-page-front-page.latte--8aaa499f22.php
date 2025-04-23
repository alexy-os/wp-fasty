<?php

declare(strict_types=1);

use Latte\Runtime as LR;

/** source: D:\xampp\htdocs\alexy-os/wp-content/themes/wp-fasty/wp-fasty-ym/views/front-page/front-page.latte */
final class Template_8aaa499f22 extends Latte\Runtime\Template
{
	public const Source = 'D:\\xampp\\htdocs\\alexy-os/wp-content/themes/wp-fasty/wp-fasty-ym/views/front-page/front-page.latte';


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo '<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">';
		echo LR\Filters::escapeHtmlText($site['title']) /* line 13 */;
		echo '</h1>
            <p class="hero-description">';
		echo LR\Filters::escapeHtmlText($site['description']) /* line 14 */;
		echo '</p>
            
            <div class="hero-actions">
                <a href="#featured" class="button button-primary button-rounded">Explore</a>
                <a href="#about" class="button button-secondary button-rounded">Learn More</a>
            </div>
        </div>
    </div>
</section>

';
		if (isset($page) && !empty($page['content'])) /* line 24 */ {
			echo '    <section id="about" class="about-section">
        <div class="container">
            <div class="about-content">
                ';
			echo $page['content'] /* line 28 */;
			echo '
            </div>
        </div>
    </section>
';
		}
		echo "\n";
		if (isset($posts) && !empty($posts)) /* line 34 */ {
			echo '    <section id="featured" class="featured-posts">
        <div class="container">
            <header class="section-header">
                <h2 class="section-title">Featured Posts</h2>
                <p class="section-description">Discover our latest articles and updates</p>
            </header>
            
            <div class="featured-grid">
';
			foreach ($posts as $post) /* line 43 */ {
				echo '                    <article class="card card-featured">
                        <a href="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($post['url'])) /* line 45 */;
				echo '" class="card-link">
';
				if (isset($post['thumbnail'])) /* line 46 */ {
					echo '                                <figure class="card-thumbnail">
                                    <img src="';
					echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($post['thumbnail']['url'])) /* line 48 */;
					echo '" alt="';
					echo LR\Filters::escapeHtmlAttr($post['thumbnail']['alt']) /* line 48 */;
					echo '" class="card-image">
                                </figure>
';
				}
				echo '                        </a>
                        
                        <header class="card-header">
';
				if (isset($post['categories'])) /* line 54 */ {
					echo '                                <div class="card-categories">
';
					foreach ($post['categories'] as $category) /* line 56 */ {
						echo '                                        <a href="';
						echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($category['url'])) /* line 57 */;
						echo '" class="card-category">';
						echo LR\Filters::escapeHtmlText($category['name']) /* line 57 */;
						echo '</a>
';

					}

					echo '                                </div>
';
				}
				echo '                            
                            <h3 class="card-title">
                                <a href="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($post['url'])) /* line 63 */;
				echo '">';
				echo LR\Filters::escapeHtmlText($post['title']) /* line 63 */;
				echo '</a>
                            </h3>
                        </header>
                        
                        <div class="card-content">
';
				if (isset($post['excerpt'])) /* line 68 */ {
					echo '                                <div class="card-excerpt">
                                    ';
					echo $post['excerpt'] /* line 70 */;
					echo '
                                </div>
';
				}
				echo '                        </div>
                        
                        <footer class="card-footer">
                            <div class="card-meta">
';
				if (isset($post['date'])) /* line 77 */ {
					echo '                                    <time datetime="';
					echo LR\Filters::escapeHtmlAttr($post['date']['formatted']) /* line 78 */;
					echo '" class="card-date">
                                        ';
					echo LR\Filters::escapeHtmlText($post['date']['display']) /* line 79 */;
					echo '
                                    </time>
';
				}
				echo '                            </div>
                            
                            <a href="';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($post['url'])) /* line 84 */;
				echo '" class="card-read-more">
                                Read More <span class="read-more-icon">→</span>
                            </a>
                        </footer>
                    </article>
';

			}

			echo '            </div>
            
            <div class="featured-action">
                <a href="';
			echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($site['url'])) /* line 93 */;
			echo '/blog" class="button button-primary button-rounded">View All Posts</a>
            </div>
        </div>
    </section>
';
		}
		echo "\n";
		do_action('front_page_sections');
		echo ' ';
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['post' => '43', 'category' => '56'], $this->params) as $ʟ_v => $ʟ_l) {
				trigger_error("Variable \$$ʟ_v overwritten in foreach on line $ʟ_l");
			}
		}
		return get_defined_vars();
	}
}
