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
  }

  /**
   * Handle the Setting "updated" event.
   */
  public function updated(Setting $setting): void
  {
    $this->clearCache($setting);
  }

  /**
   * Handle the Setting "deleted" event.
   */
  public function deleted(Setting $setting): void
  {
    $this->clearCache($setting);
  }

  /**
   * Handle the Setting "restored" event.
   */
  public function restored(Setting $setting): void
  {
    $this->clearCache($setting);
  }

  /**
   * Handle the Setting "force deleted" event.
   */
  public function forceDeleted(Setting $setting): void
  {
    $this->clearCache($setting);
  }

  private function clearCache(Setting $setting): void
  {
    Cache::forget("setting.{$setting->key}");
  }
}
