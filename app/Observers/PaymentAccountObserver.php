<?php

/*
 * Project Name: personal-v4
 * File: PaymentAccountObserver.php
 * Created Date: Thursday December 11th 2025
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Observers;

use App\Models\Gallery;
use App\Models\PaymentAccount;
use App\Services\GalleryResource\CdnService;
use Illuminate\Support\Facades\Storage;

class PaymentAccountObserver
{
  public function saving(PaymentAccount $paymentAccount): void
  {
    if (!$paymentAccount->user_id) {
      $paymentAccount->user_id = getUser()->id;
    }

    $isImageChange = $paymentAccount->isDirty('logo');
    $currentImage = $paymentAccount->logo;

    if ($isImageChange && $currentImage) {
      $this->_delete_image_from_cdn($paymentAccount);
    }
  }

  public function saved(PaymentAccount $paymentAccount): void
  {
    $currentImage = $paymentAccount->logo;

    if ($currentImage) {
      $this->_upload_image_to_cdn($paymentAccount);
    }
  }

  private function _upload_image_to_cdn(PaymentAccount $paymentAccount)
  {
    $currentImage = $paymentAccount->logo;

    $req = app(CdnService::class)->upload(
      $currentImage,
      subjectType: PaymentAccount::class,
      subjectId: $paymentAccount->id,
      dir: 'payment-account'
    );

    if ($req && $req->successful()) {
      $res = $req->json();
      $largeImage = collect($res['data'] ?? [])->firstWhere('size', 'large');

      if ($largeImage && isset($largeImage['file_path'])) {
        $this->_set_image_path($paymentAccount, $largeImage['file_path']);
      }
    }
  }

  private function _set_image_path(PaymentAccount $paymentAccount, string $file_path): void
  {
    Storage::disk('public')->delete($paymentAccount->logo);

    $paymentAccount->logo = str_replace('\\', '/', $file_path);
    $paymentAccount->saveQuietly();
  }

  /**
   * Handle the PaymentAccount "created" event.
   */
  public function created(PaymentAccount $paymentAccount): void
  {
    $this->_log('Created', $paymentAccount);
  }

  /**
   * Handle the PaymentAccount "updated" event.
   */
  public function updated(PaymentAccount $paymentAccount): void
  {
    $this->_log('Updated', $paymentAccount);
  }

  /**
   * Handle the PaymentAccount "deleted" event.
   */
  public function deleted(PaymentAccount $paymentAccount): void
  {
    $this->_log('Deleted', $paymentAccount);
    $this->_delete_image_from_cdn($paymentAccount);
  }

  /**
   * Handle the PaymentAccount "restored" event.
   */
  public function restored(PaymentAccount $paymentAccount): void
  {
    $this->_log('Restored', $paymentAccount);
  }

  /**
   * Handle the PaymentAccount "force deleted" event.
   */
  public function forceDeleted(PaymentAccount $paymentAccount): void
  {
    $this->_log('Force Deleted', $paymentAccount);
  }

  private function _delete_image_from_cdn(PaymentAccount $paymentAccount): void
  {
    $exist = Gallery::where('subject_type', PaymentAccount::class)->where('subject_id', $paymentAccount->id)->first();

    if ($exist) {
      app(CdnService::class)->deleteByGroupCode($exist->group_code);
    }
  }

  private function _log(string $event, PaymentAccount $paymentAccount): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Payment Account',
      'subject_type' => PaymentAccount::class,
      'subject_id'   => $paymentAccount->id,
    ], $paymentAccount);
  }
}
