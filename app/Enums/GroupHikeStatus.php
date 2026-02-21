<?php

namespace App\Enums;

enum GroupHikeStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
