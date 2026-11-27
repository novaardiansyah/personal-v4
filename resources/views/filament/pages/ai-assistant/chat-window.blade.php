<x-filament::section
  heading="{{ $sessions[$activeSessionId]['title'] ?? 'AI Assistant' }}"
  icon="heroicon-o-sparkles"
>
  <x-slot name="headerEnd">
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
      <select
        wire:model.live="selectedContextId"
        style="font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; border: 1px solid rgba(128,128,128,0.3); background: transparent; max-width: 180px;"
        title="Assistant Persona"
      >
        @foreach($this->contexts as $ctx)
          <option value="{{ $ctx['id'] }}">{{ $ctx['name'] }}</option>
        @endforeach
      </select>

      <input
        type="text"
        wire:model.lazy="selectedModel"
        placeholder="Model..."
        style="font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; border: 1px solid rgba(128,128,128,0.3); background: transparent; width: 130px;"
        title="Model Override"
      />

      <x-filament::icon-button
        wire:click="exportMarkdown"
        icon="heroicon-o-document-text"
        color="gray"
        size="sm"
        tooltip="Export Markdown"
      />

      <x-filament::icon-button
        wire:click="exportPdf"
        icon="heroicon-o-document-arrow-down"
        color="gray"
        size="sm"
        tooltip="Export PDF"
      />

      <x-filament::icon-button
        wire:click="toggleSidebar"
        icon="{{ $isSidebarOpen ? 'heroicon-o-bars-3-bottom-left' : 'heroicon-o-bars-3' }}"
        color="gray"
        size="sm"
        tooltip="{{ $isSidebarOpen ? 'Hide history sidebar' : 'Show history sidebar' }}"
      />
    </div>
  </x-slot>

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
            button.innerHTML = '<span>Copy</span>';

            button.onclick = () => {
              const code = pre.querySelector('code');
              const text = code ? code.innerText : pre.innerText;
              navigator.clipboard.writeText(text).then(() => {
                button.innerHTML = '<span style=\'color:#22c55e;\'>Copied!</span>';
                setTimeout(() => {
                  button.innerHTML = '<span>Copy</span>';
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
      style="display: flex; flex-direction: column; max-height: 540px; min-height: 360px; overflow-y: auto; padding: 8px;"
    >
      @if($hasMoreMessages)
        <div style="display: flex; justify-content: center; margin-bottom: 12px;">
          <x-filament::button
            wire:click="loadOlderMessages"
            size="xs"
            color="gray"
            variant="outlined"
          >
            Load older messages
          </x-filament::button>
        </div>
      @endif

      @if(empty($messages))
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; padding: 40px 16px; text-align: center;">
          <div style="font-size: 0.95rem; opacity: 0.75;">
            Start a new conversation or choose a quick prompt below.
          </div>

          @if(!empty($this->activeContext['starter_prompts']))
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; max-width: 640px;">
              @foreach($this->activeContext['starter_prompts'] as $starter)
                <x-filament::button
                  wire:click="sendPrompt('{{ addslashes($starter) }}')"
                  size="xs"
                  color="gray"
                  outlined
                >
                  {{ $starter }}
                </x-filament::button>
              @endforeach
            </div>
          @endif
        </div>
      @else
        @foreach($messages as $msg)
          @include('filament.pages.ai-assistant.message-bubble', ['msg' => $msg, 'isLast' => $loop->last])
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
      @if($attachment)
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; padding: 6px 10px; border-radius: 8px; border: 1px solid rgba(128,128,128,0.25); font-size: 0.75rem;">
          <x-filament::icon icon="heroicon-o-photo" style="width: 16px; height: 16px;" />
          <span>{{ $attachment->getClientOriginalName() }}</span>
          <x-filament::icon-button
            wire:click="removeAttachment"
            icon="heroicon-o-x-mark"
            color="danger"
            size="xs"
            tooltip="Remove attachment"
          />
        </div>
      @endif

      <div style="display: flex; flex-direction: column; width: 100%; border-radius: 12px; border: 1px solid rgba(128,128,128,0.3); padding: 4px;">
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
          style="width: 100%; resize: none; border: none; background: transparent; padding: 10px 14px 6px 14px; font-size: 0.875rem; max-height: 160px; min-height: 40px; overflow-y: auto; outline: none; box-shadow: none;"
        ></textarea>

        <div style="display: flex; align-items: center; justify-content: space-between; padding: 4px 10px 6px 14px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; font-size: 0.75rem; opacity: 0.75;" title="Attach image (receipt, screenshot)">
              <input type="file" wire:model="attachment" accept="image/*" style="display: none;" />
              <x-filament::icon icon="heroicon-o-paper-clip" style="width: 16px; height: 16px;" />
              <span>Attach Image</span>
            </label>
            <span style="font-size: 0.75rem; opacity: 0.55;">Shift + Enter for new line</span>
          </div>

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
