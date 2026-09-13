## Keutamaan Sesi Seterusnya (Arahan Pemilik, 12 September 2026)

Status: disimpan untuk disambung; belum mula dilaksanakan. Apabila pemilik kembali untuk menyambung kerja, bentangkan dua tugasan berikut mengikut turutan ini sebelum mencadangkan tugasan lain:

1. **Lengkapkan ringkasan Business Sites bersama Needs Review.** Tambah pecahan Gross Sales, diskaun, Cash/QR dan penapis tarikh, sesi, status serta ejen yang belum lengkap. Sediakan amaran rekonsiliasi bagi rekod tidak lengkap, jualan di luar waktu sesi dan bukti bayaran QR yang tiada. Mulakan dengan paparan baca sahaja tanpa mengubah data operasi.
2. **Pembetulan masa operasi dan kehadiran.** Sediakan pembetulan waktu buka/tutup sesi dan check-in/check-out ejen dengan sebab wajib, pratonton sebelum/selepas, permission khusus dan audit trail. Sahkan julat masa, kesan kepada jualan/kehadiran dan pengesahan tambahan bagi perubahan berisiko sebelum simpan secara transaction dengan row lock.

Konteks: ringkasan asas, edit/missing/void sale dan audit pembetulan jualan sudah tersedia. Kelulusan berasingan bagi perubahan berisiko, bukti pembayaran dalam borang pembetulan jualan dan ujian pengguna serentak masih merupakan kerja susulan; jangan anggap telah siap. Keputusan suite terakhir ialah 246 ujian lulus, 2,364 assertion, bukan pengesahan semua skop perancangan telah dilaksanakan.

Kekalkan arahan stok pusat dan keselamatan data sedia ada. Penyimpanan keutamaan ini bukan arahan untuk memulakan pelaksanaan atau melaras data sekarang.

---

31-Aug-2026
-----------
Objectives
1. For clicker, Add image product jadikan 25. Product image jadi 25. Semua cost kena individually
2. Add to cart untuk clicker add as new product
3. Admin boleh edit sale yg lepas daripada agent platform
4. Add on admin user

Business Sites: Daily Summary & Safe Data Correction
----------------------------------------------------

### Matlamat

Menyediakan ringkasan operasi harian yang mudah disemak oleh admin dan membolehkan rekod yang tertinggal atau tersilap dibetulkan tanpa memadam sejarah asal atau menjejaskan data sedia ada.

### Cadangan summary terbaik

Paparkan summary mengikut tarikh dan sesi perniagaan dengan maklumat berikut:

1. Status sesi: Open, Closed atau Needs Review.
2. Masa buka, masa tutup dan jumlah tempoh operasi.
3. Bilangan ejen hadir dan jumlah jam kehadiran.
4. Bilangan resit dan jumlah unit produk terjual.
5. Gross sales, customer discount dan net sales.
6. Net company, modal produk dan anggaran gross profit.
7. Pecahan kaedah bayaran: Cash dan QR.
8. Penanda amaran jika terdapat rekod tidak lengkap, jualan di luar masa sesi, bukti bayaran QR tiada atau jumlah yang perlu disemak.
9. Filter mengikut tarikh, business site, status sesi dan ejen.
10. Butang Details untuk drill-down kepada kehadiran, resit dan sejarah pembetulan.

Polisi POS Sale: komisen ejen tidak dikira atau ditolak daripada jualan POS. Semua Net Sales dimasukkan ke Net Company dan bayaran sales person/ejen akan diurus kemudian sebagai gaji harian. Formula POS ialah `Net Company = Net Sales` dan `Gross Profit = Net Company - Capital`. Komisen bagi Order melalui agent kekal tanpa perubahan.

**Arahan muktamad pemilik (12 September 2026): POS mesti menolak stok berpusat, termasuk produk aktif.** Lokasi jualan tidak menggunakan kolam stok berasingan. Clicker berstok casing menolak casing/saiz melalui servis stok bersama yang turut menyelaraskan baki pusat; jangan menolak baki produk kali kedua. Stok tidak cukup mesti ditolak secara atomik. Correction menggunakan beza kuantiti, dan void hanya memulangkan stok yang pernah ditolak, sekali sahaja. Rekod lama tidak dilaras secara retrospektif; tambahan kuantiti pada item lama yang tidak pernah menolak stok perlu direkodkan sebagai jualan baharu.

