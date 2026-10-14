<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackupStorages\Actions;

use App\Models\BackupStorage;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;

class GenerateRefreshTokenAction
{
  public static function make(): Action
  {
    return Action::make('generate_refresh_token')
      ->label('Generate Refresh Token')
      ->icon(Heroicon::OutlinedKey)
      ->color('info')
      ->visible(fn(?BackupStorage $record): bool => $record?->provider?->slug === 'google-drive')
      ->modalHeading('Generate Refresh Token')
      ->modalDescription('Authorize Google Drive and exchange authorization code for an OAuth refresh token.')
      ->modalSubmitActionLabel('Exchange Token')
      ->modalIcon(Heroicon::OutlinedKey)
      ->modalWidth(Width::Medium)
      ->schema([
        Select::make('redirect_uri')
          ->label('Redirect URI')
          ->options(function (?BackupStorage $record): array {
            $providerKeys    = is_array($record?->provider?->keys) ? $record->provider->keys : [];
            $rawRedirectUris = $providerKeys['oauth_redirect_uris'] ?? 'http://localhost';
            $redirectUris    = array_values(array_filter(array_map('trim', explode(',', $rawRedirectUris))));

            if (empty($redirectUris)) {
              $redirectUris = ['http://localhost'];
            }

            return array_combine($redirectUris, $redirectUris);
          })
          ->default(function (?BackupStorage $record): string {
            $providerKeys    = is_array($record?->provider?->keys) ? $record->provider->keys : [];
            $rawRedirectUris = $providerKeys['oauth_redirect_uris'] ?? 'http://localhost';
            $redirectUris    = array_values(array_filter(array_map('trim', explode(',', $rawRedirectUris))));

            return $redirectUris[0] ?? 'http://localhost';
          })
          ->required()
          ->live(),
        Placeholder::make('auth_instructions')
          ->label('Step 1: Authorize Google Account')
          ->content(function (?BackupStorage $record, callable $get): HtmlString {
            $providerKeys = is_array($record?->provider?->keys) ? $record->provider->keys : [];
            $clientId     = $providerKeys['oauth_client_id'] ?? null;
            $authUri      = $providerKeys['oauth_auth_uri'] ?? 'https://accounts.google.com/o/oauth2/auth';
            $redirectUri  = $get('redirect_uri') ?: 'http://localhost';

            if (empty($clientId)) {
              return new HtmlString('<div style="color: #ef4444; font-size: 0.875rem;">Client ID is missing in provider keys. Please configure the Google Drive provider keys first.</div>');
            }

            $params = [
              'client_id'     => $clientId,
              'redirect_uri'  => $redirectUri,
              'response_type' => 'code',
              'scope'         => 'https://www.googleapis.com/auth/drive',
              'access_type'   => 'offline',
              'prompt'        => 'consent',
            ];

            $authUrl = $authUri . '?' . http_build_query($params);

            return new HtmlString(
              '<a href="' . htmlspecialchars($authUrl) . '" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background-color: #2563eb; color: #ffffff; border-radius: 0.375rem; font-weight: 500; font-size: 0.875rem; text-decoration: none;">'
              . 'Open Google Authorization URL &rarr;'
              . '</a><p style="margin-top: 0.5rem; font-size: 0.75rem; color: #6b7280;">Log in and grant permissions. After redirecting (e.g. to localhost), copy either the entire redirected URL or the code parameter and paste below.</p>'
            );
          }),
        TextInput::make('code')
          ->label('Step 2: Authorization Code')
          ->placeholder('Paste authorization code or full callback URL here')
          ->required()
          ->helperText('Paste the code or redirected URL (e.g. http://localhost/?code=4/0A...) here.'),
      ])
      ->action(function (array $data, BackupStorage $record): void {
        $providerKeys = is_array($record->provider?->keys) ? $record->provider->keys : [];
        $clientId     = $providerKeys['oauth_client_id'] ?? null;
        $clientSecret = $providerKeys['oauth_client_secret'] ?? null;
        $tokenUri     = $providerKeys['oauth_token_uri'] ?? 'https://oauth2.googleapis.com/token';
        $redirectUri  = $data['redirect_uri'] ?? 'http://localhost';

        if (empty($clientId) || empty($clientSecret)) {
          Notification::make()
            ->danger()
            ->title('Provider Configuration Error')
            ->body('Provider does not have oauth_client_id or oauth_client_secret configured.')
            ->send();

          return;
        }

        $rawCode = trim($data['code'] ?? '');
        $code    = $rawCode;
        if (preg_match('/[?&]code=([^&]+)/', $rawCode, $matches)) {
          $code = urldecode($matches[1]);
        }

        $response = Http::asForm()->post($tokenUri, [
          'client_id'     => $clientId,
          'client_secret' => $clientSecret,
          'code'          => $code,
          'grant_type'    => 'authorization_code',
          'redirect_uri'  => $redirectUri,
        ]);

        if (!$response->successful()) {
          $errorMessage = $response->json('error_description') ?? $response->json('error') ?? $response->body();

          Notification::make()
            ->danger()
            ->title('Failed to Generate Refresh Token')
            ->body($errorMessage)
            ->send();

          return;
        }

        $refreshToken = $response->json('refresh_token');

        if (empty($refreshToken)) {
          Notification::make()
            ->warning()
            ->title('No Refresh Token Returned')
            ->body('Google did not return a refresh token. Make sure you opened the authorization URL with consent prompt.')
            ->send();

          return;
        }

        $keys                       = is_array($record->keys) ? $record->keys : [];
        $keys['oauth_refresh_token'] = $refreshToken;

        $record->update([
          'keys' => $keys,
        ]);

        Notification::make()
          ->success()
          ->title('Refresh Token Generated')
          ->body('Google Drive OAuth refresh token successfully generated and saved to storage keys.')
          ->send();
      });
  }
}
