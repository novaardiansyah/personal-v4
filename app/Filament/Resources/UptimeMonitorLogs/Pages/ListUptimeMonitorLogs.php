<?php

/*
 * Project Name: personal-v4
 * File: ListUptimeMonitorLogs.php
 * Created Date: Sunday February 8th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\UptimeMonitorLogs\Pages;

use App\Filament\Resources\UptimeMonitorLogs\UptimeMonitorLogResource;
use Filament\Resources\Pages\ListRecords;

class ListUptimeMonitorLogs extends ListRecords
{
  protected static string $resource = UptimeMonitorLogResource::class;

  protected function getHeaderActions(): array
  {
    return [];
  }
}
