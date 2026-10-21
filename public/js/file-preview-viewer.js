/**
 * file-preview-viewer.js
 * Modal Pratinjau Bukti Tanpa Unduh (Secure In-Browser Derivative Viewer)
 * Mendukung Gambar (Zoom/Pan/Pinch), PDF (Paging Raster), Video Player, Teks Kode
 */

(function () {
    'use strict';

    var currentFiles = [];
    var currentIndex = 0;
    var currentScale = 1;
    var currentPage = 1;
    var isDragging = false;
    var startX = 0, startY = 0, translateX = 0, translateY = 0;

    function createModalDom() {
        if (document.getElementById('secure-preview-modal')) return;

        var modal = document.createElement('div');
        modal.id = 'secure-preview-modal';
        modal.className = 'fixed inset-0 z-50 hidden bg-slate-950/85 backdrop-blur-md flex flex-col items-center justify-between p-2 sm:p-4 text-white font-sans transition-opacity duration-200 select-none';
        modal.innerHTML = `
            <!-- Header Bar -->
            <div class="w-full max-w-6xl flex items-center justify-between py-2 px-3 bg-slate-900/90 rounded-2xl border border-slate-800 shadow-xl z-10">
                <div class="flex items-center gap-3 overflow-hidden">
                    <span id="preview-type-badge" class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-indigo-600 text-white">GAMBAR</span>
                    <span id="preview-class-badge" class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-emerald-600 text-white">UMUM</span>
                    <span id="preview-filename" class="text-xs sm:text-sm font-mono font-medium truncate max-w-[200px] sm:max-w-md text-slate-200">file.jpg</span>
                    <span id="preview-page-indicator" class="text-xs text-slate-400 hidden">Hal 1 dari 1</span>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Zoom Controls -->
                    <div id="preview-zoom-controls" class="hidden sm:flex items-center bg-slate-800 rounded-xl p-0.5 border border-slate-700">
                        <button type="button" id="btn-zoom-out" class="p-1.5 hover:bg-slate-700 rounded-lg text-slate-300 hover:text-white" title="Perkecil">−</button>
                        <span id="zoom-level-text" class="text-[11px] font-mono px-2 text-slate-300">100%</span>
                        <button type="button" id="btn-zoom-in" class="p-1.5 hover:bg-slate-700 rounded-lg text-slate-300 hover:text-white" title="Perbesar">+</button>
                        <button type="button" id="btn-zoom-reset" class="p-1.5 hover:bg-slate-700 rounded-lg text-slate-300 hover:text-white text-[11px]" title="Reset">1:1</button>
                    </div>

                    <!-- Tombol Unduh Asli Sekunder (Jika berhak) -->
                    <a id="btn-download-original" href="#" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition">
                        <span>⬇ Unduh Asli</span>
                    </a>

                    <!-- Tombol Tutup (Esc) -->
                    <button type="button" id="btn-close-preview" class="p-2 hover:bg-rose-900/60 text-slate-400 hover:text-rose-200 rounded-xl transition" title="Tutup (Esc)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Content Viewer Body -->
            <div id="preview-body-container" class="relative w-full max-w-6xl flex-1 flex items-center justify-center my-3 overflow-hidden rounded-2xl bg-black/40 border border-slate-800/80">
                <!-- Nav Panah Kiri -->
                <button type="button" id="btn-prev-file" class="absolute left-3 z-20 p-3 rounded-full bg-slate-900/80 hover:bg-indigo-600 text-white shadow-lg transition disabled:opacity-30 disabled:pointer-events-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>

                <!-- Media Wrapper -->
                <div id="preview-media-viewport" class="w-full h-full flex items-center justify-center cursor-grab active:cursor-grabbing p-2">
                    <img id="preview-img-element" src="" alt="Pratinjau Bukti" class="max-h-full max-w-full object-contain transition-transform duration-75 select-none" draggable="false">
                    <video id="preview-video-element" controls class="max-h-full max-w-full hidden rounded-lg shadow-2xl" playsinline></video>
                    <div id="preview-text-element" class="hidden w-full h-full overflow-auto p-4 font-mono text-xs text-slate-200 bg-slate-900/90 rounded-xl leading-relaxed whitespace-pre-wrap"></div>
                    <div id="preview-loading-spinner" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/60 z-30">
                        <div class="animate-spin rounded-full h-10 w-10 border-4 border-indigo-500 border-t-transparent mb-2"></div>
                        <span class="text-xs text-slate-400">Memuat turunan aman...</span>
                    </div>
                </div>

                <!-- Nav Panah Kanan -->
                <button type="button" id="btn-next-file" class="absolute right-3 z-20 p-3 rounded-full bg-slate-900/80 hover:bg-indigo-600 text-white shadow-lg transition disabled:opacity-30 disabled:pointer-events-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <!-- Footer Pagination Bar -->
            <div class="w-full max-w-6xl flex items-center justify-between py-1.5 px-3 bg-slate-900/80 rounded-xl border border-slate-800 text-[11px] text-slate-400">
                <div class="flex items-center gap-2 font-mono">
                    <span id="preview-counter">Berkas 1 dari 1</span>
                    <span id="preview-sha256" class="hidden sm:inline text-slate-500 text-[10px] truncate max-w-sm">SHA: -</span>
                </div>
                <div id="preview-pdf-nav" class="hidden flex items-center gap-2">
                    <button type="button" id="btn-pdf-prev" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 rounded text-slate-200">◀ Hal Sebelumnya</button>
                    <span id="preview-pdf-page-num" class="font-bold text-slate-200">Hal 1</span>
                    <button type="button" id="btn-pdf-next" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 rounded text-slate-200">Hal Berikutnya ▶</button>
                </div>
                <div class="text-[10px] text-slate-400">Tekan <kbd class="px-1 py-0.5 bg-slate-800 rounded text-slate-300">Esc</kbd> untuk menutup</div>
            </div>
        `;

        document.body.appendChild(modal);
        bindModalEvents();
    }

    function bindModalEvents() {
        var modal = document.getElementById('secure-preview-modal');
        var btnClose = document.getElementById('btn-close-preview');
        var btnPrev = document.getElementById('btn-prev-file');
        var btnNext = document.getElementById('btn-next-file');
        var btnZoomIn = document.getElementById('btn-zoom-in');
        var btnZoomOut = document.getElementById('btn-zoom-out');
        var btnZoomReset = document.getElementById('btn-zoom-reset');
        var viewport = document.getElementById('preview-media-viewport');
        var img = document.getElementById('preview-img-element');

        btnClose.addEventListener('click', closeViewer);
        btnPrev.addEventListener('click', prevFile);
        btnNext.addEventListener('click', nextFile);

        btnZoomIn.addEventListener('click', function () { adjustZoom(0.25); });
        btnZoomOut.addEventListener('click', function () { adjustZoom(-0.25); });
        btnZoomReset.addEventListener('click', function () { resetZoom(); });

        // Wheel Zoom
        viewport.addEventListener('wheel', function (e) {
            if (img.classList.contains('hidden')) return;
            e.preventDefault();
            adjustZoom(e.deltaY < 0 ? 0.15 : -0.15);
        }, { passive: false });

        // Drag Pan
        viewport.addEventListener('mousedown', function (e) {
            if (currentScale <= 1) return;
            isDragging = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
        });

        window.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            updateTransform();
        });

        window.addEventListener('mouseup', function () {
            isDragging = false;
        });

        // Keyboard Shortcut: Left, Right, Esc
        window.addEventListener('keydown', function (e) {
            if (modal.classList.contains('hidden')) return;
            if (e.key === 'Escape') closeViewer();
            if (e.key === 'ArrowLeft') prevFile();
            if (e.key === 'ArrowRight') nextFile();
        });

        // PDF Page Nav
        document.getElementById('btn-pdf-prev').addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                renderActiveFile();
            }
        });
        document.getElementById('btn-pdf-next').addEventListener('click', function () {
            var f = currentFiles[currentIndex];
            if (f && currentPage < (f.pages || 1)) {
                currentPage++;
                renderActiveFile();
            }
        });
    }

    function adjustZoom(delta) {
        currentScale = Math.max(0.5, Math.min(4.0, currentScale + delta));
        updateTransform();
    }

    function resetZoom() {
        currentScale = 1;
        translateX = 0;
        translateY = 0;
        updateTransform();
    }

    function updateTransform() {
        var img = document.getElementById('preview-img-element');
        var zoomText = document.getElementById('zoom-level-text');
        img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
        zoomText.textContent = Math.round(currentScale * 100) + '%';
    }

    function renderActiveFile() {
        if (!currentFiles.length || currentIndex < 0 || currentIndex >= currentFiles.length) return;

        var f = currentFiles[currentIndex];
        var img = document.getElementById('preview-img-element');
        var vid = document.getElementById('preview-video-element');
        var txt = document.getElementById('preview-text-element');
        var spinner = document.getElementById('preview-loading-spinner');
        var btnPrev = document.getElementById('btn-prev-file');
        var btnNext = document.getElementById('btn-next-file');
        var pdfNav = document.getElementById('preview-pdf-nav');
        var zoomControls = document.getElementById('preview-zoom-controls');
        var btnDownload = document.getElementById('btn-download-original');

        btnPrev.disabled = currentIndex === 0;
        btnNext.disabled = currentIndex === currentFiles.length - 1;

        document.getElementById('preview-counter').textContent = `Berkas ${currentIndex + 1} dari ${currentFiles.length}`;
        document.getElementById('preview-filename').textContent = f.name || 'Berkas Lampiran';
        document.getElementById('preview-type-badge').textContent = (f.type || 'BERKAS').toUpperCase();

        var classBadge = document.getElementById('preview-class-badge');
        var cl = (f.classification || 'umum').toLowerCase();
        classBadge.textContent = cl.toUpperCase();
        if (cl === 'rahasia') {
            classBadge.className = 'px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-rose-600 text-white';
        } else if (cl === 'terbatas') {
            classBadge.className = 'px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-amber-600 text-white';
        } else {
            classBadge.className = 'px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-emerald-600 text-white';
        }

        var shaEl = document.getElementById('preview-sha256');
        if (f.sha256) {
            shaEl.textContent = `SHA-256: ${f.sha256.substring(0, 16)}...`;
            shaEl.classList.remove('hidden');
        } else {
            shaEl.classList.add('hidden');
        }

        if (f.downloadUrl) {
            btnDownload.href = f.downloadUrl;
            btnDownload.classList.remove('hidden');
        } else {
            btnDownload.classList.add('hidden');
        }

        // Reset elements
        resetZoom();
        img.classList.add('hidden');
        vid.classList.add('hidden');
        vid.pause();
        txt.classList.add('hidden');
        spinner.classList.remove('hidden');

        var previewUrl = f.previewUrl;
        if (f.type === 'pdf') {
            previewUrl += (previewUrl.includes('?') ? '&' : '?') + 'page=' + currentPage;
            pdfNav.classList.remove('hidden');
            zoomControls.classList.remove('hidden');
            document.getElementById('preview-pdf-page-num').textContent = `Hal ${currentPage} dari ${f.pages || 1}`;
        } else {
            pdfNav.classList.add('hidden');
        }

        if (f.type === 'image' || f.type === 'pdf') {
            zoomControls.classList.remove('hidden');
            img.onload = function () {
                spinner.classList.add('hidden');
                img.classList.remove('hidden');
            };
            img.onerror = function () {
                spinner.innerHTML = '<span class="text-rose-400 text-xs">Gagal memuat pratinjau derivatif.</span>';
            };
            img.src = previewUrl;
        } else if (f.type === 'text') {
            zoomControls.classList.add('hidden');
            fetch(previewUrl)
                .then(r => r.text())
                .then(content => {
                    spinner.classList.add('hidden');
                    txt.textContent = content;
                    txt.classList.remove('hidden');
                })
                .catch(() => {
                    spinner.innerHTML = '<span class="text-rose-400 text-xs">Gagal membaca teks pratinjau.</span>';
                });
        } else if (f.type === 'video') {
            zoomControls.classList.add('hidden');
            vid.src = previewUrl;
            vid.onloadeddata = function () {
                spinner.classList.add('hidden');
                vid.classList.remove('hidden');
            };
        }
    }

    function prevFile() {
        if (currentIndex > 0) {
            currentIndex--;
            currentPage = 1;
            renderActiveFile();
        }
    }

    function nextFile() {
        if (currentIndex < currentFiles.length - 1) {
            currentIndex++;
            currentPage = 1;
            renderActiveFile();
        }
    }

    function openViewer(files, startIndex) {
        createModalDom();
        currentFiles = files || [];
        currentIndex = startIndex || 0;
        currentPage = 1;

        var modal = document.getElementById('secure-preview-modal');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        renderActiveFile();
    }

    function closeViewer() {
        var modal = document.getElementById('secure-preview-modal');
        if (modal) {
            modal.classList.add('hidden');
            var vid = document.getElementById('preview-video-element');
            if (vid) vid.pause();
        }
        document.body.style.overflow = '';
    }

    window.SecureFilePreviewViewer = {
        open: openViewer,
        close: closeViewer
    };
})();
