# Media — images for this portfolio

Drop original, high-quality files here (JPG, PNG or WebP), then run:

```
php artisan portfolio:media ngoun-leng
```

This creates resized WebP files in `public/portfolios/ngoun-leng/`. Until you run it, the site
serves the original files directly, so new images show up right away, just not optimized.

Anything missing shows as a neutral "Image required" placeholder with the expected shape.
Use **real** material only: actual screenshots, renders, stills and photographs.

## What the layout expects

| File | Shape | Minimum size | Where it shows |
|---|---|---|---|
| `portrait/portrait.jpg` | 4:5 portrait | 1600 × 2000 | Home hero |
| `portrait/working.jpg` | 3:4 portrait | 1200 × 1600 | About page |
| `brand/og-image.jpg` | 1.91:1 | 1200 × 630 | Link previews on social media |
| `projects/{project}/cover.jpg` | see `cover.ratio` in that project's `project.php` | 2400 px on the long side | Work list, case study hero |
| `projects/{project}/gallery-N.jpg` | see `gallery` in `project.php` | 1600 px on the long side | Case study gallery |
| `projects/{project}/feature-*.jpg` | see `features` in `en.php` | 1600 px on the long side | Case study "Key features" |

The image is cropped to the shape listed in `ratio`. If your image has a different shape,
change `ratio` in `project.php` instead of stretching the image (e.g. `'16/9'`, `'4/5'`, `'1/1'`).

## Tips

- Screenshots: capture at 2× (retina) and crop out browser chrome.
- Portraits: natural light, plain background, eyes in the upper third.
- Keep each file under ~5 MB; the optimizer handles the rest.
