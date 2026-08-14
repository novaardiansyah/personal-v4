@if($msg['role'] === 'user')
  <div wire:key="msg-{{ $msg['id'] }}" style="display: flex; width: 100%; justify-content: flex-end; margin: 10px 0;">
    <div style="padding: 12px 16px; border-radius: 16px 16px 0px 16px; max-width: 100%; word-break: break-word;" class="bg-primary-600 dark:bg-primary-500 text-white border border-primary-600 dark:border-primary-500 text-sm leading-relaxed shadow-sm">
      {!! nl2br(e($msg['content'])) !!}
    </div>
  </div>
@else
  <div wire:key="msg-{{ $msg['id'] }}" style="display: flex; width: 100%; justify-content: flex-start; margin: 10px 0;">
    <div style="padding: 14px 18px; border-radius: 16px 16px 16px 0px; max-width: 100%; word-break: break-word;" class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 border dark:border-gray-700 text-sm leading-relaxed shadow-sm space-y-2">
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