### Fungsi admin untuk tambah dan membetulkan rekod

1. **Tambah rekod jualan tertinggal**
   - Admin memilih business site, sesi, tarikh/masa jualan dan sales agent.
   - Produk, kuantiti, diskaun pelanggan, kaedah bayaran dan bukti bayaran dimasukkan seperti POS biasa.
   - Rekod ditanda sebagai `Admin correction` dan sebab penambahan diwajibkan.

2. **Edit rekod jualan**
   - Benarkan pembetulan sales agent, produk, kuantiti, diskaun, maklumat pelanggan, kaedah bayaran, bukti bayaran dan tarikh/masa jualan.
   - Nombor resit asal, pencipta asal dan hubungan sejarah tidak boleh diubah secara senyap.
   - Sebelum simpan, paparkan perbandingan nilai lama dan nilai baharu.

3. **Betulkan masa operasi dan kehadiran**
   - Benarkan admin membetulkan masa buka/tutup sesi dan masa check-in/check-out ejen.
   - Masa tutup mesti selepas masa buka.
   - Kehadiran dan jualan di luar julat sesi perlu diberi amaran dan memerlukan pengesahan tambahan.

4. **Batalkan rekod tanpa delete**
   - Rekod jualan yang salah tidak dipadam secara kekal.
   - Gunakan status `Voided` berserta sebab, admin dan masa pembatalan.
   - Rekod void kekal boleh dilihat dalam audit tetapi dikecualikan daripada total summary.

### Perlindungan data wajib

1. Semua pembetulan mesti berjalan dalam database transaction dan menggunakan row lock bagi mengelakkan dua admin mengubah rekod yang sama serentak.
2. Simpan audit trail yang tidak boleh diedit: admin, tarikh/masa, sebab, nilai sebelum dan nilai selepas.
3. Reason for correction wajib diisi; perubahan kewangan memerlukan confirmation kedua.
4. Jangan gunakan hard delete untuk sesi, jualan atau item yang mempunyai sejarah.
5. Migration mestilah additive sahaja; jangan ubah atau padam data lama semasa deployment.
6. Summary mesti dikira semula daripada rekod berkuat kuasa selepas pembetulan, bukan menyimpan total manual yang mudah tidak selaras.
7. Hanya admin dengan permission khusus boleh melakukan pembetulan.
8. Sediakan indikator `Corrected` pada resit dan pautan untuk melihat correction history.

### Cadangan aliran kerja

1. Admin membuka Business Sites dan memilih tarikh atau sesi.
2. Sistem memaparkan daily summary dan sebarang `Needs Review` warning.
3. Admin memilih `Add missing sale`, `Correct sale`, `Correct attendance` atau `Void sale`.
4. Admin memasukkan sebab dan menyemak paparan before/after.
5. Sistem mengesahkan data, menyimpan perubahan dan audit secara atomic.
6. Summary dikira semula serta-merta dan rekod memaparkan badge `Corrected`.

### Fasa pelaksanaan yang disyorkan

1. Fasa 1: Tambah correction history, permission, validation dan audit trail.
2. Fasa 2: Tambah daily summary, filter dan reconciliation warning secara read-only.
3. Fasa 3: Tambah fungsi edit jualan dengan reason wajib.
4. Fasa 4: Tambah missing sale dan void sale tanpa hard delete.
5. Fasa 5: Tambah pembetulan masa operasi/kehadiran serta approval untuk perubahan berisiko tinggi.
6. Fasa 6: Ujian feature bagi happy path, validation, conflict, audit trail dan ketepatan pengiraan summary.

### Acceptance criteria

1. Data sedia ada kekal dan migration tidak memadam atau menukar rekod lama.
2. Admin boleh mengenal pasti rekod harian yang tertinggal melalui summary dan warning.
3. Setiap tambah, edit atau void mempunyai sebab serta audit before/after.
4. Total jualan, unit, diskaun pelanggan, net company, modal dan profit berubah dengan betul selepas pembetulan.
5. Dua perubahan serentak tidak menyebabkan lost update.
6. Jualan yang dibatalkan tidak masuk dalam summary tetapi sejarahnya masih boleh diaudit.
7. Pengguna tanpa permission tidak boleh mengakses fungsi pembetulan.
