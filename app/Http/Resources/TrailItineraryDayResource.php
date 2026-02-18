<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TrailItineraryDay */
class TrailItineraryDayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_number' => $this->day_number,
            'title' => $this->title,
            'description' => $this->description,
            'distance_km' => $this->distance_km,
            'elevation_gain_m' => $this->elevation_gain_m,
            'start_point' => $this->start_point,
            'end_point' => $this->end_point,
            'accommodation' => $this->accommodation,
            'sort_order' => $this->sort_order,
        ];
    }
}
