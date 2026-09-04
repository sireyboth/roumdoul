<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
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

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
