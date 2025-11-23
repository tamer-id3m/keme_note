<?php

namespace App\Traits;

enum QueueStatus: string
{
    case QUEUED = 'Queued';
    case IN_PROGRESS = 'In-Progress';
    case DONE = 'Done';
    case FAILED = 'Failed';
}
