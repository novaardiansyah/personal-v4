<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
  public function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        $this->getNameFormComponent(),
        $this->getEmailFormComponent(),
        $this->getPasswordFormComponent(),
        $this->getPasswordConfirmationFormComponent(),
        $this->getCurrentPasswordFormComponent(),
				FileUpload::make('avatar_url')
          ->label('Profile picture')
          ->disk('public')
          ->directory('images/avatar')
          ->image()
          ->imageEditor()
          ->enableDownload()
          ->enableOpen(),
      ]);
  }
}
