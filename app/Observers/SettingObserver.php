<?php

namespace App\Observers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
  /**
   * Handle the Setting "created" event.
   */
  public function created(Setting $setting): void
  {
    $this->clearCache($setting);
    $this->_log('Created', $setting);
  }

  /**
   * Handle the Setting "updated" event.
   */
  public function updated(Setting $setting): void
  {
    $this->clearCache($setting);
    $this->_log('Updated', $setting);
  }

  /**
   * Handle the Setting "deleted" event.
   */
  public function deleted(Setting $setting): void
  {
    $this->clearCache($setting);
    $this->_log('Deleted', $setting);
  }

  /**
   * Handle the Setting "restored" event.
   */
  public function restored(Setting $setting): void
  {
    $this->clearCache($setting);
    $this->_log('Restored', $setting);
  }

  /**
   * Handle the Setting "force deleted" event.
   */
  public function forceDeleted(Setting $setting): void
  {
    $this->clearCache($setting);
    $this->_log('Force Deleted', $setting);
  }

  private function clearCache(Setting $setting): void
  {
    Cache::forget("setting.{$setting->key}");
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
