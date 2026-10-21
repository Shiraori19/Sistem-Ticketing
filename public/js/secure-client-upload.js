/**
 * secure-client-upload.js
 * Modul Kompresi Gambar Client-Side & Penanganan Unggahan Aman
 * Tanpa CDN eksternal (Pure Vanilla JS HTML5 Canvas) - Sesuai Kebijakan CSP
 */

(function () {
    'use strict';

    window.SecureClientUpload = {
        /**
         * Kompres berkas gambar tunggal jika berformat JPEG / PNG
         * @param {File} file
         * @param {Object} options { maxDimension: 1920, targetSizeBytes: 1048576, quality: 0.82 }
         * @returns {Promise<File>}
         */
        compressImage: function (file, options) {
            options = Object.assign({
                maxDimension: 1920,
                targetSizeBytes: 1024 * 1024, // 1 MB
                quality: 0.82
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
        attachToForm: function (fileInputId, previewContainerId, rawCheckboxId) {
            var fileInput = document.getElementById(fileInputId);
            var container = document.getElementById(previewContainerId);
            var rawCheckbox = rawCheckboxId ? document.getElementById(rawCheckboxId) : null;

            if (!fileInput) return;

            fileInput.addEventListener('change', async function () {
                var files = Array.from(fileInput.files);
                if (!files.length) {
                    if (container) container.innerHTML = '';
                    return;
                }

                var isRaw = rawCheckbox && rawCheckbox.checked;
                var dt = new DataTransfer();

                if (container) {
                    container.innerHTML = '<div class="text-xs text-slate-500 flex items-center gap-2"><svg class="animate-spin h-3.5 w-3.5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Memeriksa & mengoptimasi berkas...</div>';
                }

                var summaryHtml = '<div class="space-y-1.5 pt-1">';

                for (var i = 0; i < files.length; i++) {
                    var f = files[i];
                    var originalSize = f.size;
                    var processedFile = f;

                    if (!isRaw && f.type.match(/^image\/(jpeg|png|jpg)$/i)) {
                        processedFile = await SecureClientUpload.compressImage(f);
                    }

                    dt.items.add(processedFile);

                    var sizeBefore = SecureClientUpload.formatBytes(originalSize);
                    var sizeAfter = SecureClientUpload.formatBytes(processedFile.size);
                    var savings = originalSize > processedFile.size ? ' <span class="text-emerald-700 font-bold bg-emerald-100 px-1.5 py-0.5 rounded text-[10px]">Hemat ' + Math.round((1 - processedFile.size / originalSize) * 100) + '%</span>' : '';

                    summaryHtml += '<div class="flex items-center justify-between text-xs p-2 bg-slate-50 border border-slate-200 rounded-lg">' +
                        '<div class="truncate max-w-[200px] sm:max-w-[320px] font-mono text-slate-700 font-medium">📎 ' + f.name + '</div>' +
                        '<div class="text-right text-[11px] text-slate-500 whitespace-nowrap">' +
                        (isRaw ? '<span class="text-amber-700 font-semibold">(Unggah Asli) </span>' : '') +
                        sizeBefore + (originalSize !== processedFile.size ? ' → ' + sizeAfter : '') + savings +
                        '</div></div>';
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
