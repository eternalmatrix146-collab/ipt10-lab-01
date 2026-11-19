<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Category;
use App\Models\Order;
use App\Services\CartService;
use Phantom\Database\DB;
use Throwable;

/**
 * Rook's help center: vetted answers about store policies and how-tos, kept in
 * resources/knowledge/faq.md. Searched with simple keyword scoring (title words
 * weigh most, then the keywords line, then the body) — no embeddings needed for
 * a couple of dozen entries.
 *
 * {placeholders} are filled from config, so fees and limits always match checkout.
 */
final class KnowledgeBase
{
    private const STOPWORDS = [
        'a', 'an', 'the', 'i', 'me', 'my', 'is', 'are', 'do', 'does', 'can', 'could', 'how', 'what', 'when', 'where',
        'why', 'which', 'you', 'your', 'to', 'of', 'for', 'in', 'on', 'it', 'and', 'or', 'if', 'be', 'with', 'at',
        'this', 'that', 'there', 'please', 'tell', 'about', 'much', 'many', 'get', 'have', 'has', 'will', 'would',
    ];

    /** @var array<int, array{title: string, keywords: string, body: string}>|null */
    private ?array $entries = null;

    public function __construct(private string $file)
    {
    }

    /**
     * @return array<int, array{topic: string, answer: string}> best matches first (empty if nothing relevant)
     */
    public function search(string $query, int $limit = 3): array
    {
        $terms = self::terms($query);
        // Whole-phrase form of the query, so keyword phrases made only of common words
        // ("what can you do", "who are you") still match.
        $phrase = ' ' . trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($query))) . ' ';

        $scored = [];
        foreach ($this->entries() as $entry) {
            $title = self::terms($entry['title']);
            $keywords = self::terms($entry['keywords']);
            $body = self::terms($entry['body']);
            $score = 0;
            foreach ($terms as $term) {
                $score += 5 * (int) in_array($term, $title, true)
                    + 3 * (int) in_array($term, $keywords, true)
                    + (int) in_array($term, $body, true);
            }
            foreach (explode(',', mb_strtolower($entry['keywords'])) as $keyword) {
                $keyword = trim($keyword);
                if (mb_strlen($keyword) >= 3 && str_contains($phrase, " {$keyword} ")) {
                    $score += 6;
                }
            }
            if ($score > 0) {
                $scored[] = [$score, $entry];
            }
        }
        if ($scored === []) {
            return [];
        }

        usort($scored, static fn (array $a, array $b) => $b[0] <=> $a[0]);
        // Drop weak runners-up (a single stray word match) so the model gets only relevant entries.
        $floor = $scored[0][0] / 3;
        $scored = array_filter($scored, static fn (array $s) => $s[0] >= $floor);
        return array_map(
            static fn (array $s) => ['topic' => $s[1]['title'], 'answer' => $s[1]['body']],
            array_slice($scored, 0, max(1, $limit))
        );
    }

    /** @return string[] entry titles, for "what can I ask?" */
    public function topics(): array
    {
        return array_column($this->entries(), 'title');
    }

    /** @return array<int, array{title: string, keywords: string, body: string}> */
    private function entries(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        $text = (string) @file_get_contents($this->file);
        $text = (string) preg_replace('/<!--.*?-->/s', '', $text);
        $text = strtr($text, $this->placeholders());

        $this->entries = [];
        foreach (preg_split('/^## /m', $text) ?: [] as $i => $chunk) {
            if ($i === 0 || trim($chunk) === '') {
                continue; // the part before the first entry is the file header
            }
            $lines = explode("\n", trim($chunk));
            $title = trim(array_shift($lines));
            $keywords = '';
            if (isset($lines[0]) && str_starts_with(strtolower(trim($lines[0])), 'keywords:')) {
                $keywords = trim(substr(trim(array_shift($lines)), strlen('keywords:')));
            }
            $this->entries[] = ['title' => $title, 'keywords' => $keywords, 'body' => trim(implode("\n", $lines))];
        }
        return $this->entries;
    }

    /** @return array<string, string> */
    private function placeholders(): array
    {
        $model = (string) config('services.nvidia.model', '');
        return $this->catalog() + [
            '{model}' => str_contains($model, 'nemotron-3-ultra') ? 'Nemotron 3 Ultra' : (basename($model) ?: 'Nemotron'),
            '{shipping_fee}' => peso((float) config('app.shipping.flat_fee', 150)),
            '{free_shipping_over}' => peso((float) config('app.shipping.free_over', 3000)),
            '{max_qty}' => (string) CartService::MAX_QTY_PER_ITEM,
            '{currencies}' => implode(', ', (array) config('app.currency.supported', ['PHP'])),
            '{payment_methods}' => implode(', ', Order::PAYMENT_METHODS),
            '{statuses}' => implode(', ', Order::STATUSES),
            '{rate_refresh_hours}' => (string) max(1, intdiv((int) config('services.frankfurter.cache_minutes', 360), 60)),
            '{low_stock}' => '5',
        ];
    }

    /**
     * Live catalog facts, so "what do you sell?" follows whatever the admins change.
     *
     * @return array<string, string>
     */
    private function catalog(): array
    {
        try {
            $lines = array_map(
                static fn (Category $c) => "- {$c->name}: {$c->description} ({$c->productCount()} products)",
                Category::ordered()
            );
            $stats = DB::connection()->selectOne('SELECT COUNT(*) AS n, MIN(price) AS lo, MAX(price) AS hi FROM products') ?? [];
            return [
                '{catalog_overview}' => $lines !== [] ? implode("\n", $lines) : '- (no categories yet)',
                '{product_count}' => (string) (int) ($stats['n'] ?? 0),
                '{price_min}' => peso((float) ($stats['lo'] ?? 0)),
                '{price_max}' => peso((float) ($stats['hi'] ?? 0)),
            ];
        } catch (Throwable) {
            // Database unavailable: keep the entry useful instead of failing the whole search.
            return [
                '{catalog_overview}' => '- See the Shop page for the current categories.',
                '{product_count}' => 'many',
                '{price_min}' => 'a few hundred pesos',
                '{price_max}' => 'a few thousand pesos',
            ];
        }
    }

    /** @return string[] lower-cased words without stopwords, naive plurals folded ("orders" -> "order") */
    private static function terms(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text)) ?: [];
        $out = [];
        foreach ($words as $word) {
            if ($word === '' || in_array($word, self::STOPWORDS, true)) {
                continue;
            }
            $out[] = mb_strlen($word) > 3 && str_ends_with($word, 's') && !str_ends_with($word, 'ss') ? mb_substr($word, 0, -1) : $word;
        }
        return array_values(array_unique($out));
    }
}
