<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Disetujui</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7fa; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #10b981 0%, #0d9488 100%); padding: 40px 30px; text-align: center;">
            <div style="width: 70px; height: 70px; background-color: rgba(255,255,255,0.2); border-radius: 20px; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 32px;">🏥</span>
            </div>
            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700;">UPTD Puskesmas Karang Rejo</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 14px;">Sistem Manajemen Stok Barang</p>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 40px;">✓</span>
                </div>
                <h2 style="color: #16a34a; margin: 0; font-size: 24px;">Akun Anda Telah Disetujui!</h2>
            </div>
            
            <p style="color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                Halo <strong>{{ $user->name }}</strong>,
            </p>
            
            <p style="color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 25px;">
                Selamat! Akun Anda telah disetujui oleh administrator. Anda sekarang dapat login ke sistem menggunakan email dan password yang telah Anda daftarkan.
            </p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('login') }}" style="display: inline-block; background: linear-gradient(135deg, #10b981 0%, #0d9488 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 12px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(16,185,129,0.3);">
                    Login Sekarang
                </a>
            </div>

            <div style="background-color: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px 20px; border-radius: 8px; margin: 25px 0;">
                <p style="color: #166534; font-size: 14px; margin: 0;">
                    <strong>Tips:</strong> Simpan email dan password Anda dengan aman. Jangan bagikan kredensial login Anda kepada siapapun.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 25px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="color: #64748b; font-size: 13px; margin: 0;">
                &copy; {{ date('Y') }} UPTD Puskesmas Karang Rejo. All rights reserved.
            </p>
            <p style="color: #94a3b8; font-size: 12px; margin: 10px 0 0;">
                Email ini dikirim secara otomatis. Mohon tidak membalas email ini.
            </p>
        </div>
    </div>
</body>
</html>
