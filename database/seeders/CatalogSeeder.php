<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Seed placeholder categories and services for the ROUMDOUL digital-services shop.
     */
    public function run(): void
    {
        $catalog = [
            [
                'name_en' => 'AI Tools',
                'name_km' => 'ឧបករណ៍ AI',
                'icon' => 'sparkles',
                'services' => [
                    [
                        'name_en' => 'Google Gemini Pro',
                        'name_km' => 'Google Gemini Pro',
                        'short' => 'Advanced AI assistant with 2TB storage and premium features.',
                        'description' => 'Get full access to Google\'s most capable AI model, integrated across Gmail, Docs, and Drive, plus 2TB of cloud storage. Perfect for professionals and students who want a serious productivity boost.',
                        'price' => 8.00,
                        'featured' => true,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 8.00],
                            ['label' => '6 Months', 'price' => 42.00],
                            ['label' => '12 Months', 'price' => 75.00],
                        ],
                    ],
                    [
                        'name_en' => 'ChatGPT Plus',
                        'name_km' => 'ChatGPT Plus',
                        'short' => 'Priority access to GPT-5 with faster responses and no limits.',
                        'description' => 'Unlock GPT-5, image generation, advanced data analysis, and priority access during peak times. A must-have for writers, developers, and researchers.',
                        'price' => 9.00,
                        'featured' => true,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 9.00],
                            ['label' => '3 Months', 'price' => 25.00],
                            ['label' => '12 Months', 'price' => 90.00],
                        ],
                    ],
                    [
                        'name_en' => 'Midjourney',
                        'name_km' => 'Midjourney',
                        'short' => 'Generate stunning AI art and illustrations in seconds.',
                        'description' => 'Full access to Midjourney\'s image generation engine — ideal for designers, marketers, and content creators who need high-quality visuals fast.',
                        'price' => 10.00,
                        'featured' => false,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 10.00],
                            ['label' => '12 Months', 'price' => 96.00],
                        ],
                    ],
                    [
                        'name_en' => 'Perplexity Pro',
                        'name_km' => 'Perplexity Pro',
                        'short' => 'AI-powered research and search assistant.',
                        'description' => 'Get unlimited Pro searches, file uploads, and access to top AI models for research, fact-checking, and writing.',
                        'price' => 7.00,
                        'featured' => false,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 7.00],
                            ['label' => '12 Months', 'price' => 70.00],
                        ],
                    ],
                ],
            ],
            [
                'name_en' => 'Design & Creative',
                'name_km' => 'ការរចនានិងគំនិតច្នៃប្រឌិត',
                'icon' => 'paint-brush',
                'services' => [
                    [
                        'name_en' => 'Envato Elements',
                        'name_km' => 'Envato Elements',
                        'short' => 'Unlimited downloads of templates, graphics, fonts, and more.',
                        'description' => 'One subscription for unlimited creative assets — WordPress themes, video templates, stock photos, fonts, and graphics, all with a commercial license.',
                        'price' => 12.00,
                        'featured' => true,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 12.00],
                            ['label' => '12 Months', 'price' => 110.00],
                        ],
                    ],
                    [
                        'name_en' => 'Canva Pro',
                        'name_km' => 'Canva Pro',
                        'short' => 'Premium templates, backgrounds, and brand kit tools.',
                        'description' => 'Design like a pro with premium templates, background remover, brand kits, and unlimited storage for your creative projects.',
                        'price' => 6.00,
                        'featured' => true,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 6.00],
                            ['label' => '12 Months', 'price' => 55.00],
                        ],
                    ],
                    [
                        'name_en' => 'Adobe Creative Cloud',
                        'name_km' => 'Adobe Creative Cloud',
                        'short' => 'Full access to Photoshop, Illustrator, Premiere Pro, and more.',
                        'description' => 'The complete Adobe suite — Photoshop, Illustrator, Premiere Pro, After Effects, and 20+ apps for design, video, and photography.',
                        'price' => 25.00,
                        'featured' => false,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 25.00],
                            ['label' => '12 Months', 'price' => 240.00],
                        ],
                    ],
                    [
                        'name_en' => 'Freepik Premium',
                        'name_km' => 'Freepik Premium',
                        'short' => 'Millions of vectors, photos, and PSD files.',
                        'description' => 'Download unlimited premium vectors, stock photos, icons, and PSD mockups for all your design projects.',
                        'price' => 9.00,
                        'featured' => false,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 9.00],
                            ['label' => '12 Months', 'price' => 85.00],
                        ],
                    ],
                ],
            ],
            [
                'name_en' => 'Streaming',
                'name_km' => 'ស្ទ្រីមមីង',
                'icon' => 'play',
                'services' => [
                    [
                        'name_en' => 'Netflix Premium',
                        'name_km' => 'Netflix Premium',
                        'short' => '4K Ultra HD streaming on up to 4 screens.',
                        'description' => 'Watch unlimited movies and series in stunning 4K Ultra HD, with support for up to 4 simultaneous screens.',
                        'price' => 5.00,
                        'featured' => true,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 5.00],
                            ['label' => '3 Months', 'price' => 14.00],
                            ['label' => '12 Months', 'price' => 50.00],
                        ],
                    ],
                    [
                        'name_en' => 'Spotify Premium',
                        'name_km' => 'Spotify Premium',
                        'short' => 'Ad-free music streaming with offline downloads.',
                        'description' => 'Enjoy ad-free music, unlimited skips, and offline downloads across all your devices.',
                        'price' => 3.50,
                        'featured' => false,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 3.50],
                            ['label' => '12 Months', 'price' => 36.00],
                        ],
                    ],
                    [
                        'name_en' => 'YouTube Premium',
                        'name_km' => 'YouTube Premium',
                        'short' => 'Ad-free videos, background play, and YouTube Music.',
                        'description' => 'Watch YouTube without ads, play videos in the background, and get YouTube Music Premium included.',
                        'price' => 4.00,
                        'featured' => false,
                        'plans' => [
                            ['label' => '1 Month', 'price' => 4.00],
                            ['label' => '12 Months', 'price' => 42.00],
                        ],
                    ],
                ],
            ],
            [
                'name_en' => 'Software & Licenses',
                'name_km' => 'កម្មវិធីនិងអាជ្ញាប័ណ្ណ',
                'icon' => 'cpu-chip',
                'services' => [
                    [
                        'name_en' => 'Windows 11 Pro',
                        'name_km' => 'Windows 11 Pro',
                        'short' => 'Genuine lifetime activation key.',
                        'description' => 'Genuine, lifetime Windows 11 Pro license key with instant digital delivery and activation support.',
                        'price' => 18.00,
                        'featured' => true,
                        'plans' => [
                            ['label' => 'Lifetime License', 'price' => 18.00],
                        ],
                    ],
                    [
                        'name_en' => 'Microsoft Office 365',
                        'name_km' => 'Microsoft Office 365',
                        'short' => 'Word, Excel, PowerPoint, and 1TB OneDrive storage.',
                        'description' => 'Full Microsoft 365 suite with Word, Excel, PowerPoint, Outlook, and 1TB of OneDrive cloud storage.',
                        'price' => 15.00,
                        'featured' => false,
                        'plans' => [
                            ['label' => '12 Months', 'price' => 15.00],
                            ['label' => 'Lifetime License', 'price' => 35.00],
                        ],
                    ],
                    [
                        'name_en' => 'Kaspersky Total Security',
                        'name_km' => 'Kaspersky Total Security',
                        'short' => 'Complete antivirus and internet security suite.',
                        'description' => 'Protect your devices with real-time antivirus, firewall, and privacy protection tools from Kaspersky.',
                        'price' => 10.00,
                        'featured' => false,
                        'plans' => [
                            ['label' => '1 Device / 1 Year', 'price' => 10.00],
                            ['label' => '3 Devices / 1 Year', 'price' => 18.00],
                        ],
                    ],
                ],
            ],
            [
                'name_en' => 'Gaming',
                'name_km' => 'ហ្គេម',
                'icon' => 'puzzle-piece',
                'services' => [
                    [
                        'name_en' => 'Steam Wallet Code',
                        'name_km' => 'Steam Wallet Code',
                        'short' => 'Top up your Steam wallet instantly.',
                        'description' => 'Instantly delivered Steam Wallet codes to top up your account and buy games, DLC, or in-game items.',
                        'price' => 10.00,
                        'featured' => true,
                        'plans' => [
                            ['label' => '$10 Credit', 'price' => 10.00],
                            ['label' => '$25 Credit', 'price' => 25.00],
                            ['label' => '$50 Credit', 'price' => 50.00],
                        ],
                    ],
                    [
                        'name_en' => 'PUBG Mobile UC',
                        'name_km' => 'PUBG Mobile UC',
                        'short' => 'Unknown Cash top-up for PUBG Mobile.',
                        'description' => 'Fast, reliable UC top-ups for PUBG Mobile so you never miss a season pass or crate drop.',
                        'price' => 5.00,
                        'featured' => false,
                        'plans' => [
                            ['label' => '325 UC', 'price' => 5.00],
                            ['label' => '660 UC', 'price' => 10.00],
                            ['label' => '1800 UC', 'price' => 25.00],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($catalog as $categoryIndex => $categoryData) {
            $category = Category::create([
                'name_en' => $categoryData['name_en'],
                'name_km' => $categoryData['name_km'],
                'slug' => Str::slug($categoryData['name_en']),
                'icon' => $categoryData['icon'],
                'sort_order' => $categoryIndex,
            ]);

            foreach ($categoryData['services'] as $serviceIndex => $serviceData) {
                $service = Service::create([
                    'category_id' => $category->id,
                    'name_en' => $serviceData['name_en'],
                    'name_km' => $serviceData['name_km'],
                    'slug' => Str::slug($serviceData['name_en']),
                    'short_description' => $serviceData['short'],
                    'description' => $serviceData['description'],
                    'base_price' => $serviceData['price'],
                    'is_featured' => $serviceData['featured'],
                    'is_active' => true,
                    'sort_order' => $serviceIndex,
                ]);

                foreach ($serviceData['plans'] as $planIndex => $planData) {
                    $service->plans()->create([
                        'label' => $planData['label'],
                        'price' => $planData['price'],
                        'sort_order' => $planIndex,
                    ]);
                }
            }
        }
    }
}
