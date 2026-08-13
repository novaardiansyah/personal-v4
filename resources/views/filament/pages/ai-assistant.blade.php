<x-filament-panels::page>
  <div style="display: flex; flex-direction: row; gap: 1.5rem; width: 100%; align-items: stretch; flex-wrap: wrap;">
    <div style="flex: 0 0 320px; width: 320px; box-sizing: border-box;">
      <x-filament::section
        heading="History"
        icon="heroicon-o-chat-bubble-left-ellipsis"
      >
        <div
          x-data="{ showSearch: false }"
          style="display: flex; flex-direction: column; gap: 10px;"
        >
          <div style="display: flex; align-items: center; gap: 8px; width: 100%;">
            <div style="flex: 1 1 0%; min-width: 0;">
              <x-filament::button
                wire:click="createNewSession"
                icon="heroicon-o-plus"
                size="md"
                color="primary"
                style="width: 100%;"
              >
                New Chat
              </x-filament::button>
            </div>

            <x-filament::button
              @click="showSearch = !showSearch"
              icon="heroicon-o-magnifying-glass"
              color="primary"
              size="md"
              tooltip="Search chats"
            />
          </div>

          <div x-show="showSearch" style="width: 100%;">
            <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
              <x-filament::input
                type="text"
                wire:model.live="searchQuery"
                placeholder="Search conversations..."
              />
            </x-filament::input.wrapper>
          </div>

          <div style="display: flex; flex-direction: column; gap: 8px; max-height: 420px; overflow-y: auto; padding-right: 4px;">
            @forelse($this->filteredSessions as $session)
              @if($editingSessionId === $session['id'])
                <div
                  wire:key="session-edit-{{ $session['id'] }}"
                  style="display: flex; align-items: center; gap: 6px; padding: 6px 8px; border-radius: 8px;"
                  class="border border-primary-500 bg-primary-50 dark:bg-primary-950/40"
                >
                  <div style="flex: 1 1 0%; min-width: 0;">
                    <x-filament::input.wrapper size="sm">
                      <x-filament::input
                        type="text"
                        wire:model="editingTitle"
                        @keydown.enter.prevent="$wire.saveSessionTitle()"
                        @keydown.escape.prevent="$wire.cancelEditSession()"
                      />
                    </x-filament::input.wrapper>
                  </div>
                  <x-filament::icon-button
                    wire:click="saveSessionTitle"
                    icon="heroicon-o-check"
                    color="success"
                    size="xs"
                    tooltip="Save title"
                  />
                  <x-filament::icon-button
                    wire:click="cancelEditSession"
                    icon="heroicon-o-x-mark"
                    color="gray"
                    size="xs"
                    tooltip="Cancel"
                  />
                </div>
              @else
                <div
                  wire:key="session-{{ $session['id'] }}"
                  wire:click="selectSession('{{ $session['id'] }}')"
                  style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                  @class([
                    'group border',
                    'border-primary-500 bg-primary-50 dark:bg-primary-950/40 text-primary-600 font-medium' => $activeSessionId === $session['id'],
                    'border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' => $activeSessionId !== $session['id'],
                  ])
                >
                  <div style="display: flex; align-items: center; gap: 8px; overflow: hidden; white-space: nowrap; flex: 1 1 0%; min-width: 0;">
                    <x-filament::icon icon="heroicon-o-chat-bubble-left" class="h-4 w-4 shrink-0 text-gray-400" />
                    <span style="overflow: hidden; text-overflow: ellipsis;" class="text-sm">{{ $session['title'] }}</span>
                  </div>

                  <div style="display: flex; align-items: center; gap: 6px;" class="opacity-0 group-hover:opacity-100 transition-opacity">
                    <x-filament::icon-button
                      wire:click.stop="editSession('{{ $session['id'] }}')"
                      icon="heroicon-o-pencil-square"
                      color="primary"
                      size="xs"
                      tooltip="Rename chat"
                    />
                    <x-filament::icon-button
                      wire:click.stop="deleteSession('{{ $session['id'] }}')"
                      icon="heroicon-o-trash"
                      color="danger"
                      size="xs"
                      tooltip="Delete chat"
                    />
                  </div>
                </div>
              @endif
            @empty
              <div style="text-align: center; padding: 24px 0;" class="text-sm text-gray-400">
                No conversation history.
              </div>
            @endforelse
          </div>

          @if(count($sessions) > 0)
            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid rgba(128,128,128,0.2);" class="text-xs">
              <span class="text-gray-400">Total: {{ count($sessions) }}</span>
              <x-filament::button
                wire:click="clearAllSessions"
                wire:confirm="Clear all conversations?"
                color="danger"
                size="xs"
                variant="link"
              >
                Clear all
              </x-filament::button>
            </div>
          @endif
        </div>
      </x-filament::section>
    </div>

    <div style="flex: 1 1 0%; min-width: 320px; box-sizing: border-box;">
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
                @if($msg['role'] === 'user')
                  <div wire:key="msg-{{ $msg['id'] }}" style="display: flex; width: 100%; justify-content: flex-end; margin: 10px 0;">
                    <div style="padding: 12px 16px; border-radius: 16px 16px 0px 16px; max-width: 80%; word-break: break-word;" class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 border dark:border-gray-700 text-sm leading-relaxed shadow-sm">
                      {!! nl2br(e($msg['content'])) !!}
                    </div>
                  </div>
                @else
                  <div wire:key="msg-{{ $msg['id'] }}" style="display: flex; width: 100%; justify-content: flex-start; margin: 10px 0;">
                    <div style="padding: 14px 18px; border-radius: 16px 16px 16px 0px; max-width: 85%; word-break: break-word;" class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 border dark:border-gray-700 text-sm leading-relaxed shadow-sm space-y-2">
                      @if(!empty($msg['is_placeholder']))
                        <div
                          x-data="{
                            texts: [
                              'Thinking...',
                              'Hmm, almost there...',
                              'Processing your request...',
                              'Searching for the best answer...',
                              'Crafting the response...',
                              'Connecting the dots...',
                              'Analyzing the details...',
                              'Formulating ideas...',
                              'Gathering information...',
                              'Putting it all together...',
                              'Formatting output...',
                              'Almost done...',
                              'Finalizing details...',
                              'Just a moment...',
                              'Consulting the knowledge base...',
                              'Drafting a thoughtful response...',
                              'Checking for optimal solutions...',
                              'Reviewing your context...',
                              'Polishing the response...',
                              'Fetching relevant insights...',
                              'Synthesizing the details...',
                              'Preparing the summary...',
                              'Refining the generated text...',
                              'Double-checking facts...',
                              'Weaving the answers...'
                            ],
                            currentIndex: 0,
                            timer: null,
                            pickRandom() {
                              let next;
                              do {
                                next = Math.floor(Math.random() * this.texts.length);
                              } while (next === this.currentIndex && this.texts.length > 1);
                              this.currentIndex = next;
                            },
                            init() {
                              this.pickRandom();
                              this.timer = setInterval(() => {
                                this.pickRandom();
                              }, 3000);
                            },
                            destroy() {
                              if (this.timer) clearInterval(this.timer);
                            }
                          }"
                          style="display: flex; align-items: center; gap: 8px;"
                          class="animate-pulse text-gray-400"
                        >
                          <x-filament::icon icon="heroicon-o-sparkles" class="h-4 w-4 text-primary-500" />
                          <span x-text="texts[currentIndex]">Thinking...</span>
                        </div>
                      @else
                        <div class="ai-markdown-content text-sm leading-relaxed space-y-2">
                          {!! Str::markdown($msg['content']) !!}
                        </div>
                      @endif
                    </div>
                  </div>
                @endif
              @endforeach
            @endif
          </div>

          <style>
            .copy-code-btn {
              position: absolute;
              top: 8px;
              right: 8px;
              display: flex;
              align-items: center;
              gap: 4px;
              background-color: rgba(255, 255, 255, 0.12);
              color: #94a3b8;
              border: 1px solid rgba(255, 255, 255, 0.2);
              border-radius: 6px;
              padding: 3px 8px;
              font-size: 0.75rem;
              font-family: inherit;
              cursor: pointer;
              transition: all 0.2s ease;
              z-index: 10;
            }
            .copy-code-btn:hover {
              background-color: rgba(255, 255, 255, 0.25);
              color: #ffffff;
            }
            .ai-markdown-content p {
              margin-bottom: 0.5rem;
            }
            .ai-markdown-content p:last-child {
              margin-bottom: 0;
            }
            .ai-markdown-content pre {
              background-color: #1e293b !important;
              color: #f8fafc !important;
              padding: 12px 16px !important;
              border-radius: 8px !important;
              overflow-x: auto !important;
              font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
              font-size: 0.85rem !important;
              margin: 10px 0 !important;
              line-height: 1.5 !important;
              border: 1px solid rgba(255, 255, 255, 0.1) !important;
            }
            .ai-markdown-content code {
              background-color: rgba(128, 128, 128, 0.18) !important;
              padding: 2px 6px !important;
              border-radius: 4px !important;
              font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
              font-size: 0.875em !important;
            }
            .ai-markdown-content pre code {
              background-color: transparent !important;
              padding: 0 !important;
              border-radius: 0 !important;
              color: inherit !important;
            }
            .ai-markdown-content ul {
              list-style-type: disc !important;
              padding-left: 1.25rem !important;
              margin: 8px 0 !important;
            }
            .ai-markdown-content ol {
              list-style-type: decimal !important;
              padding-left: 1.25rem !important;
              margin: 8px 0 !important;
            }
            .ai-markdown-content li {
              margin-bottom: 4px !important;
            }
            .ai-markdown-content h1, .ai-markdown-content h2, .ai-markdown-content h3, .ai-markdown-content h4 {
              font-weight: 600 !important;
              margin-top: 12px !important;
              margin-bottom: 6px !important;
            }
            .ai-markdown-content h1 { font-size: 1.25rem !important; }
            .ai-markdown-content h2 { font-size: 1.1rem !important; }
            .ai-markdown-content h3 { font-size: 1.0rem !important; }
            .ai-markdown-content blockquote {
              border-left: 4px solid #3b82f6;
              padding-left: 12px;
              margin: 8px 0;
              font-style: italic;
              opacity: 0.85;
            }
            .ai-markdown-content table {
              width: 100%;
              border-collapse: collapse;
              margin: 10px 0;
              font-size: 0.875rem;
            }
            .ai-markdown-content th, .ai-markdown-content td {
              border: 1px solid rgba(128, 128, 128, 0.2);
              padding: 6px 10px;
              text-align: left;
            }
            .ai-markdown-content th {
              background-color: rgba(128, 128, 128, 0.1);
            }
          </style>

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
    </div>
  </div>
</x-filament-panels::page>
