<?php
    $mode = $mode ?? 'floating';
    $isInline = $mode === 'inline';
?>

<div
    x-data="projectChat({ projectId: <?php echo e($project->id); ?>, openDefault: <?php echo e($isInline ? 'true' : 'false'); ?> })"
    class="<?php echo e($isInline ? 'w-full' : 'fixed bottom-5 right-5 z-50'); ?>"
    <?php if(!$isInline): ?> style="width: 22rem; max-width: calc(100vw - 2.5rem);" <?php endif; ?>
>
    <?php if(!$isInline): ?>
    
    <div class="flex justify-end">
        <button
            @click="open = !open"
            type="button"
            class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 shadow-lg transition-all duration-200 hover:shadow-xl bg-[#AA5F3C] text-white"
            :class="open
                ? '!bg-[color:var(--surface-primary)] !text-[color:var(--text-primary)] border theme-border-primary'
                : 'bg-[#AA5F3C] text-white hover:bg-[#8B4A2E]'"
        >
            <svg x-show="!open" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <svg x-show="open" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span x-show="!open" class="text-sm font-medium">Tanya Asisten</span>
            <span x-show="open" class="text-sm font-medium">Tutup</span>
        </button>
    </div>
    <?php endif; ?>

    
    <div
        <?php if(!$isInline): ?> x-show="open" x-transition <?php endif; ?>
        class="<?php echo e($isInline ? 'rounded-2xl border theme-border-primary bg-[color:var(--surface-primary)] shadow-sm overflow-hidden flex flex-col' : 'mt-3 rounded-2xl border theme-border-primary bg-[color:var(--surface-primary)] shadow-2xl overflow-hidden flex flex-col'); ?>"
        style="<?php echo e($isInline ? 'height: 32rem;' : 'height: 26rem;'); ?>"
    >
        
        <div class="shrink-0 px-4 py-3 border-b theme-border-primary bg-[color:var(--bg-tertiary)] flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-[#AA5F3C]/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-semibold theme-text-primary leading-tight">Asisten Riset</span>
                    <p class="text-[10px] theme-text-muted">Berdasarkan artikel dalam project ini</p>
                </div>
            </div>
            <div x-show="loading" class="flex items-center gap-1.5 text-[10px] theme-text-muted">
                <span class="w-1.5 h-1.5 rounded-full bg-[#AA5F3C] animate-pulse"></span>
                Mengetik…
            </div>
        </div>

        
        <div
            x-ref="messagesContainer"
            class="flex-1 overflow-y-auto px-4 py-4 space-y-4"
        >
            
            <template x-if="messages.length === 0">
                <div class="text-center py-6">
                    <div class="w-12 h-12 rounded-full bg-[#AA5F3C]/10 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <p class="text-sm theme-text-secondary leading-relaxed max-w-[14rem] mx-auto">
                        Tanyakan apa saja tentang artikel-artikel dalam project ini. Aku akan menjawab berdasarkan ringkasan yang tersedia.
                    </p>
                    <div class="flex flex-wrap justify-center gap-2 mt-4">
                        <button
                            @click="quickAsk('Ringkasan singkat dari semua artikel')"
                            class="text-[11px] px-2.5 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary border theme-border-primary hover:bg-[#AA5F3C] hover:text-white hover:border-transparent transition-colors"
                        >Ringkasan semua artikel</button>
                        <button
                            @click="quickAsk('Bandingkan metodologi antar artikel')"
                            class="text-[11px] px-2.5 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary border theme-border-primary hover:bg-[#AA5F3C] hover:text-white hover:border-transparent transition-colors"
                        >Bandingkan metodologi</button>
                        <button
                            @click="quickAsk('Apa kesimpulan utamanya?')"
                            class="text-[11px] px-2.5 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary border theme-border-primary hover:bg-[#AA5F3C] hover:text-white hover:border-transparent transition-colors"
                        >Kesimpulan utama</button>
                    </div>
                </div>
            </template>

            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div
                        class="max-w-[88%] rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed"
                        :class="msg.role === 'user'
                            ? 'bg-[#AA5F3C] text-white rounded-tr-sm'
                            : 'bg-[color:var(--bg-tertiary)] theme-text-secondary rounded-tl-sm border theme-border-primary'"
                    >
                        <div x-html="formatContent(msg.content)"></div>

                        
                        <template x-if="msg.role === 'assistant' && msg.context_articles && msg.context_articles.length">
                            <div class="mt-2.5 pt-2 border-t border-white/10" :class="msg.role === 'user' ? 'border-white/20' : 'border-[color:var(--border-primary)]/50'">
                                <p class="text-[10px] mb-1.5 opacity-70">Sumber artikel:</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="articleId in msg.context_articles" :key="articleId">
                                        <a
                                            :href="`/articles/${articleId}`"
                                            target="_blank"
                                            class="text-[10px] px-2 py-0.5 rounded-full transition-colors"
                                            :class="msg.role === 'user'
                                                ? 'bg-white/15 text-white hover:bg-white/25'
                                                : 'bg-[#AA5F3C]/10 text-[#AA5F3C] hover:bg-[#AA5F3C]/20'"
                                        >
                                            Artikel <span x-text="articleId"></span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            
            <div x-show="loading && messages.length > 0" class="flex justify-start">
                <div class="bg-[color:var(--bg-tertiary)] rounded-2xl rounded-tl-sm px-3.5 py-2.5 border theme-border-primary">
                    <div class="flex items-center gap-1.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-[#AA5F3C] animate-bounce"></div>
                        <div class="w-1.5 h-1.5 rounded-full bg-[#AA5F3C] animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-1.5 h-1.5 rounded-full bg-[#AA5F3C] animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>

            <div x-ref="scrollAnchor"></div>
        </div>

        
        <div class="shrink-0 px-4 py-3 border-t theme-border-primary bg-[color:var(--surface-primary)]">
            <form @submit.prevent="sendMessage" class="flex items-end gap-2">
                <div class="flex-1 relative">
                    <textarea
                        x-model="newMessage"
                        @keydown.enter.prevent="if (!event.shiftKey) { sendMessage(); }"
                        @input="autoResize($event)"
                        rows="1"
                        placeholder="Tulis pertanyaan..."
                        class="w-full resize-none rounded-xl border theme-border-primary bg-[color:var(--bg-tertiary)] px-3 py-2 pr-8 text-sm theme-text-primary placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] focus:ring-1"
                        style="min-height: 2.5rem; max-height: 6rem; overflow-y: auto;"
                    ></textarea>
                    <span class="absolute right-2 bottom-2.5 text-[10px] theme-text-muted hidden sm:block">Enter</span>
                </div>
                <button
                    type="submit"
                    :disabled="!newMessage.trim() || loading"
                    class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-[#AA5F3C] text-white hover:bg-[#8B4A2E] transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed shrink-0 shadow-sm"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<?php $includeScript = $includeScript ?? true; ?>
<?php if($includeScript): ?>
<script>
function projectChat(config) {
    return {
        projectId: config.projectId,
        open: config.openDefault ?? false,
        messages: [],
        newMessage: '',
        loading: false,
        nextId: 1,

        init() {
            this.loadHistory();
        },

        async loadHistory() {
            try {
                const res = await fetch(`/projects/${this.projectId}/chat/history`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const json = await res.json();
                if (json.success && json.data.length) {
                    this.messages = json.data.map((msg, i) => ({
                        id: i + 1,
                        role: msg.role,
                        content: msg.content,
                        context_articles: msg.context_articles || [],
                        created_at: msg.created_at,
                    }));
                    this.nextId = this.messages.length + 1;
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) {
                console.error('Failed to load chat history', e);
            }
        },

        quickAsk(text) {
            this.newMessage = text;
            this.sendMessage();
        },

        autoResize(e) {
            const el = e.target;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 96) + 'px';
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        async sendMessage() {
            const text = this.newMessage.trim();
            if (!text || this.loading) return;

            // Reset textarea height
            const textarea = this.$el.querySelector('textarea');
            if (textarea) {
                textarea.style.height = 'auto';
            }

            this.messages.push({
                id: this.nextId++,
                role: 'user',
                content: text,
                context_articles: [],
            });

            this.newMessage = '';
            this.loading = true;
            this.$nextTick(() => this.scrollToBottom());

            try {
                const res = await fetch(`/projects/${this.projectId}/chat/send`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ message: text }),
                });

                const json = await res.json();

                if (json.success) {
                    this.messages.push({
                        id: this.nextId++,
                        role: 'assistant',
                        content: json.data.content,
                        context_articles: json.data.context_articles || [],
                    });
                } else {
                    this.messages.push({
                        id: this.nextId++,
                        role: 'assistant',
                        content: json.message || 'Maaf, terjadi kesalahan. Silakan coba lagi.',
                        context_articles: [],
                    });
                }
            } catch (e) {
                this.messages.push({
                    id: this.nextId++,
                    role: 'assistant',
                    content: 'Maaf, gagal terhubung ke server. Pastikan koneksi internet aktif.',
                    context_articles: [],
                });
            } finally {
                this.loading = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        formatContent(text) {
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/```([\s\S]*?)```/g, '<pre class="bg-[color:var(--bg-tertiary)] rounded-lg p-2 mt-1 overflow-x-auto text-xs"><code>$1</code></pre>')
                .replace(/`(.+?)`/g, '<code class="bg-[color:var(--bg-tertiary)] px-1 py-0.5 rounded text-xs">$1</code>')
                .replace(/\n/g, '<br>');
        },
    };
}
</script>
<?php endif; ?>
<?php /**PATH C:\laragon\www\myartikel\resources\views\projects\chat-panel.blade.php ENDPATH**/ ?>