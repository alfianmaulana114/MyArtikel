<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">Admin Dashboard</h2>
            <p class="text-sm theme-text-muted">Ringkasan & monitoring platform secara global.</p>
        </div>
    </x-slot>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="card p-4 text-center">
            <div class="text-2xl font-bold theme-text-primary">{{ $totalUsers }}</div>
            <div class="text-xs theme-text-muted mt-1">Total Users</div>
            <div class="text-[10px] theme-text-muted mt-0.5">{{ $activeUsers }} aktif · {{ $inactiveUsers }} nonaktif</div>
        </div>
        <div class="card p-4 text-center">
            <div class="text-2xl font-bold theme-text-primary">{{ $totalArticles }}</div>
            <div class="text-xs theme-text-muted mt-1">Total Articles</div>
            <div class="text-[10px] theme-text-muted mt-0.5">{{ $articleStats['ready'] }} ready · {{ $articleStats['processing'] }} proc · {{ $articleStats['failed'] }} failed</div>
        </div>
        <div class="card p-4 text-center">
            <div class="text-2xl font-bold theme-text-primary">{{ $totalSummaries }}</div>
            <div class="text-xs theme-text-muted mt-1">Summaries</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Monthly Trends Chart --}}
        <div class="lg:col-span-2 card p-6" x-data="{
            trends: {{ Js::from($monthlyTrends) }},
            init() {
                this.$nextTick(() => this.renderChart());
            },
            renderChart() {
                const canvas = this.$refs.chart;
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                const labels = this.trends.map(t => t.month);
                const articles = this.trends.map(t => t.articles);
                const users = this.trends.map(t => t.users);
                const maxVal = Math.max(...articles, ...users, 1);
                const w = canvas.width = canvas.offsetWidth * 2;
                const h = canvas.height = canvas.offsetHeight * 2;
                ctx.scale(2, 2);
                const cw = canvas.offsetWidth, ch = canvas.offsetHeight;
                const pad = { top: 20, right: 20, bottom: 50, left: 40 };
                const pw = cw - pad.left - pad.right;
                const ph = ch - pad.top - pad.bottom;

                ctx.clearRect(0, 0, cw, ch);

                // Grid
                ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--border-primary').trim() || '#e5e7eb';
                ctx.lineWidth = 0.5;
                for (let i = 0; i <= 4; i++) {
                    const y = pad.top + (ph / 4) * i;
                    ctx.beginPath();
                    ctx.moveTo(pad.left, y);
                    ctx.lineTo(cw - pad.right, y);
                    ctx.stroke();
                    const val = Math.round(maxVal - (maxVal / 4) * i);
                    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim() || '#9ca3af';
                    ctx.font = '10px sans-serif';
                    ctx.fillText(val, 2, y + 3);
                }

                // X labels
                ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim() || '#9ca3af';
                ctx.font = '9px sans-serif';
                ctx.textAlign = 'center';
                labels.forEach((l, i) => {
                    const x = pad.left + (pw / (labels.length - 1)) * i;
                    ctx.fillText(l, x, ch - 5);
                });

                const drawLine = (data, color) => {
                    ctx.strokeStyle = color;
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    data.forEach((v, i) => {
                        const x = pad.left + (pw / (data.length - 1)) * i;
                        const y = pad.top + ph - (v / maxVal) * ph;
                        if (i === 0) ctx.moveTo(x, y);
                        else ctx.lineTo(x, y);
                    });
                    ctx.stroke();

                    // Dots
                    data.forEach((v, i) => {
                        const x = pad.left + (pw / (data.length - 1)) * i;
                        const y = pad.top + ph - (v / maxVal) * ph;
                        ctx.fillStyle = color;
                        ctx.beginPath();
                        ctx.arc(x, y, 3, 0, Math.PI * 2);
                        ctx.fill();
                    });
                };

                drawLine(articles, '#AA5F3C');
                drawLine(users, '#8B9A7A');
            },
            resizeHandler() { this.renderChart(); }
        }" x-init="window.addEventListener('resize', resizeHandler)" class="overflow-hidden">
            <h3 class="font-semibold theme-text-primary mb-4">Tren Bulanan (12 Bulan)</h3>
            <div class="flex items-center gap-4 mb-3 text-xs">
                <span class="flex items-center gap-1"><span class="w-3 h-0.5 inline-block rounded" style="background:#AA5F3C"></span> Articles</span>
                <span class="flex items-center gap-1"><span class="w-3 h-0.5 inline-block rounded" style="background:#8B9A7A"></span> Users</span>
            </div>
            <canvas x-ref="chart" class="w-full" style="height:250px"></canvas>
        </div>

        {{-- Top Contributors --}}
        <div class="card p-6">
            <h3 class="font-semibold theme-text-primary mb-4">Top Contributors</h3>
            <div class="space-y-3">
                @forelse ($topUsers as $i => $u)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-xs font-semibold theme-text-muted w-5">{{ $i + 1 }}</span>
                            <span class="text-sm theme-text-primary truncate">{{ $u->name }}</span>
                        </div>
                        <span class="text-xs font-medium theme-text-secondary">{{ $u->articles_count }} articles</span>
                    </div>
                @empty
                    <div class="text-sm theme-text-muted">Belum ada data.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Registrations --}}
        <div class="card p-6">
            <h3 class="font-semibold theme-text-primary mb-4">User Terbaru</h3>
            <div class="space-y-3">
                @forelse ($recentUsers as $u)
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <div class="text-sm font-medium theme-text-primary truncate">{{ $u->name }}</div>
                            <div class="text-xs theme-text-muted truncate">{{ $u->email }}</div>
                        </div>
                        <span class="text-xs theme-text-muted whitespace-nowrap">{{ $u->created_at->format('d M Y') }}</span>
                    </div>
                @empty
                    <div class="text-sm theme-text-muted">Belum ada user.</div>
                @endforelse
            </div>
        </div>

        {{-- Article Status Breakdown --}}
        <div class="card p-6">
            <h3 class="font-semibold theme-text-primary mb-4">Status Artikel</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm theme-text-secondary">Ready</span>
                    <span class="text-sm font-semibold text-[#8B9A7A]">{{ $articleStats['ready'] }}</span>
                </div>
                <div class="w-full bg-[var(--border-primary)] rounded-full h-2">
                    <div class="bg-[#8B9A7A] h-2 rounded-full" style="width: {{ $totalArticles > 0 ? round($articleStats['ready'] / $totalArticles * 100) : 0 }}%"></div>
                </div>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-sm theme-text-secondary">Processing</span>
                    <span class="text-sm font-semibold text-[#D4A76A]">{{ $articleStats['processing'] }}</span>
                </div>
                <div class="w-full bg-[var(--border-primary)] rounded-full h-2">
                    <div class="bg-[#D4A76A] h-2 rounded-full" style="width: {{ $totalArticles > 0 ? round($articleStats['processing'] / $totalArticles * 100) : 0 }}%"></div>
                </div>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-sm theme-text-secondary">Failed</span>
                    <span class="text-sm font-semibold text-[color:var(--error)]">{{ $articleStats['failed'] }}</span>
                </div>
                <div class="w-full bg-[var(--border-primary)] rounded-full h-2">
                    <div class="bg-[color:var(--error)] h-2 rounded-full" style="width: {{ $totalArticles > 0 ? round($articleStats['failed'] / $totalArticles * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
