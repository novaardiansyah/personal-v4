<x-filament-panels::page>
  <div class="flex flex-col lg:flex-row gap-6 w-full items-start" style="width: 100%; padding-bottom: 32px">
    <div class="w-full lg:w-80 shrink-0" style="box-sizing: border-box;">
      @include('filament.pages.ai-assistant.history-sidebar')
    </div>

    <div class="w-full flex-1 min-w-0" style="box-sizing: border-box;">
      @include('filament.pages.ai-assistant.chat-window')
    </div>
  </div>
</x-filament-panels::page>
