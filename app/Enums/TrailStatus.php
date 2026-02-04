<?php

namespace App\Enums;

enum TrailStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
