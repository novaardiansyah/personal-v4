@if(in_array($msg['role'] ?? '', ['tool', 'system']) || (empty($msg['content']) && empty($msg['is_placeholder'])))
@elseif($msg['role'] === 'user')
  <div wire:key="msg-{{ $msg['id'] }}" style="display: flex; width: 100%; justify-content: flex-end; margin: 10px 0;">
    <div style="padding: 12px 16px; border-radius: 16px 16px 0px 16px; max-width: 85%; word-break: break-word; background-color: #d97706; color: #ffffff; font-size: 0.875rem; line-height: 1.5;">
      @if(!empty($msg['attachments']))
        <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;">
          @foreach($msg['attachments'] as $att)
            @if(!empty($att['path']))
              <img src="{{ asset('storage/' . $att['path']) }}" alt="{{ $att['name'] ?? 'attachment' }}" style="max-width: 180px; max-height: 140px; border-radius: 8px; object-fit: cover;" />
            @endif
          @endforeach
        </div>
      @endif
      {!! nl2br(e($msg['content'])) !!}
    </div>
  </div>
@else
  <div wire:key="msg-{{ $msg['id'] }}" style="display: flex; width: 100%; justify-content: flex-start; margin: 10px 0;">
    <div style="padding: 14px 18px; border-radius: 16px 16px 16px 0px; max-width: 90%; word-break: break-word; border: 1px solid rgba(128,128,128,0.22); background-color: rgba(128,128,128,0.06); font-size: 0.875rem; line-height: 1.5; display: flex; flex-direction: column; gap: 8px;">
      @if(!empty($msg['is_placeholder']))
        <div style="display: flex; flex-direction: column; gap: 6px;">
          <div style="display: flex; align-items: center; gap: 8px; opacity: 0.7; font-size: 0.8rem;">
            <x-filament::icon icon="heroicon-o-sparkles" style="width: 16px; height: 16px; color: #f59e0b;" />
            <span wire:stream="streaming-status">Thinking...</span>
          </div>
          <div wire:stream="streaming-reply" style="white-space: pre-wrap;"></div>
        </div>
      @else
        @if(!empty($msg['reasoning_content']))
          <details style="border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 6px 10px; font-size: 0.78rem; opacity: 0.8;">
            <summary style="cursor: pointer; font-weight: 600;">Thinking Process</summary>
            <div style="margin-top: 6px; white-space: pre-wrap;">{{ $msg['reasoning_content'] }}</div>
          </details>
        @endif

        <div class="ai-markdown-content" style="font-size: 0.875rem; line-height: 1.6;">
          {!! Str::markdown($msg['content']) !!}
        </div>

        @if(!empty($msg['pending_actions']))
          <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 6px;">
            @foreach($msg['pending_actions'] as $action)
              <div style="padding: 10px 12px; border-radius: 8px; border: 1px solid rgba(245, 158, 11, 0.4); background-color: rgba(245, 158, 11, 0.08); display: flex; flex-direction: column; gap: 8px;">
                <div style="font-size: 0.8rem; font-weight: 600;">
                  {{ $action['summary'] ?? $action['tool'] }}
                </div>

                @if(($action['status'] ?? 'pending') === 'pending')
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <x-filament::button
                      wire:click="confirmAction('{{ $msg['id'] }}', '{{ $action['action_id'] }}')"
                      color="success"
                      size="xs"
                      icon="heroicon-o-check"
                    >
                      Confirm
                    </x-filament::button>
                    <x-filament::button
                      wire:click="cancelAction('{{ $msg['id'] }}', '{{ $action['action_id'] }}')"
                      color="gray"
                      size="xs"
                      icon="heroicon-o-x-mark"
                    >
                      Cancel
                    </x-filament::button>
                  </div>
                @else
                  <div style="font-size: 0.75rem; opacity: 0.75;">
                    Status: <strong>{{ ucfirst($action['status']) }}</strong>
                    @if(!empty($action['result']['message']))
                      — {{ $action['result']['message'] }}
                    @endif
                  </div>
                @endif
              </div>
            @endforeach
          </div>
        @endif

        @if(!empty($msg['executed_tools']))
          <div style="display: flex; flex-wrap: wrap; gap: 6px; padding-top: 4px;">
            @foreach($msg['executed_tools'] as $toolName)
              <span style="font-size: 0.7rem; padding: 2px 8px; border-radius: 999px; border: 1px solid rgba(128,128,128,0.25); opacity: 0.75;">
                {{ $toolName }}
              </span>
            @endforeach
          </div>
        @endif

        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 6px; border-top: 1px solid rgba(128,128,128,0.15); font-size: 0.72rem; opacity: 0.7;">
          <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            @if(!empty($msg['model_used']))
              <span>{{ $msg['model_used'] }}</span>
            @endif
            @if(!empty($msg['latency_ms']))
              <span>&bull; {{ number_format($msg['latency_ms'] / 1000, 2) }}s</span>
            @endif
            @if(!empty($msg['token_total']))
              <span>&bull; {{ number_format($msg['token_total']) }} tok</span>
            @endif
          </div>

          <div style="display: flex; align-items: center; gap: 6px;" x-data="{ copied: false }">
            <x-filament::icon-button
              @click="navigator.clipboard.writeText(@js($msg['content'])).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
              icon="heroicon-o-clipboard"
              color="gray"
              size="xs"
              tooltip="Copy response"
            />
            @if(!empty($isLast))
              <x-filament::icon-button
                wire:click="regenerateLastMessage"
                icon="heroicon-o-arrow-path"
                color="gray"
                size="xs"
                tooltip="Regenerate response"
              />
            @endif
          </div>
        </div>
      @endif
    </div>
  </div>
@endif
