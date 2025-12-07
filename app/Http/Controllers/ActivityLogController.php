<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
  public function preview_email(Request $request, ActivityLog $activityLog)
  {
    if ($activityLog->event !== 'Mail Notification') {
      abort(404);
    }
    
    $html = $activityLog->properties['html'] ?? null;
    
    if (!$html) {
      abort(404);
    }

    echo $html;
  }
}
