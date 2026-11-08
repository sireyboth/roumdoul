<?php

namespace Tests\Feature;

use App\Models\PortfolioMessage;
use App\Portfolio\PortfolioRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PortfolioTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/portfolio/ngoun-leng';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['portfolio.editor_hints' => false]);
    }

    public function test_root_redirects_to_the_visitors_language(): void
    {
        $this->get(self::BASE)->assertRedirect(self::BASE.'/en');
        $this->get(self::BASE, ['Accept-Language' => 'km-KH,km;q=0.9'])->assertRedirect(self::BASE.'/km');
    }

    public function test_every_page_renders_in_both_languages(): void
    {
        foreach (['en', 'km'] as $locale) {
            foreach (['', '/about', '/work', '/expertise', '/notes', '/contact'] as $page) {
                $this->get(self::BASE."/{$locale}{$page}")
                    ->assertOk()
                    ->assertSee('<html lang="'.$locale.'"', false)
                    ->assertSee('hreflang="en"', false)
                    ->assertSee('hreflang="km"', false);
            }
        }
    }

    public function test_home_shows_the_brief_copy_in_each_language(): void
    {
        $this->get(self::BASE.'/en')->assertSee('Different disciplines')->assertSee('Have an idea worth building'); // "?" is wrapped in the accent span
        $this->get(self::BASE.'/km')->assertSee('វិស័យខុសគ្នា')->assertSee('ទំព័រដើម');
    }

    public function test_every_project_has_a_working_case_study_in_both_languages(): void
    {
        $projects = app(PortfolioRepository::class)->find('ngoun-leng')->projects();
        $this->assertCount(11, $projects);

        foreach ($projects as $project) {
            foreach (['en', 'km'] as $locale) {
                $this->get(self::BASE."/{$locale}/work/{$project->slug}")
                    ->assertOk()
                    ->assertSee($project->title($locale));
            }
        }
    }

    public function test_khmer_case_study_falls_back_to_english_with_a_notice(): void
    {
        $this->get(self::BASE.'/km/work/digital-invitations')
            ->assertOk()
            ->assertSee('វេទិកាធៀបអញ្ជើញឌីជីថល')
            ->assertSee(__('portfolio.case.only_in', [], 'km'))
            ->assertSee('lang="en"', false);
    }

    public function test_unknown_things_are_404(): void
    {
        $this->get('/portfolio/nobody/en')->assertNotFound();
        $this->get(self::BASE.'/fr')->assertNotFound();
        $this->get(self::BASE.'/en/work/does-not-exist')->assertNotFound();
        $this->get(self::BASE.'/en/notes/_example')->assertNotFound();
    }

    public function test_work_filter_works_without_javascript(): void
    {
        $html = $this->get(self::BASE.'/en/work?category=creative')->assertOk()->getContent();

        // Creative rows visible, technology rows rendered but hidden (JS can reveal them without a reload).
        $this->assertMatchesRegularExpression('/data-category="creative" data-status="[a-z_]+"\s*>/', $html);
        $this->assertMatchesRegularExpression('/data-category="technology" data-status="[a-z_]+"\s+hidden/', $html);
    }

    public function test_editor_notes_and_drafts_are_hidden_from_visitors(): void
    {
        $this->get(self::BASE.'/en/about')->assertDontSee('pf-editor-note', false)->assertDontSee('placeholder timeline');
        $this->get(self::BASE.'/en/work/roumdoul')->assertDontSee('pf-editor-note', false);
    }

    public function test_contact_form_saves_the_message(): void
    {
        $this->post(self::BASE.'/en/contact', $this->validMessage())
            ->assertRedirect(self::BASE.'/en/contact#contact-form')
            ->assertSessionHas('portfolio_contact_sent');

        $this->assertDatabaseHas('portfolio_messages', [
            'portfolio' => 'ngoun-leng',
            'locale' => 'en',
            'email' => 'sok@example.com',
            'subject' => 'A project',
        ]);

        $this->get(self::BASE.'/en/contact')->assertSee('Message received.');
    }

    public function test_contact_form_validates_input(): void
    {
        $this->post(self::BASE.'/en/contact', ['name' => '', 'email' => 'not-an-email', 'message' => 'short'] + $this->validMessage(['name' => '', 'email' => 'not-an-email', 'message' => 'short']))
            ->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame(0, PortfolioMessage::count());
    }

    public function test_contact_form_rejects_the_honeypot_and_instant_submissions(): void
    {
        $this->post(self::BASE.'/en/contact', $this->validMessage(['website' => 'http://spam.test']))
            ->assertSessionHasErrors('website');

        $this->post(self::BASE.'/en/contact', $this->validMessage(['_started' => Crypt::encryptString((string) now()->timestamp)]))
            ->assertSessionHasErrors('message');

        $this->post(self::BASE.'/en/contact', $this->validMessage(['_started' => 'tampered']))
            ->assertSessionHasErrors('message');

        $this->assertSame(0, PortfolioMessage::count());
    }

    public function test_khmer_contact_errors_are_in_khmer(): void
    {
        $this->post(self::BASE.'/km/contact', $this->validMessage(['name' => '']))
            ->assertSessionHasErrors(['name' => 'សូមបំពេញ ឈ្មោះរបស់អ្នក។']);
    }

    public function test_media_route_serves_files_and_blocks_traversal(): void
    {
        $this->get(self::BASE.'/media/brand/favicon.svg')->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->get(self::BASE.'/media/README.md')->assertNotFound();
        $this->get(self::BASE.'/media/../portfolio.php')->assertNotFound();
        $this->get(self::BASE.'/media/..%2Fportfolio.php')->assertNotFound();
    }

    public function test_sitemap_lists_portfolio_pages(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url(self::BASE.'/en/work/roumdoul'), false)
            ->assertSee(url(self::BASE.'/km/about'), false);
    }

    public function test_a_second_portfolio_works_by_adding_a_folder(): void
    {
        $dir = storage_path('framework/testing/portfolios');
        File::deleteDirectory($dir);
        File::ensureDirectoryExists("{$dir}/jane-doe/content");
        File::ensureDirectoryExists("{$dir}/jane-doe/projects/thing");
        File::put("{$dir}/jane-doe/portfolio.php", "<?php return ['name' => 'Jane Doe', 'locales' => ['en'], 'disciplines' => ['design'], 'featured' => ['thing']];");
        File::put("{$dir}/jane-doe/content/en.php", "<?php return ['role' => 'Designer', 'disciplines' => ['design' => ['title' => 'Design']]];");
        File::put("{$dir}/jane-doe/projects/thing/project.php", "<?php return ['category' => 'design', 'status' => 'completed', 'cover' => ['src' => 'projects/thing/cover.jpg', 'ratio' => '16/10']];");
        File::put("{$dir}/jane-doe/projects/thing/en.php", "<?php return ['title' => 'The Thing', 'summary' => 'A thing.'];");

        config(['portfolio.path' => $dir]);
        $this->app->forgetInstance(PortfolioRepository::class);
        $this->app->singleton(PortfolioRepository::class, fn () => new PortfolioRepository($dir));

        try {
            $this->get('/portfolio/jane-doe/en')->assertOk()->assertSee('Jane Doe')->assertSee('The Thing');
            // No media at all in this fixture → every image slot shows the honest placeholder.
            $this->get('/portfolio/jane-doe/en/work/thing')->assertOk()->assertSee('The Thing')->assertSee('Image required');
            $this->get('/portfolio/jane-doe/km')->assertNotFound(); // only offers English

            // Editor hints: hidden from visitors, shown while editing (with the file to add).
            $this->get('/portfolio/jane-doe/en/work/thing')->assertDontSee('pf-editor-note', false)->assertDontSee('media/projects/thing/cover.jpg');
            config(['portfolio.editor_hints' => true]);
            $this->get('/portfolio/jane-doe/en/work/thing')->assertSee('pf-editor-note', false)->assertSee('media/projects/thing/cover.jpg');
        } finally {
            File::deleteDirectory($dir);
        }
    }

    private function validMessage(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sok Dara',
            'email' => 'sok@example.com',
            'subject' => 'A project',
            'message' => 'I would like to talk about a business system.',
            'website' => '',
            '_started' => Crypt::encryptString((string) now()->subMinute()->timestamp),
        ], $overrides);
    }
}
