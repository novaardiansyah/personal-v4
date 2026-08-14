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

    <form
      x-data="{
        resize() {
          const el = $refs.userTextarea;
          if (!el) return;
          el.style.height = 'auto';
          el.style.height = Math.min(Math.max(el.scrollHeight, 40), 160) + 'px';
        },
        submitChat() {
          const el = $refs.userTextarea;
          if (!el) return;
          const text = el.value.trim();
          if (!text) return;

          $wire.sendMessage(text);
          el.value = '';
          el.style.height = '40px';
        }
      }"
      x-init="
        $nextTick(() => resize());
        $watch('$wire.userMessage', (v) => {
          if (!v && $refs.userTextarea) {
            $refs.userTextarea.value = '';
            $refs.userTextarea.style.height = '40px';
          }
        });
      "
      @submit.prevent="submitChat()"
      style="width: 100%; padding-top: 12px; border-top: 1px solid rgba(128,128,128,0.2);"
    >
      <div class="relative flex flex-col w-full rounded-xl border border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900 shadow-sm focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 transition-all">
        <textarea
          x-ref="userTextarea"
          wire:model="userMessage"
          rows="1"
          placeholder="Type a message..."
          @input="resize(); $wire.userMessage = $el.value"
          @keydown="
            if ($event.key === 'Enter' && !$event.shiftKey && !$event.isComposing) {
              $event.preventDefault();
              submitChat();
            }
          "
          class="w-full resize-none border-none bg-transparent px-4 pt-3 pb-2 text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-400 focus:outline-none focus:ring-0"
          style="max-height: 160px; min-height: 40px; overflow-y: auto; box-shadow: none;"
        ></textarea>

        <div style="display: flex; align-items: center; justify-content: space-between; padding: 4px 12px 8px 16px;">
          <span class="text-xs text-gray-400">Shift + Enter for new line</span>
          <x-filament::icon-button
            type="submit"
            icon="heroicon-m-paper-airplane"
            color="primary"
            size="md"
            tooltip="Send message (Enter)"
          />
        </div>
      </div>
    </form>
  </div>
</x-filament::section>
