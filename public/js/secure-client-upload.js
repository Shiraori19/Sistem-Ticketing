/**
 * secure-client-upload.js
 * Modul Kompresi Gambar Client-Side & Penanganan Unggahan Aman
 * Tanpa CDN eksternal (Pure Vanilla JS HTML5 Canvas) - Sesuai Kebijakan CSP
 */

(function () {
    'use strict';

    window.SecureClientUpload = {
        /**
         * Validasi Mendalam Client-Side Berdasarkan Magic Bytes dan Integritas Format
         * @param {File} file
         * @returns {Promise<{valid: boolean, error?: string, detectedFormat?: string}>}
         */
        validateFile: async function (file) {
            if (!file) return { valid: false, error: 'Berkas tidak ditemukan.' };

            var originalName = file.name || 'berkas';
            var parts = originalName.split('.');

            // 1. Cegah ekstensi ganda (double extension)
            if (parts.length > 2) {
                return {
                    valid: false,
                    error: 'Berkas "' + originalName + '" ditolak: Berkas dengan ekstensi ganda (. ' + parts.slice(1).join('.') + ') dilarang demi keamanan.'
                };
            }

            var ext = parts.pop().toLowerCase();
            var allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!allowedExtensions.includes(ext)) {
                return {
                    valid: false,
                    error: 'Format berkas "' + originalName + '" (.' + ext + ') tidak diizinkan. Hanya menerima format JPG, PNG, atau PDF.'
                };
            }

            if (file.size <= 0) {
                return {
                    valid: false,
                    error: 'Ukuran berkas "' + originalName + '" 0 byte (kosong) tidak diizinkan.'
                };
            }

            // Batas berkas PDF tidak dapat dikompresi di browser, maksimal 1 MB
            if (ext === 'pdf' && file.size > 1024 * 1024) {
                return {
                    valid: false,
                    error: 'Ukuran berkas PDF "' + originalName + '" (' + (file.size / 1024 / 1024).toFixed(2) + ' MB) melebihi batas maksimal 1 MB per berkas. Berkas PDF tidak dapat dikompresi di peramban, silakan gunakan dokumen di bawah 1 MB.'
                };
            }

            // Batas gambar sebelum kompresi (maksimal 10 MB untuk mencegah crash canvas browser)
            var maxInputSizeBytes = 10 * 1024 * 1024;
            if (file.size > maxInputSizeBytes) {
                return {
                    valid: false,
                    error: 'Ukuran berkas "' + originalName + '" (' + (file.size / 1024 / 1024).toFixed(2) + ' MB) melebihi batas maksimal unggahan 10 MB.'
                };
            }

            try {
                var slice = file.slice(0, 16);
                var buffer = await slice.arrayBuffer();
                var bytes = new Uint8Array(buffer);

                if (bytes.length < 4) {
                    return {
                        valid: false,
                        error: 'Berkas "' + originalName + '" rusak atau corrupt (ukuran biner tidak mencukupi).'
                    };
                }

                // File Signature Checks:
                var isJpg = bytes[0] === 0xFF && bytes[1] === 0xD8 && bytes[2] === 0xFF;
                var isPng = bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4E && bytes[3] === 0x47 &&
                            bytes[4] === 0x0D && bytes[5] === 0x0A && bytes[6] === 0x1A && bytes[7] === 0x0A;
                var isPdf = bytes[0] === 0x25 && bytes[1] === 0x50 && bytes[2] === 0x44 && bytes[3] === 0x46 && bytes[4] === 0x2D;

                // Tolak Dokumen Office (DOCX/XLSX) atau ZIP (PK\x03\x04: 50 4B)
                if (bytes[0] === 0x50 && bytes[1] === 0x4B) {
                    return {
                        valid: false,
                        error: 'Berkas "' + originalName + '" ditolak! Terdeteksi sebagai dokumen Office (Word/Excel) atau arsip ZIP yang diganti ekstensinya. Silakan simpan sebagai PDF asli terlebih dahulu.'
                    };
                }

                // Tolak Executable (MZ: 4D 5A)
                if (bytes[0] === 0x4D && bytes[1] === 0x5A) {
                    return {
                        valid: false,
                        error: 'Berkas "' + originalName + '" ditolak! Terdeteksi sebagai program eksekusi (EXE/DLL) yang dilarang.'
                    };
                }

                // Tolak Kontainer Video MP4 (ftyp)
                var isMp4 = false;
                for (var i = 0; i < bytes.length - 3; i++) {
                    if (bytes[i] === 0x66 && bytes[i+1] === 0x74 && bytes[i+2] === 0x79 && bytes[i+3] === 0x70) {
                        isMp4 = true;
                        break;
                    }
                }
                if (isMp4) {
                    return {
                        valid: false,
                        error: 'Berkas "' + originalName + '" ditolak! Terdeteksi sebagai berkas video MP4 yang diganti ekstensinya.'
                    };
                }

                var detectedFormat = null;
                if (isJpg) detectedFormat = 'jpg';
                else if (isPng) detectedFormat = 'png';
                else if (isPdf) detectedFormat = 'pdf';

                if (!detectedFormat) {
                    return {
                        valid: false,
                        error: 'Berkas "' + originalName + '" ditolak! Isi berkas bukan gambar (JPG/PNG) atau PDF yang sah (manipulasi signature biner terdeteksi).'
                    };
                }

                var allowedForExt = detectedFormat === 'jpg' ? ['jpg', 'jpeg'] : [detectedFormat];
                if (!allowedForExt.includes(ext)) {
                    return {
                        valid: false,
                        error: 'Pemalsuan ekstensi terdeteksi pada "' + originalName + '". Ekstensi (.' + ext + ') tidak sesuai dengan isi berkas aslinya (.' + detectedFormat + ').'
                    };
                }

                // Uji Decode Gambar
                if (detectedFormat === 'jpg' || detectedFormat === 'png') {
                    var decodeResult = await new Promise(function (resolve) {
                        var img = new Image();
                        var blobUrl = URL.createObjectURL(file);
                        img.onload = function () {
                            URL.revokeObjectURL(blobUrl);
                            if (img.width > 6000 || img.height > 6000) {
                                resolve({ ok: false, error: 'Dimensi gambar "' + originalName + '" (' + img.width + 'x' + img.height + ' px) melebihi batas aman 6000x6000 px.' });
                            } else if (img.width <= 0 || img.height <= 0) {
                                resolve({ ok: false, error: 'Dimensi gambar "' + originalName + '" tidak valid.' });
                            } else {
                                resolve({ ok: true });
                            }
                        };
                        img.onerror = function () {
                            URL.revokeObjectURL(blobUrl);
                            resolve({ ok: false, error: 'Berkas gambar "' + originalName + '" rusak, corrupt, atau bukan gambar valid.' });
                        };
                        img.src = blobUrl;
                    });

                    if (!decodeResult.ok) {
                        return { valid: false, error: decodeResult.error };
                    }
                }

                // Uji Objek Berbahaya pada PDF
                if (detectedFormat === 'pdf') {
                    var textChunk = await file.slice(0, Math.min(file.size, 512 * 1024)).text();
                    if (/\/JavaScript/i.test(textChunk) || /\/JS\b/i.test(textChunk) || /\/Launch\b/i.test(textChunk) || /\/EmbeddedFiles/i.test(textChunk)) {
                        return {
                            valid: false,
                            error: 'Dokumen PDF "' + originalName + '" ditolak karena memuat elemen skrip aktif atau lampiran terlarang.'
                        };
                    }
                }

                return { valid: true, detectedFormat: detectedFormat };
            } catch (err) {
                return {
                    valid: false,
                    error: 'Gagal memvalidasi biner berkas "' + originalName + '": ' + err.message
                };
            }
        },

        /**
         * Kompres berkas gambar tunggal jika berformat JPEG / PNG
         * @param {File} file
         * @param {Object} options { maxDimension: 1920, targetSizeBytes: 1048576, quality: 0.82 }
         * @returns {Promise<File>}
         */
        compressImage: function (file, options) {
            options = Object.assign({
                maxDimension: 1920,
                targetSizeBytes: 950 * 1024, // < 1 MB target aman
                quality: 0.80
            }, options || {});

            return new Promise(function (resolve, reject) {
                // Lewatkan jika bukan gambar JPEG / PNG
                if (!file.type.match(/^image\/(jpeg|png|jpg)$/i)) {
                    return resolve(file);
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    var img = new Image();
                    img.onload = function () {
                        var w = img.width;
                        var h = img.height;

                        // Hitung dimensi target proporsional
                        if (w > options.maxDimension || h > options.maxDimension) {
                            if (w > h) {
                                h = Math.round((h / w) * options.maxDimension);
                                w = options.maxDimension;
                            } else {
                                w = Math.round((w / h) * options.maxDimension);
                                h = options.maxDimension;
                            }
                        }

                        // Gambar ke Canvas (Membuang seluruh metadata EXIF & GPS secara alami)
                        var canvas = document.createElement('canvas');
                        canvas.width = w;
                        canvas.height = h;
                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, w, h);

                        // Ekspor bertahap sampai mendekati target ukuran
                        var currentQuality = options.quality;
                        canvas.toBlob(function (blob) {
                            if (!blob) {
                                return resolve(file);
                            }

                            // Jika hasil masih > targetSizeBytes dan kualitas masih bisa diturunkan
                            if (blob.size > options.targetSizeBytes && currentQuality > 0.6) {
                                canvas.toBlob(function (blobLower) {
                                    var finalBlob = blobLower || blob;
                                    var compressedFile = new File([finalBlob], file.name, {
                                        type: 'image/jpeg',
                                        lastModified: Date.now()
                                    });
                                    resolve(compressedFile);
                                }, 'image/jpeg', 0.68);
                            } else {
                                var compressedFile = new File([blob], file.name, {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });
                                resolve(compressedFile);
                            }
                        }, 'image/jpeg', currentQuality);
                    };
                    img.onerror = function () {
                        resolve(file); // Fallback ke file asli jika parse gambar gagal
                    };
                    img.src = e.target.result;
                };
                reader.onerror = function () {
                    resolve(file);
                };
                reader.readAsDataURL(file);
            });
        },

        /**
         * Format ukuran bytes ke teks yang mudah dibaca (KB / MB)
         */
        formatBytes: function (bytes) {
            if (bytes === 0) return '0 B';
            var k = 1024;
            var sizes = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        /**
         * Pasang penanganan otomatis pada form formulir unggahan
         */
        attachToForm: function (fileInputId, previewContainerId) {
            var fileInput = document.getElementById(fileInputId);
            var container = document.getElementById(previewContainerId);

            if (!fileInput) return;

            fileInput.addEventListener('change', async function () {
                var files = Array.from(fileInput.files);
                if (!files.length) {
                    if (container) container.innerHTML = '';
                    return;
                }

                var dt = new DataTransfer();

                if (container) {
                    container.innerHTML = '<div class="text-xs text-slate-500 flex items-center gap-2"><svg class="animate-spin h-3.5 w-3.5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Memeriksa & mengoptimasi berkas...</div>';
                }

                var summaryHtml = '<div class="space-y-1.5 pt-1">';

                for (var i = 0; i < files.length; i++) {
                    var f = files[i];
                    var check = await SecureClientUpload.validateFile(f);
                    if (!check.valid) {
                        fileInput.value = '';
                        if (container) {
                            container.innerHTML = '<div class="text-xs text-rose-700 font-bold p-2 bg-rose-50 border border-rose-300 rounded-lg">⚠️ ' + check.error + '</div>';
                        }
                        return;
                    }

                    var originalSize = f.size;
                    var processedFile = f;

                    if (f.type.match(/^image\/(jpeg|png|jpg)$/i)) {
                        processedFile = await SecureClientUpload.compressImage(f);
                    }

                    // Validasi pasca-kompresi: berkas tidak boleh melebihi 1 MB
                    if (processedFile.size > 1024 * 1024) {
                        fileInput.value = '';
                        if (container) {
                            container.innerHTML = '<div class="text-xs text-rose-700 font-bold p-2 bg-rose-50 border border-rose-300 rounded-lg">⚠️ Ukuran berkas "' + f.name + '" setelah optimasi (' + (processedFile.size / 1024 / 1024).toFixed(2) + ' MB) melebihi batas 1 MB per berkas.</div>';
                        }
                        return;
                    }

                    dt.items.add(processedFile);

                    var sizeBefore = SecureClientUpload.formatBytes(originalSize);
                    var sizeAfter = SecureClientUpload.formatBytes(processedFile.size);
                    var savings = originalSize > processedFile.size ? ' <span class="text-emerald-700 font-bold bg-emerald-100 px-1.5 py-0.5 rounded text-[10px]">Hemat ' + Math.round((1 - processedFile.size / originalSize) * 100) + '%</span>' : '';

                    summaryHtml += '<div class="flex items-center justify-between text-xs p-2 bg-slate-50 border border-slate-200 rounded-lg">' +
                        '<div class="truncate max-w-[200px] sm:max-w-[320px] font-mono text-slate-700 font-medium">📎 ' + f.name + '</div>' +
                        '<div class="text-right text-[11px] text-slate-500 whitespace-nowrap">' +
                        sizeBefore + (originalSize !== processedFile.size ? ' → ' + sizeAfter : '') + savings +
                        '</div></div>';
                }

                // Cek total akumulasi seluruh berkas (maksimal 2 MB)
                var totalBytes = Array.from(dt.files).reduce(function (acc, curr) { return acc + curr.size; }, 0);
                if (totalBytes > 2 * 1024 * 1024) {
                    fileInput.value = '';
                    if (container) {
                        container.innerHTML = '<div class="text-xs text-rose-700 font-bold p-2 bg-rose-50 border border-rose-300 rounded-lg">⚠️ Total akumulasi seluruh berkas (' + (totalBytes / 1024 / 1024).toFixed(2) + ' MB) melebihi batas maksimal akumulasi 2 MB.</div>';
                    }
                    return;
                }

                summaryHtml += '</div>';
                fileInput.files = dt.files;

                if (container) {
                    container.innerHTML = summaryHtml;
                }
            });
        }
    };
})();
