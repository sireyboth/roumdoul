<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Portfolio\Note;
use App\Portfolio\Portfolio;
use App\Portfolio\PortfolioContext;
use App\Portfolio\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PortfolioController extends Controller
{
    /** /portfolio/{slug} → the visitor's language if offered, else the default. */
    public function root(Request $request, Portfolio $portfolio): RedirectResponse
    {
        $locale = $request->getPreferredLanguage($portfolio->locales()) ?? $portfolio->defaultLocale();

        return redirect()->route('portfolio.home', ['portfolio' => $portfolio->slug, 'locale' => $locale]);
    }

    public function home(Portfolio $portfolio, string $locale)
    {
        return $this->render($portfolio, $locale, 'home', [], [
            'featured' => $portfolio->featuredProjects(),
            'projects' => $portfolio->projects(),
        ]);
    }

    public function about(Portfolio $portfolio, string $locale)
    {
        return $this->render($portfolio, $locale, 'about');
    }

    public function work(Request $request, Portfolio $portfolio, string $locale)
    {
        $category = in_array($request->query('category'), $portfolio->disciplineKeys(), true) ? $request->query('category') : null;
        $status = in_array($request->query('status'), Project::STATUSES, true) ? $request->query('status') : null;
        $view = $request->query('view') === 'gallery' ? 'gallery' : 'index';

        return $this->render($portfolio, $locale, 'work', [], [
            'projects' => $portfolio->projects(),
            'activeCategory' => $category,
            'activeStatus' => $status,
            'view' => $view,
        ]);
    }

    public function project(Portfolio $portfolio, string $locale, string $project)
    {
        $model = $portfolio->project($project) ?? abort(404);
        $all = $portfolio->projects()->values();
        $index = $all->search(fn (Project $p) => $p->slug === $model->slug);

        return $this->render($portfolio, $locale, 'work.show', ['project' => $model->slug], [
            'project' => $model,
            'related' => $portfolio->relatedProjects($model),
            'next' => $all[($index + 1) % $all->count()],
            'position' => $index + 1,
            'total' => $all->count(),
        ]);
    }

    public function expertise(Portfolio $portfolio, string $locale)
    {
        return $this->render($portfolio, $locale, 'expertise', [], [
            'projects' => $portfolio->projects(),
        ]);
    }

    public function notes(Request $request, Portfolio $portfolio, string $locale)
    {
        $type = in_array($request->query('type'), Note::TYPES, true) ? $request->query('type') : null;

        return $this->render($portfolio, $locale, 'notes', [], [
            'notes' => $portfolio->notes(),
            'activeType' => $type,
        ]);
    }

    public function note(Portfolio $portfolio, string $locale, string $note)
    {
        $model = $portfolio->note($note) ?? abort(404);

        return $this->render($portfolio, $locale, 'notes.show', ['note' => $model->slug], [
            'note' => $model,
            'related' => collect($model->get('related', []))->map(fn ($slug) => $portfolio->project($slug))->filter()->values(),
        ]);
    }

    public function contact(Portfolio $portfolio, string $locale)
    {
        return $this->render($portfolio, $locale, 'contact', [], [
            // Encrypted render time for the "too fast to be human" check.
            'formToken' => Crypt::encryptString((string) now()->timestamp),
        ]);
    }

    /** Serves an un-optimized source image from portfolios/{slug}/media. */
    public function media(Portfolio $portfolio, string $path): BinaryFileResponse
    {
        $file = $portfolio->media()->sourcePath($path) ?? abort(404);

        return response()->file($file, [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Type' => str_ends_with(strtolower($file), '.svg') ? 'image/svg+xml' : (mime_content_type($file) ?: 'application/octet-stream'),
        ]);
    }

    protected function render(Portfolio $portfolio, string $locale, string $page, array $params = [], array $data = [])
    {
        abort_unless($portfolio->supports($locale), 404);

        App::setLocale($locale);

        $pf = new PortfolioContext($portfolio, $locale, $page, $params);
        View::share('pf', $pf);

        return response()->view("portfolio.pages.{$page}", $data + ['pf' => $pf]);
    }
}
