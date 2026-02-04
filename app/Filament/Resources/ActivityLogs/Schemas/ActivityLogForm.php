<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Schemas\Schema;

class ActivityLogForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        // ! Not used
      ]);
  }
}
