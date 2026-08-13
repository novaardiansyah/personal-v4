<x-filament-panels::page>
  <div style="display: flex; flex-direction: row; gap: 1.5rem; width: 100%; align-items: stretch; flex-wrap: wrap;">
    <div style="flex: 0 0 320px; width: 320px; box-sizing: border-box;">
      @include('filament.pages.ai-assistant.history-sidebar')
    </div>

    <div style="flex: 1 1 0%; min-width: 320px; box-sizing: border-box;">
      @include('filament.pages.ai-assistant.chat-window')
    </div>
  </div>
</x-filament-panels::page>
