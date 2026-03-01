<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendLoginNotificationJob implements ShouldQueue
{
	use Queueable;

	public function __construct(
		public User $user,
		public array $context = []
	) {}

	public function handle(): void
	{
		$authService = new AuthService();
		$authService->sendLoginTelegramNotification($this->user, $this->context);
		$authService->sendLoginEmailNotification($this->user, $this->context);
	}
}
