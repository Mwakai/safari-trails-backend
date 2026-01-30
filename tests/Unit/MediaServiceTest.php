<?php

use App\Services\MediaService;

beforeEach(function () {
    $this->service = new MediaService;
});

describe('sanitizeFilename', function () {
    it('converts to lowercase and replaces spaces with hyphens', function () {
        expect($this->service->sanitizeFilename('Chania Falls View.JPG'))
            ->toBe('chania-falls-view.jpg');
    });

    it('removes special characters', function () {
        expect($this->service->sanitizeFilename('Mt. Kenya Sunrise!.png'))
            ->toBe('mt-kenya-sunrise.png');
    });

    it('transliterates accented characters', function () {
        expect($this->service->sanitizeFilename('naïve café.jpg'))
            ->toBe('naive-cafe.jpg');
    });

    it('removes consecutive hyphens', function () {
        expect($this->service->sanitizeFilename('hello---world.jpg'))
            ->toBe('hello-world.jpg');
    });

    it('trims hyphens from start and end', function () {
        expect($this->service->sanitizeFilename('-hello-world-.jpg'))
            ->toBe('hello-world.jpg');
    });

    it('preserves underscores', function () {
        expect($this->service->sanitizeFilename('my_photo_2024.jpg'))
            ->toBe('my_photo_2024.jpg');
    });
});

describe('determineType', function () {
    it('identifies image types', function () {
        expect($this->service->determineType('image/jpeg')->value)->toBe('image');
        expect($this->service->determineType('image/png')->value)->toBe('image');
        expect($this->service->determineType('image/gif')->value)->toBe('image');
        expect($this->service->determineType('image/webp')->value)->toBe('image');
        expect($this->service->determineType('image/svg+xml')->value)->toBe('image');
    });

    it('identifies video types', function () {
        expect($this->service->determineType('video/mp4')->value)->toBe('video');
        expect($this->service->determineType('video/webm')->value)->toBe('video');
    });

    it('identifies document types', function () {
        expect($this->service->determineType('application/pdf')->value)->toBe('document');
    });
});
