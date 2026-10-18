<!-- ================================================================= -->
<!-- KOMPONEN MODAL IN-APP PREVIEW BERKAS (LIGHTBOX VIEWER)            -->
<!-- Mendukung Gambar (JPG/PNG) & PDF Embed Iframe Interaktif          -->
<!-- ================================================================= -->
<div id="inapp-preview-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 transition-all duration-200">
    <div class="relative bg-white rounded-3xl max-w-4xl w-full shadow-2xl border border-slate-700/50 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-150">
        <!-- Header Modal -->
        <div class="bg-slate-900 text-white px-5 py-4 flex items-center justify-between border-b border-slate-800 flex-shrink-0">
            <div class="flex items-center gap-2.5 overflow-hidden pr-3">
                <span id="preview-modal-icon" class="text-xl">👁️</span>
                <div class="overflow-hidden">
                    <h3 id="preview-modal-title" class="text-sm font-bold text-white truncate max-w-md sm:max-w-xl">
                        Preview Dokumen Bukti Lampiran
                    </h3>
                    <p id="preview-modal-subtitle" class="text-[11px] text-slate-400 font-mono truncate">
                        Memuat berkas...
                    </p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 flex-shrink-0">
                <!-- Tombol Unduh Asli Opsional -->
                <a id="preview-modal-download-btn" href="#" target="_blank" download
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 hover:border-slate-600 transition shadow-xs"
                   title="Unduh berkas asli ke penyimpanan lokal">
                    <span>⬇️</span>
                    <span class="hidden sm:inline">Unduh Asli</span>
                </a>
                
                <!-- Tombol Tutup Modal -->
                <button type="button" onclick="closeInAppPreviewModal()"
                        class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-rose-600 text-slate-300 hover:text-white flex items-center justify-center transition focus:outline-hidden cursor-pointer"
                        title="Tutup Preview (Esc)">
                    ✕
                </button>
            </div>
        </div>

        <!-- Body / Container Preview -->
        <div id="preview-modal-body" class="p-4 sm:p-6 bg-slate-950/95 flex-1 overflow-auto flex items-center justify-center min-h-[350px]">
            <!-- Spinner / Loading State -->
            <div id="preview-modal-loading" class="text-center py-12 text-slate-400 text-sm">
                <div class="inline-block animate-spin text-2xl mb-2">⏳</div>
                <p>Memuat tampilan berkas...</p>
            </div>

            <!-- Preview Gambar (JPG / PNG) -->
            <div id="preview-image-container" class="hidden w-full flex items-center justify-center">
                <img id="preview-image-elem" src="" alt="Bukti Lampiran" 
                     class="max-h-[75vh] max-w-full object-contain rounded-xl shadow-2xl border border-slate-800 transition duration-200">
            </div>

            <!-- Preview Dokumen PDF (Iframe Interaktif) -->
            <div id="preview-pdf-container" class="hidden w-full h-[75vh]">
                <iframe id="preview-pdf-iframe" src="" 
                        class="w-full h-full rounded-xl border border-slate-800 bg-white shadow-2xl"
                        frameborder="0">
                </iframe>
            </div>

            <!-- Fallback untuk format lain -->
            <div id="preview-fallback-container" class="hidden text-center p-8 bg-slate-900 rounded-2xl border border-slate-800 max-w-md">
                <div class="text-4xl mb-3">📄</div>
                <h4 class="text-sm font-bold text-white mb-1">Pratinjau Langsung Tidak Didukung</h4>
                <p class="text-xs text-slate-400 mb-4">Berkas ini dapat dibuka atau diunduh langsung untuk melihat isinya.</p>
                <a id="preview-fallback-link" href="#" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                    <span>Buka Berkas di Tab Baru</span> ↗️
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function openInAppPreview(fileUrl, fileName = 'Dokumen Bukti Lampiran') {
        const modal = document.getElementById('inapp-preview-modal');
        if (!modal) return;

        const titleElem = document.getElementById('preview-modal-title');
        const subtitleElem = document.getElementById('preview-modal-subtitle');
        const downloadBtn = document.getElementById('preview-modal-download-btn');
        const iconElem = document.getElementById('preview-modal-icon');

        const loadingElem = document.getElementById('preview-modal-loading');
        const imgContainer = document.getElementById('preview-image-container');
        const imgElem = document.getElementById('preview-image-elem');
        const pdfContainer = document.getElementById('preview-pdf-container');
        const pdfIframe = document.getElementById('preview-pdf-iframe');
        const fallbackContainer = document.getElementById('preview-fallback-container');
        const fallbackLink = document.getElementById('preview-fallback-link');

        // Reset display state
        if (loadingElem) loadingElem.classList.remove('hidden');
        if (imgContainer) imgContainer.classList.add('hidden');
        if (pdfContainer) pdfContainer.classList.add('hidden');
        if (fallbackContainer) fallbackContainer.classList.add('hidden');

        // Set labels
        if (titleElem) titleElem.textContent = fileName;
        if (subtitleElem) subtitleElem.textContent = fileUrl.split('?')[0];

        // Format download URL
        const downloadUrl = fileUrl.includes('?') ? `${fileUrl}&download=1` : `${fileUrl}?download=1`;
        if (downloadBtn) downloadBtn.href = downloadUrl;
        if (fallbackLink) fallbackLink.href = fileUrl;

        // Determine extension reliably (handles query parameters and file names)
        let ext = '';
        try {
            const urlObj = new URL(fileUrl, window.location.origin);
            const fileParam = urlObj.searchParams.get('file');
            if (fileParam && fileParam.includes('.')) {
                ext = fileParam.split('.').pop().toLowerCase();
            }
        } catch (e) {}

        if (!ext) {
            const cleanPath = fileUrl.split('?')[0];
            if (cleanPath.includes('.')) {
                ext = cleanPath.split('.').pop().toLowerCase();
            }
        }

        if (!ext && fileName && fileName.includes('.')) {
            ext = fileName.split('.').pop().toLowerCase();
        }

        if (['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(ext)) {
            if (iconElem) iconElem.textContent = '🖼️';
            imgElem.onload = () => {
                if (loadingElem) loadingElem.classList.add('hidden');
                if (imgContainer) imgContainer.classList.remove('hidden');
            };
            imgElem.onerror = () => {
                if (loadingElem) loadingElem.classList.add('hidden');
                if (fallbackContainer) fallbackContainer.classList.remove('hidden');
            };
            imgElem.src = fileUrl;
        } else if (ext === 'pdf') {
            if (iconElem) iconElem.textContent = '📄';
            if (loadingElem) loadingElem.classList.add('hidden');
            if (pdfContainer) pdfContainer.classList.remove('hidden');
            pdfIframe.src = fileUrl;
        } else {
            if (iconElem) iconElem.textContent = '📎';
            if (loadingElem) loadingElem.classList.add('hidden');
            if (fallbackContainer) fallbackContainer.classList.remove('hidden');
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeInAppPreviewModal() {
        const modal = document.getElementById('inapp-preview-modal');
        if (!modal) return;

        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');

        const imgElem = document.getElementById('preview-image-elem');
        const pdfIframe = document.getElementById('preview-pdf-iframe');
        if (imgElem) imgElem.src = '';
        if (pdfIframe) pdfIframe.src = '';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const previewModal = document.getElementById('inapp-preview-modal');
            if (previewModal && !previewModal.classList.contains('hidden')) {
                closeInAppPreviewModal();
            }
        }
    });
</script>
