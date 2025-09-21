<?php

namespace App\Models;

use App\Observers\UserObserver;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail, HasAppAuthentication, HasEmailAuthentication, HasAvatar
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'code',
    'email',
    'password',
    'avatar_url',
    'email_verified_at',
    'has_email_authentication',
    'app_authentication_secret',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var list<string>
   */
  protected $hidden = [
    'password',
    'remember_token',
    'app_authentication_secret',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'app_authentication_secret' => 'encrypted',
      'has_email_authentication' => 'boolean',
    ];
  }

  public function canAccessPanel(Panel $panel): bool
  {
    return true;
  }

  public function getFilamentAvatarUrl(): ?string
  {
    return $this->avatar_url ? Storage::url($this->avatar_url) : null;
  }

  public function getAppAuthenticationSecret(): ?string
  {
    return $this->app_authentication_secret;
  }

  public function saveAppAuthenticationSecret(?string $secret): void
  {
    $this->app_authentication_secret = $secret;
    $this->save();
  }

  public function getAppAuthenticationHolderName(): string
  {
    return $this->email;
  }

  public function hasEmailAuthentication(): bool
  {
    return $this->has_email_authentication;
  }

  public function toggleEmailAuthentication(bool $condition): void
  {
    $this->has_email_authentication = $condition;
    $this->save();
  }
}
