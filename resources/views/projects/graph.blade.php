<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold text-xl theme-text-primary leading-tight truncate">
                    Peta Pengetahuan: {{ $project->title }}
                </h2>
                <p class="text-sm theme-text-muted mt-1">
                    Lihat bagaimana artikel-artikelmu saling terhubung
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary text-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m2 14l7-7m-7 7l-7-7"/></svg>
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Legend & Stats --}}
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mb-4 text-xs theme-text-muted">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#9E735B]"></span> Utama
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#A89880]"></span> Kutipan
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#7A8B7A]"></span> Inspirasi
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#8C7E6E]"></span> Referensi
                </span>
                <span class="ml-auto flex items-center gap-3">
                    <span id="node-count">—</span> artikel
                    <span>·</span>
                    <span id="edge-count">—</span> hubungan
                </span>
            </div>

            {{-- Graph Container --}}
            <div class="card rounded-2xl overflow-hidden relative border theme-border-primary" style="height: 32rem; background: var(--bg-tertiary, #f5f2ed);">
                <div id="cy" class="w-full h-full"></div>

                {{-- Loading Overlay --}}
                <div id="graph-loading" class="absolute inset-0 flex items-center justify-center z-10 transition-opacity duration-300" style="background: var(--surface-primary, #fff);">
                    <div class="text-center">
                        <svg class="animate-spin w-8 h-8 text-[#AA5F3C] mx-auto mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>
                        <p class="text-sm theme-text-muted">Membangun peta...</p>
                    </div>
                </div>

                {{-- Controls --}}
                <div class="absolute bottom-4 left-4 flex flex-col gap-1.5 z-20">
                    <button onclick="cy.zoom(cy.zoom() + 0.3);" class="w-8 h-8 rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] hover:bg-[color:var(--hover-bg)] flex items-center justify-center shadow-sm transition" title="Perbesar">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <button onclick="cy.zoom(cy.zoom() - 0.3);" class="w-8 h-8 rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] hover:bg-[color:var(--hover-bg)] flex items-center justify-center shadow-sm transition" title="Perkecil">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </button>
                    <button onclick="cy.fit(cy.elements(), 40);" class="w-8 h-8 rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] hover:bg-[color:var(--hover-bg)] flex items-center justify-center shadow-sm transition" title="Reset tampilan">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v16H4z" opacity="0.3"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h10v10H4z"/></svg>
                    </button>
                </div>

                {{-- Detail Panel --}}
                <div
                    id="node-detail"
                    class="absolute top-4 right-4 w-80 rounded-2xl border theme-border-primary bg-[color:var(--surface-primary)] shadow-2xl transform translate-x-[calc(100%+2rem)] transition-transform duration-300 ease-out z-20"
                >
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="min-w-0">
                                <h3 id="detail-title" class="text-sm font-semibold theme-text-primary leading-snug"></h3>
                                <div class="flex items-center gap-2 mt-1.5">
                                    <span id="detail-role-badge" class="text-[10px] px-2 py-0.5 rounded-full font-medium"></span>
                                    <span id="detail-tags" class="text-[10px] theme-text-muted"></span>
                                </div>
                            </div>
                            <button onclick="closeDetail()" class="shrink-0 p-1 rounded-lg hover:bg-[color:var(--hover-bg)] text-[color:var(--text-muted)] transition">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="border-t theme-border-primary pt-3">
                            <p id="detail-summary" class="text-sm theme-text-secondary leading-relaxed line-clamp-6"></p>
                        </div>

                        <div id="detail-key-points" class="flex flex-wrap gap-1.5 mt-3 pt-3 border-t theme-border-primary"></div>

                        <a id="detail-link" href="#" class="mt-4 btn btn-primary text-xs w-full text-center block py-2 rounded-xl">
                            Buka Artikel
                        </a>
                    </div>
                </div>
            </div>

            {{-- How to read --}}
            <div class="mt-4 rounded-xl border border-dashed theme-border-primary bg-[color:var(--bg-tertiary)] px-5 py-4">
                <h4 class="text-sm font-medium theme-text-primary mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Cara Membaca Peta Ini
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs theme-text-secondary">
                    <div class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-full bg-[#9E735B] shrink-0 mt-0.5"></span>
                        <span><strong>Warna titik</strong> menunjukkan peran artikel: utama, kutipan, inspirasi, atau referensi.</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="w-8 h-px bg-[#C4B5A0] shrink-0 mt-2.5"></span>
                        <span><strong>Garis tipis</strong> artinya artikel punya tag yang sama. <strong>Garis putus-putus</strong> artinya saling mengutip.</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 text-[#9E735B]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                        <span><strong>Klik titik</strong> untuk melihat ringkasan. <strong>Scroll</strong> untuk zoom. <strong>Drag</strong> untuk geser.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cytoscape.js Local --}}
    <script src="{{ asset('js/cytoscape.min.js') }}"></script>

    <script>
    let cy = null;

    document.addEventListener('DOMContentLoaded', async () => {
        const projectId = {{ $project->id }};
        const cyContainer = document.getElementById('cy');
        const loading = document.getElementById('graph-loading');
        const detailPanel = document.getElementById('node-detail');

        if (typeof cytoscape === 'undefined') {
            loading.innerHTML = `
                <div class="text-center">
                    <svg class="w-8 h-8 text-red-500 mx-auto mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm theme-text-muted">Gagal memuat library graph.</p>
                </div>
            `;
            return;
        }

        try {
            const res = await fetch(`/projects/${projectId}/graph/data`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();

            if (!json.success) throw new Error('Failed to load graph data');

            const data = json.data;
            document.getElementById('node-count').textContent = data.stats.node_count;
            document.getElementById('edge-count').textContent = data.stats.edge_count;

            if (!data.nodes || data.nodes.length === 0) {
                loading.innerHTML = `
                    <div class="text-center">
                        <svg class="w-12 h-12 text-[#AA5F3C] mx-auto mb-3 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm theme-text-muted">Belum ada artikel dalam project ini.</p>
                        <a href="{{ route('projects.show', $project) }}" class="mt-3 inline-flex text-xs text-[#AA5F3C] hover:underline">Tambahkan artikel &rarr;</a>
                    </div>
                `;
                return;
            }

            // Build elements
            const elements = [
                ...data.nodes.map(n => ({ data: {
                    id: n.id, label: n.label, fullTitle: n.full_title,
                    articleId: n.article_id, size: n.size, color: n.color,
                    summary: n.summary, keyPoints: n.key_points,
                    role: n.role, roleLabel: n.role_label,
                }})),
                ...data.edges.map(e => ({ data: {
                    source: e.source, target: e.target,
                    weight: e.weight, type: e.type, color: e.color,
                }})),
            ];

            // Detect dark mode
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const bgColor = isDark ? '#1c1917' : '#f5f2ed';
            const textColor = isDark ? '#e8e2da' : '#3d3429';

            cy = cytoscape({
                container: cyContainer,
                elements: elements,
                style: [
                    {
                        selector: 'node',
                        style: {
                            'background-color': 'data(color)',
                            'width': 'data(size)',
                            'height': 'data(size)',
                            'border-width': 2,
                            'border-color': bgColor,
                            'label': '', // no label by default
                            'transition-property': 'width, height, border-width, background-color',
                            'transition-duration': '0.25s',
                        }
                    },
                    {
                        selector: 'node:selected, node.hover',
                        style: {
                            'width': 'mapData(size, 18, 42, 26, 52)',
                            'height': 'mapData(size, 18, 42, 26, 52)',
                            'border-width': 3,
                            'border-color': textColor,
                            'label': 'data(label)',
                            'color': textColor,
                            'font-size': '10px',
                            'font-weight': '500',
                            'text-valign': 'bottom',
                            'text-halign': 'center',
                            'text-margin-y': '6px',
                            'text-background-color': bgColor,
                            'text-background-opacity': 0.85,
                            'text-background-padding': '3px 6px',
                            'text-background-shape': 'roundrectangle',
                        }
                    },
                    {
                        selector: 'edge',
                        style: {
                            'width': 'mapData(weight, 1, 5, 1, 2.5)',
                            'line-color': 'data(color)',
                            'target-arrow-color': 'data(color)',
                            'target-arrow-shape': 'vee',
                            'target-arrow-fill': 'hollow',
                            'arrow-scale': 0.5,
                            'curve-style': 'bezier',
                            'opacity': 0.55,
                        }
                    },
                    {
                        selector: 'edge[!color]',
                        style: {
                            'line-color': isDark ? '#C4B5A0' : '#C4B5A0',
                            'target-arrow-color': isDark ? '#C4B5A0' : '#C4B5A0',
                        }
                    },
                    {
                        selector: 'edge[type = "citation_link"]',
                        style: {
                            'line-style': 'dashed',
                            'line-dash-pattern': [4, 4],
                            'opacity': 0.7,
                        }
                    },
                    {
                        selector: 'edge[type = "project_group"]',
                        style: {
                            'line-style': 'dotted',
                            'line-dash-pattern': [2, 6],
                            'opacity': 0.35,
                        }
                    },
                ],
                layout: {
                    name: 'cose',
                    padding: 30,
                    nodeRepulsion: 8000,
                    idealEdgeLength: 80,
                    edgeElasticity: 0.45,
                    nestingFactor: 1.2,
                    gravity: 0.25,
                    numIter: 3000,
                    initialTemp: 150,
                    coolingFactor: 0.96,
                    minTemp: 1.0,
                    animate: false,
                    componentSpacing: 60,
                },
                wheelSensitivity: 0.25,
                minZoom: 0.25,
                maxZoom: 4,
            });

            // Hover behavior (desktop)
            cy.on('mouseover', 'node', function(evt) {
                evt.target.addClass('hover');
            });
            cy.on('mouseout', 'node', function(evt) {
                evt.target.removeClass('hover');
            });

            // Click node
            cy.on('tap', 'node', function(evt) {
                evt.target.select();
                showDetail(evt.target.data());
            });

            // Click background
            cy.on('tap', function(evt) {
                if (evt.target === cy) {
                    cy.nodes().unselect();
                    closeDetail();
                }
            });

            loading.style.opacity = '0';
            setTimeout(() => loading.style.display = 'none', 300);

        } catch (e) {
            console.error('Graph error:', e);
            loading.innerHTML = `
                <div class="text-center">
                    <svg class="w-8 h-8 text-red-500 mx-auto mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm theme-text-muted">Gagal memuat graph. Refresh halaman untuk mencoba lagi.</p>
                </div>
            `;
        }

        function showDetail(data) {
            document.getElementById('detail-title').textContent = data.fullTitle || data.label;

            const badge = document.getElementById('detail-role-badge');
            badge.textContent = data.roleLabel || 'Referensi';
            badge.style.backgroundColor = data.color + '20';
            badge.style.color = data.color;

            document.getElementById('detail-summary').textContent = data.summary || 'Tidak ada ringkasan.';

            const kpContainer = document.getElementById('detail-key-points');
            kpContainer.innerHTML = '';
            if (data.keyPoints && data.keyPoints.length) {
                data.keyPoints.forEach(point => {
                    const span = document.createElement('span');
                    span.className = 'text-[10px] px-2 py-0.5 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary border theme-border-primary';
                    span.textContent = point;
                    kpContainer.appendChild(span);
                });
            }

            document.getElementById('detail-link').href = `/articles/${data.articleId}`;
            detailPanel.classList.remove('translate-x-[calc(100%+2rem)]');
        }

        window.closeDetail = function() {
            detailPanel.classList.add('translate-x-[calc(100%+2rem)]');
            if (cy) cy.nodes().unselect();
        };
    });
    </script>
</x-app-layout>
