<?php

namespace App\Enums;

enum ArtisanCommandRunStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Failed = 'failed';
    case Completed = 'completed';
}
