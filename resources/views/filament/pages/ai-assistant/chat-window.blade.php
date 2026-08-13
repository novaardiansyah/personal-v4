<x-filament::section
  heading="{{ $sessions[$activeSessionId]['title'] ?? 'AI Assistant' }}"
  icon="heroicon-o-sparkles"
>
  <div
    x-data="{
      scrollToBottom() {
        $nextTick(() => {
          if ($refs.chatContainer) {
            $refs.chatContainer.scrollTop = $refs.chatContainer.scrollHeight;
          }
        });
      },
      initCopyButtons() {
        $nextTick(() => {
          this.$el.querySelectorAll('.ai-markdown-content pre').forEach((pre) => {
            if (pre.querySelector('.copy-code-btn')) return;

            pre.style.position = 'relative';

            const button = document.createElement('button');
            button.className = 'copy-code-btn';
            button.type = 'button';
            button.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' style=\'width:14px;height:14px;\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5\' /></svg><span>Copy</span>';

            button.onclick = () => {
              const code = pre.querySelector('code');
              const text = code ? code.innerText : pre.innerText;
              navigator.clipboard.writeText(text).then(() => {
                button.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' style=\'width:14px;height:14px;color:#22c55e;\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'m4.5 12.75 6 6 9-13.5\' /></svg><span style=\'color:#22c55e;\'>Copied!</span>';
                setTimeout(() => {
                  button.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' style=\'width:14px;height:14px;\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5\' /></svg><span>Copy</span>';
                }, 2000);
              });
            };

            pre.appendChild(button);
          });
        });
      }
    }"
    x-init="scrollToBottom(); initCopyButtons(); $watch('$wire.messages', () => { scrollToBottom(); initCopyButtons(); });"
    style="display: flex; flex-direction: column; gap: 16px;"
  >
    <div
      x-ref="chatContainer"
      style="display: flex; flex-direction: column; max-height: 520px; min-height: 350px; overflow-y: auto; padding: 8px;"
    >
      @if(empty($messages))
        <div style="text-align: center; padding: 48px 16px;" class="text-sm text-gray-400">
          <p>Start a new conversation or select a session from the history.</p>
        </div>
      @else
        @foreach($messages as $msg)
          @include('filament.pages.ai-assistant.message-bubble', ['msg' => $msg])
        @endforeach
      @endif
    </div>

    @include('filament.pages.ai-assistant.markdown-styles')

    <form wire:submit.prevent="sendMessage" style="display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid rgba(128,128,128,0.2);">
      <div style="flex: 1 1 0%; min-width: 0;">
        <x-filament::input.wrapper>
          <x-filament::input
            type="text"
            wire:model="userMessage"
            placeholder="Type a message..."
          />
        </x-filament::input.wrapper>
      </div>

      <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
        Send
      </x-filament::button>
    </form>
  </div>
</x-filament::section>
