document.addEventListener('DOMContentLoaded', function () {

    // Toggle sidebar mobile
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('sidebar');

    if (toggleBtn && sidebar) {
        // Buat overlay untuk menutup sidebar di mobile
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    // Auto dismiss alert
    // Semua .alert akan hilang otomatis setelah 4 detik
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            alertEl.style.transition = 'opacity 0.4s ease';
            alertEl.style.opacity    = '0';
            setTimeout(function () { alertEl.remove(); }, 400);
        }, 4000);
    });

    // Pencarian tabel real-time
    /**
     * Inisialisasi pencarian real-time untuk tabel
     * Atribut pada elemen input: data-search-target="#id-tabel"
     */
    document.querySelectorAll('[data-search-target]').forEach(function (input) {
        input.addEventListener('input', function () {
            const targetId  = this.getAttribute('data-search-target');
            const table     = document.querySelector(targetId);
            if (!table) return;

            const keyword   = this.value.toLowerCase().trim();
            const rows      = table.querySelectorAll('tbody tr');
            let   visible   = 0;

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                const show = keyword === '' || text.includes(keyword);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            // Tampilkan empty state jika tidak ada hasil
            let emptyRow = table.querySelector('.js-empty-search');
            if (visible === 0) {
                if (!emptyRow) {
                    const cols = table.querySelector('thead tr')
                                      ? table.querySelector('thead tr').children.length : 5;
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'js-empty-search';
                    emptyRow.innerHTML =
                        `<td colspan="${cols}" style="text-align:center;padding:2rem;color:var(--text-muted)">
                            Tidak ada data yang cocok dengan pencarian.
                        </td>`;
                    table.querySelector('tbody').appendChild(emptyRow);
                }
            } else {
                if (emptyRow) emptyRow.remove();
            }
        });
    });

    // Filter select tabel
    /**
     * Filter tabel berdasarkan kolom tertentu menggunakan <select>
     * Atribut: data-filter-target="#id-tabel"  data-filter-col="2" (index kolom, 0-based)
     */
    document.querySelectorAll('[data-filter-target]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const targetId  = this.getAttribute('data-filter-target');
            const colIndex  = parseInt(this.getAttribute('data-filter-col') || '0');
            const table     = document.querySelector(targetId);
            if (!table) return;

            const value = this.value.toLowerCase().trim();
            table.querySelectorAll('tbody tr').forEach(function (row) {
                if (row.classList.contains('js-empty-search')) return;
                const cell = row.children[colIndex];
                if (!cell) return;
                const text = cell.textContent.toLowerCase().trim();
                row.style.display = (value === '' || text.includes(value)) ? '' : 'none';
            });
        });
    });

    // Konfirmasi sebelum hapus
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = this.getAttribute('data-confirm') || 'Apakah Anda yakin ingin menghapus data ini?';
            if (!confirm(msg)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // Pilihan buku peminjaman
    const bookPicker  = document.getElementById('bookPicker');
    const counterText = document.getElementById('pickerCounter');

    if (bookPicker && counterText) {
        function updateCounter() {
            const checked = bookPicker.querySelectorAll('input[type="checkbox"]:checked').length;
            counterText.textContent = checked === 0
                ? 'Belum ada buku dipilih'
                : `${checked} buku dipilih`;
        }

        bookPicker.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            cb.addEventListener('change', updateCounter);
        });

        updateCounter(); // inisialisasi
    }

    // Validasi form sebelum submit
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            let valid = true;

            // Hapus error sebelumnya
            form.querySelectorAll('.field-error').forEach(function (el) { el.remove(); });
            form.querySelectorAll('.form-control.error').forEach(function (el) {
                el.classList.remove('error');
            });

            // Cek semua field required
            form.querySelectorAll('[required]').forEach(function (field) {
                let val = field.value.trim();

                // Khusus checkbox group: cek minimal satu yang dicentang
                if (field.type === 'checkbox') {
                    const name     = field.name;
                    const allBoxes = form.querySelectorAll(`[name="${name}"]`);
                    const anyChecked = Array.from(allBoxes).some(function (cb) { return cb.checked; });
                    if (!anyChecked && field === allBoxes[0]) {
                        showFieldError(field.closest('.form-group') || field.parentNode,
                                       'Pilih minimal satu buku.');
                        valid = false;
                    }
                    return;
                }

                if (val === '') {
                    field.classList.add('error');
                    showFieldError(field, field.getAttribute('data-label') || 'Field ini wajib diisi.');
                    valid = false;
                }
            });

            if (!valid) e.preventDefault();
        });
    });

    function showFieldError(field, message) {
        const err = document.createElement('div');
        err.className = 'field-error';
        err.textContent = message;
        err.style.cssText = 'color:#888;font-size:0.75rem;margin-top:0.25rem;';
        field.parentNode.insertBefore(err, field.nextSibling);
    }

    // Style untuk field error
    const style = document.createElement('style');
    style.textContent = '.form-control.error { border-color: #555 !important; }';
    document.head.appendChild(style);


    // Pencarian katalog dengan filter kategori
    const katalogSearch = document.getElementById('katalogSearch');
    const katalogFilter = document.getElementById('katalogFilter');
    const katalogTable  = document.getElementById('katalogTable');

    if (katalogSearch && katalogTable) {
        function filterKatalog() {
            const keyword  = katalogSearch.value.toLowerCase().trim();
            const kategori = katalogFilter ? katalogFilter.value.toLowerCase().trim() : '';
            const rows     = katalogTable.querySelectorAll('tbody tr');
            let visible    = 0;

            rows.forEach(function (row) {
                if (row.classList.contains('js-empty-search')) return;
                const text     = row.textContent.toLowerCase();
                const katCell  = row.children[4]; // Kolom kategori (index 4)
                const katText  = katCell ? katCell.textContent.toLowerCase().trim() : '';

                const matchKw  = keyword  === '' || text.includes(keyword);
                const matchKat = kategori === '' || katText.includes(kategori);

                row.style.display = (matchKw && matchKat) ? '' : 'none';
                if (matchKw && matchKat) visible++;
            });
        }

        katalogSearch.addEventListener('input', filterKatalog);
        if (katalogFilter) katalogFilter.addEventListener('change', filterKatalog);
    }

});
