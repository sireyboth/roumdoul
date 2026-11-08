<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Portfolio\PortfolioRepository;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/shop'), 'priority' => '0.9'],
            ['loc' => url('/about'), 'priority' => '0.5'],
            ['loc' => url('/contact'), 'priority' => '0.5'],
            ['loc' => url('/feedback'), 'priority' => '0.3'],
            ['loc' => url('/privacy'), 'priority' => '0.2'],
            ['loc' => url('/terms'), 'priority' => '0.2'],
        ];

        foreach (Category::all(['slug']) as $category) {
            $urls[] = ['loc' => url('/shop/'.$category->slug), 'priority' => '0.7'];
        }

        foreach (Service::where('is_active', true)->get(['slug', 'updated_at']) as $service) {
            $urls[] = [
                'loc' => url('/service/'.$service->slug),
                'priority' => '0.8',
                'lastmod' => $service->updated_at?->toAtomString(),
            ];
        }

        foreach (app(PortfolioRepository::class)->all() as $portfolio) {
            foreach ($portfolio->locales() as $locale) {
                $params = ['portfolio' => $portfolio->slug, 'locale' => $locale];

                foreach (['home' => '0.8', 'about' => '0.6', 'work' => '0.7', 'expertise' => '0.5', 'notes' => '0.5', 'contact' => '0.4'] as $page => $priority) {
                    $urls[] = ['loc' => route("portfolio.{$page}", $params), 'priority' => $priority];
                }

                foreach ($portfolio->projects() as $project) {
                    $urls[] = ['loc' => route('portfolio.work.show', $params + ['project' => $project->slug]), 'priority' => '0.6'];
                }

                foreach ($portfolio->notes()->reject->isDraft() as $note) {
                    $urls[] = [
                        'loc' => route('portfolio.notes.show', $params + ['note' => $note->slug]),
                        'priority' => '0.5',
                        'lastmod' => $note->date()?->toAtomString(),
                    ];
                }
            }
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
