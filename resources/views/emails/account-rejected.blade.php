<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Ditolak</title>
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
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 40px;">✕</span>
                </div>
                <h2 style="color: #dc2626; margin: 0; font-size: 24px;">Pendaftaran Ditolak</h2>
            </div>
            
            <p style="color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                Halo <strong>{{ $userName }}</strong>,
            </p>
            
            <p style="color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 25px;">
                Mohon maaf, pendaftaran akun Anda telah ditolak oleh administrator. Hal ini mungkin disebabkan oleh data yang tidak sesuai atau alasan administratif lainnya.
            </p>

            <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px 20px; border-radius: 8px; margin: 25px 0;">
                <p style="color: #991b1b; font-size: 14px; margin: 0;">
                    Jika Anda merasa ini adalah kesalahan, silakan hubungi administrator atau coba mendaftar kembali dengan data yang benar.
                </p>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('register') }}" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 12px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(99,102,241,0.3);">
                    Daftar Ulang
                </a>
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
