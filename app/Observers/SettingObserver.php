<?php

namespace App\Observers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
  public function created(Setting $setting): void
  {
    $this->clearCache($setting);
    $this->_log('Created', $setting);
  }

  public function updated(Setting $setting): void
  {
    $this->clearCache($setting);
    $this->_log('Updated', $setting);
  }

  public function deleted(Setting $setting): void
  {
    $this->clearCache($setting);
    $this->_log('Deleted', $setting);
  }

  public function restored(Setting $setting): void
  {
    $this->clearCache($setting);
    $this->_log('Restored', $setting);
  }

  public function forceDeleted(Setting $setting): void
  {
    $this->clearCache($setting);
    $this->_log('Force Deleted', $setting);
  }

  private function clearCache(Setting $setting): void
  {
    Cache::forget("setting.{$setting->key}");
    if ($setting->subject_type) {
      Cache::forget("setting.{$setting->subject_type}.{$setting->key}");
    }

    $originalSubjectType = $setting->getOriginal('subject_type');
    $originalKey = $setting->getOriginal('key');
    if ($originalKey) {
      Cache::forget("setting.{$originalKey}");
      if ($originalSubjectType) {
        Cache::forget("setting.{$originalSubjectType}.{$originalKey}");
      }
    }
  }

  private function _log(string $event, Setting $setting): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Setting',
      'subject_type' => Setting::class,
      'subject_id'   => $setting->id,
    ], $setting);
  }
}
