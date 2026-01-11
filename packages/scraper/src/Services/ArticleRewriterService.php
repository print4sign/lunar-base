<?php

namespace Lunar\Scraper\Services;

use Lunar\Models\Article;

class ArticleRewriterService
{
    public function __construct(
        protected ClaudeClient $claude
    ) {}

    public function rewrite(Article $article, array $locales = ['nl', 'en']): Article
    {
        // Backup original content if not already backed up
        if (empty($article->original_content)) {
            $article->original_content = [
                'title' => $article->title,
                'body' => $article->body,
                'excerpt' => $article->excerpt,
            ];
        }

        $rewrittenBody = [];
        $rewrittenExcerpt = [];

        foreach ($locales as $locale) {
            $originalBody = $article->body[$locale] ?? null;
            $originalExcerpt = $article->excerpt[$locale] ?? null;

            if ($originalBody) {
                $rewrittenBody[$locale] = $this->rewriteContent($originalBody, $locale);
            }

            if ($originalExcerpt) {
                $rewrittenExcerpt[$locale] = $this->rewriteContent($originalExcerpt, $locale, 'excerpt');
            }
        }

        $article->update([
            'body' => array_merge($article->body ?? [], $rewrittenBody),
            'excerpt' => array_merge($article->excerpt ?? [], $rewrittenExcerpt),
            'original_content' => $article->original_content,
        ]);

        return $article->fresh();
    }

    protected function rewriteContent(string $content, string $locale, string $type = 'body'): string
    {
        $languageName = match ($locale) {
            'nl' => 'Dutch',
            'en' => 'English',
            'de' => 'German',
            default => 'Dutch',
        };

        $lengthGuidance = $type === 'excerpt'
            ? 'Keep it concise, around 1-2 sentences.'
            : 'Maintain similar length and structure to the original.';

        $prompt = <<<PROMPT
Rewrite the following article content so that it:
- Is unique and does not infringe copyright
- Contains the same information and meaning
- Is written professionally and clearly
- Is suitable for a print/sign webshop called "Drukhoek"
- Replaces any Probo-specific references with generic terms or "Drukhoek"
- Maintains the same formatting (headings, lists, paragraphs)
- Is written in {$languageName}
- {$lengthGuidance}

IMPORTANT: Return ONLY the rewritten text, no explanations or commentary.

Original content:
{$content}
PROMPT;

        return $this->claude->complete($prompt);
    }

    public function rewriteBatch(iterable $articles, array $locales = ['nl', 'en']): int
    {
        $count = 0;

        foreach ($articles as $article) {
            $this->rewrite($article, $locales);
            $article->update(['status' => 'rewritten']);
            $count++;
        }

        return $count;
    }
}
