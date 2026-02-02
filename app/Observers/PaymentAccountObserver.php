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
      $this->_deleteImage($paymentAccount);
    }
  }

  public function saved(PaymentAccount $paymentAccount): void
  {
    $currentImage = $paymentAccount->logo;

    if ($currentImage) {
      $req = app(CdnService::class)->upload(
        $currentImage,
        subjectType: PaymentAccount::class,
        subjectId: $paymentAccount->id,
        dir: 'payment-account'
      );

      if ($req && $req->successful()) {
        Storage::disk('public')->delete($currentImage);
      }
    }
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
    $this->_deleteImage($paymentAccount);
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

  private function _deleteImage(PaymentAccount $paymentAccount): void
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
