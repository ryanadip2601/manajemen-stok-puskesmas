# Panduan Menjalankan Aplikasi via Jaringan WiFi (LAN)

Panduan ini menjelaskan cara menjalankan aplikasi agar dapat diakses dari perangkat lain (HP, laptop, tablet) yang terhubung ke jaringan WiFi yang sama.

## Langkah 1: Cek IP Address Komputer

Buka **Command Prompt** atau **PowerShell**, lalu ketik:

```
ipconfig
```

Cari bagian **Wireless LAN adapter Wi-Fi** dan catat **IPv4 Address**, contoh:
```
IPv4 Address. . . . . . . . . . . : 192.168.1.100
```

## Langkah 2: Jalankan Server Laravel

Buka terminal di folder project, lalu jalankan:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Output akan menampilkan:
```
INFO  Server running on [http://0.0.0.0:8000].
```

## Langkah 3: Akses dari Perangkat Lain

Dari HP atau laptop lain yang terhubung ke WiFi yang sama, buka browser dan akses:

```
http://192.168.1.100:8000
```

> Ganti `192.168.1.100` dengan IP Address komputer Anda dari Langkah 1.

## Langkah 4: Konfigurasi Firewall (Jika Tidak Bisa Diakses)

Jika perangkat lain tidak bisa mengakses, kemungkinan firewall memblokir koneksi.

### Windows Firewall:

1. Buka **Windows Defender Firewall**
2. Klik **Advanced Settings**
3. Pilih **Inbound Rules** > **New Rule**
4. Pilih **Port** > Next
5. Pilih **TCP** dan masukkan port **8000**
6. Pilih **Allow the connection** > Next
7. Centang semua (Domain, Private, Public) > Next
8. Beri nama: `Laravel Server` > Finish

### Atau via PowerShell (Run as Administrator):

```powershell
New-NetFirewallRule -DisplayName "Laravel Server" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow
```

## Langkah 5: Update APP_URL di .env (Opsional)

Untuk menghindari masalah asset/URL, update file `.env`:

```env
APP_URL=http://192.168.1.100:8000
```

Lalu clear cache:
```bash
php artisan config:clear
php artisan cache:clear
```

## Troubleshooting

### Masalah: Tidak bisa diakses dari HP
- Pastikan HP dan komputer terhubung ke WiFi yang **sama**
- Pastikan firewall sudah dikonfigurasi
- Coba matikan sementara Windows Firewall untuk testing

### Masalah: Halaman tidak loading sempurna
- Update `APP_URL` di file `.env` sesuai IP Address
- Jalankan `php artisan config:clear`

### Masalah: Connection refused
- Pastikan server Laravel masih berjalan
- Pastikan menggunakan `--host=0.0.0.0`
- Cek apakah port 8000 tidak digunakan aplikasi lain

### Masalah: IP Address berubah
- IP dari router bisa berubah (DHCP)
- Untuk IP tetap, atur Static IP di pengaturan WiFi komputer

## Quick Command

Jalankan semua dalam satu perintah:

```powershell
# Cek IP dan jalankan server
ipconfig | findstr IPv4; php artisan serve --host=0.0.0.0 --port=8000
```

## Catatan Penting

1. **Keamanan**: Metode ini hanya untuk development/testing di jaringan lokal
2. **Jangan gunakan di jaringan publik** (cafe, hotel, dll)
3. Untuk production, gunakan web server seperti Nginx/Apache dengan HTTPS
4. Server akan berhenti jika terminal ditutup

## Contoh Skenario

| Perangkat | IP Address | Cara Akses |
|-----------|------------|------------|
| Komputer Server | 192.168.1.100 | http://localhost:8000 |
| HP Android | - | http://192.168.1.100:8000 |
| Laptop Lain | - | http://192.168.1.100:8000 |
| Tablet | - | http://192.168.1.100:8000 |

---

**Tips**: Bookmark alamat IP di browser HP agar mudah diakses kembali.
