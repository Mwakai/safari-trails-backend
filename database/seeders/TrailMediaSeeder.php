<?php

namespace Database\Seeders;

use App\Enums\MediaType;
use App\Enums\TrailImageType;
use App\Models\Media;
use App\Models\Trail;
use App\Models\TrailImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TrailMediaSeeder extends Seeder
{
    /**
     * @var array<string, array{url: string, alt: string}>
     */
    private array $trailImages = [
        'hells-gate-gorge-walk' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/Hell%27s_Gate%2C_Kenya.jpg/1280px-Hell%27s_Gate%2C_Kenya.jpg',
            'alt' => "Hell's Gate gorge, Naivasha, Kenya",
        ],
        'ngong-hills-ridgeline-trail' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Ngong%27_Hills%2C_Nairobi%2C_Kenya.jpg/1280px-Ngong%27_Hills%2C_Nairobi%2C_Kenya.jpg',
            'alt' => 'Ngong Hills ridgeline above Nairobi, Kenya',
        ],
        'karura-forest-loop' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/Karura_Forest_Nairobi_05.JPG/1280px-Karura_Forest_Nairobi_05.JPG',
            'alt' => 'Karura Forest trails, Nairobi, Kenya',
        ],
        'oloolua-nature-trail' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/Karura_Forest_Nairobi_05.JPG/1280px-Karura_Forest_Nairobi_05.JPG',
            'alt' => 'Indigenous forest trail, Karen, Nairobi',
        ],
        'mt-longonot-crater-circuit' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d9/2013-01-23_07-24-03_Kenya_Rift_Valley_-_Kijabe.jpg/1280px-2013-01-23_07-24-03_Kenya_Rift_Valley_-_Kijabe.jpg',
            'alt' => 'Mount Longonot volcano in the Great Rift Valley, Kenya',
        ],
        'menengai-crater-walk' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1d/Menengai_crater_view_from_the_edge.jpg/1280px-Menengai_crater_view_from_the_edge.jpg',
            'alt' => 'Menengai caldera view from the rim, Nakuru, Kenya',
        ],
        'mt-kenya-sirimon-chogoria' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/MtKenyaMackinder.jpg/1280px-MtKenyaMackinder.jpg',
            'alt' => "Mount Kenya high altitude landscape near Mackinder's Camp",
        ],
        'aberdare-ranges-circuit' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/8/89/Aberdare_Park_entrance.jpg',
            'alt' => 'Aberdare National Park, Kenya',
        ],
        'ol-donyo-sabuk-circuit' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e4/Mt_Kilimambogo.jpg/1280px-Mt_Kilimambogo.jpg',
            'alt' => 'Ol Donyo Sabuk (Mount Kilimambogo) from the Athi Plains, Kenya',
        ],
        'kakamega-forest-trail' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/8/8f/Kakamega_forest_view.jpg',
            'alt' => 'Kakamega tropical rainforest canopy, Western Kenya',
        ],
        'shimba-hills-forest-trail' => [
            'url' => 'https://upload.wikimedia.org/wikipedia/commons/8/8c/Elephants_at_shimba.jpg',
            'alt' => 'Elephants in Shimba Hills National Reserve, Coast Kenya',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uploader = User::whereHas('role', fn ($q) => $q->where('slug', 'content_manager'))->first();

        if (! $uploader) {
            return;
        }

        Storage::disk('public')->makeDirectory('trails');

        foreach ($this->trailImages as $slug => $image) {
            $trail = Trail::where('slug', $slug)->first();

            if (! $trail) {
                $this->command->warn("Trail not found: {$slug}");

                continue;
            }

            $this->command->getOutput()->write("  Downloading image for <info>{$trail->name}</info>... ");

            $response = Http::timeout(30)
                ->retry(2, 1000)
                ->withHeaders(['User-Agent' => 'SafariTrailsBackend/1.0 (seeder; contact@kenyatrails.com)'])
                ->get($image['url']);

            if (! $response->successful()) {
                $this->command->getOutput()->writeln('<comment>SKIPPED</comment> (HTTP '.$response->status().')');

                continue;
            }

            $filename = Str::slug($trail->name).'.jpg';
            $path = "trails/{$slug}/{$filename}";

            Storage::disk('public')->makeDirectory("trails/{$slug}");
            Storage::disk('public')->put($path, $response->body());

            $localPath = Storage::disk('public')->path($path);
            $size = Storage::disk('public')->size($path);
            [$width, $height] = @getimagesize($localPath) ?: [1280, 800];

            $media = Media::create([
                'filename' => $filename,
                'original_filename' => $filename,
                'path' => $path,
                'disk' => 'public',
                'mime_type' => 'image/jpeg',
                'size' => $size,
                'type' => MediaType::Image,
                'width' => $width,
                'height' => $height,
                'alt_text' => $image['alt'],
                'uploaded_by' => $uploader->id,
            ]);

            $trail->update(['featured_image_id' => $media->id]);

            TrailImage::create([
                'trail_id' => $trail->id,
                'media_id' => $media->id,
                'type' => TrailImageType::Gallery,
                'caption' => $image['alt'],
                'sort_order' => 1,
                'created_at' => now(),
            ]);

            $this->command->getOutput()->writeln('<info>OK</info>');

            // Be polite to Wikimedia's servers
            sleep(1);
        }
    }
}
