
import { Article, ArticleHeader } from "@uikits/ui8px/core/source/clsx/components/article";

function Test() {
  return (
    <article data-slot="article" className="article">
      <header data-slot="article-header" className="article-header">
        <h2>Title</h2>
      </header>
    </article>
  );
}
