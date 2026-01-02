<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
  public function preview_email(ActivityLog $activityLog)
  {
    if ($activityLog->event !== 'Mail Notification') {
      abort(404);
    }
    
    $html = $activityLog->properties['html'] ?? null;
    
    if (!$html) {
      abort(404);
    }

    $html = '<div style="margin-top: 30px; margin-bottom: 30px;">' . $html . '</div>';

    return response($html)->header('Content-Type', 'text/html');
  }
}
