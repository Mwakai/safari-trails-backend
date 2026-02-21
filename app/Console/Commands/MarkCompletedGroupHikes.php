<?php

namespace App\Console\Commands;

use App\Enums\GroupHikeStatus;
use App\Models\GroupHike;
use Illuminate\Console\Command;

class MarkCompletedGroupHikes extends Command
{
    protected $signature = 'group-hikes:mark-completed';

    protected $description = 'Mark past published group hikes as completed';

    public function handle(): int
    {
        $count = GroupHike::query()
            ->where('status', GroupHikeStatus::Published)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('end_date')->where('end_date', '<', today());
                })->orWhere(function ($q2) {
                    $q2->whereNull('end_date')->where('start_date', '<', today());
                });
            })
            ->update(['status' => GroupHikeStatus::Completed]);

        $this->info("Marked {$count} group hikes as completed.");

        return Command::SUCCESS;
    }
}
