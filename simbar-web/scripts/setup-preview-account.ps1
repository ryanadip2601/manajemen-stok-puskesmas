param(
    [Parameter(Mandatory = $true)]
    [string]$Email
)

$ErrorActionPreference = 'Stop'
$projectRef = 'vlcftyrexirkrswodmsk'
$authUrl = "https://$projectRef.supabase.co/auth/v1"
$emailAddress = $Email.Trim().ToLowerInvariant()
if ($emailAddress -notmatch '^[a-z0-9_.+-]+@[a-z0-9.-]+\.[a-z]{2,}$') {
    throw 'Masukkan alamat email profil SIMBAR yang valid.'
}

$tempSql = Join-Path ([IO.Path]::GetTempPath()) ('simbar-preview-' + [guid]::NewGuid().ToString('N') + '.sql')
$oldCliHome = $env:SUPABASE_HOME
$oldNoKeyring = $env:SUPABASE_NO_KEYRING
$oldAccessToken = $env:SUPABASE_ACCESS_TOKEN
$password = $null
$confirmation = $null

function Invoke-SimbarSql([string]$Sql, [switch]$Write) {
    Set-Content -LiteralPath $tempSql -Value $Sql -Encoding Ascii
    $mode = '--read-only'
    if ($Write) { $mode = '--yes' }
    $output = & npx.cmd --yes supabase db query "--remote=$projectRef" --agent no --output-format json --file $tempSql $mode 2>$null
    if ($LASTEXITCODE -ne 0) { throw 'Permintaan database gagal. Periksa login Supabase CLI khusus SIMBAR.' }
    $output | ConvertFrom-Json
}

try {
    $env:SUPABASE_HOME = Join-Path $env:LOCALAPPDATA 'SIMBAR-CLI\supabase'
    $env:SUPABASE_NO_KEYRING = '1'
    Remove-Item Env:SUPABASE_ACCESS_TOKEN -ErrorAction SilentlyContinue
    $profile = @(Invoke-SimbarSql @"
SELECT u.id, u.is_approved, u.role, u.auth_user_id,
       (SELECT a.id FROM auth.users a WHERE lower(a.email) = '$emailAddress' AND a.deleted_at IS NULL) AS existing_auth_id
FROM simbar.users u WHERE lower(u.email) = '$emailAddress';
"@)
    if ($profile.Count -ne 1 -or !$profile[0].is_approved -or $profile[0].role -notin 'admin', 'admin_gudang', 'staff') {
        throw 'Email harus cocok dengan satu profil SIMBAR yang sudah disetujui.'
    }
    $profile = $profile[0]
    if ($profile.auth_user_id) {
        if ($profile.auth_user_id -ne $profile.existing_auth_id) { throw 'Tautan akun perlu diperiksa oleh administrator.' }
        Write-Output 'Akun Preview sudah ditautkan. Masuk dengan kata sandi Supabase Auth Anda.'
        return
    }

    if ($profile.existing_auth_id) {
        $password = Read-Host 'Kata sandi akun Supabase SIMBAR yang sudah ada' -AsSecureString
    } else {
        $password = Read-Host 'Buat kata sandi Preview SIMBAR (minimal 12 karakter)' -AsSecureString
        $confirmation = Read-Host 'Ulangi kata sandi Preview SIMBAR' -AsSecureString
    }
    $plain = [System.Net.NetworkCredential]::new('', $password).Password
    if (!$profile.existing_auth_id) {
        if ($plain.Length -lt 12) { throw 'Kata sandi harus minimal 12 karakter.' }
        if ($plain -cne [System.Net.NetworkCredential]::new('', $confirmation).Password) {
            throw 'Ulangan kata sandi berbeda. Jalankan kembali skrip.'
        }
    }

    $keyArgs = @('--agent', 'no', '--output-format', 'json')
    if (!$profile.existing_auth_id) { $keyArgs += '--reveal' }
    $raw = & npx.cmd --yes supabase projects api-keys "--remote=$projectRef" @keyArgs 2>$null
    if ($LASTEXITCODE -ne 0) { throw 'Tidak dapat membaca pengaturan akses Supabase SIMBAR.' }
    $keys = ($raw | ConvertFrom-Json).keys
    try {
        if ($profile.existing_auth_id) {
            $publicKey = $keys | ForEach-Object api_key | Where-Object { $_ -match '^sb_publishable_[A-Za-z0-9_-]+$' } | Select-Object -First 1
            if (!$publicKey) { throw 'Publishable key SIMBAR tidak ditemukan.' }
            $body = @{ email = $emailAddress; password = $plain } | ConvertTo-Json -Compress
            $response = Invoke-RestMethod -Uri "$authUrl/token?grant_type=password" -Method Post -Headers @{ apikey = $publicKey } -Body $body -ContentType 'application/json' -MaximumRedirection 0 -TimeoutSec 20
            $authId = [guid]::Parse($response.user.id).ToString()
            if ($authId -ne $profile.existing_auth_id) { throw 'ID Supabase Auth berbeda.' }
        } else {
            $adminKey = $keys | Where-Object { $_.name -eq 'service_role' } | ForEach-Object api_key | Where-Object { $_ -match '^eyJ[A-Za-z0-9_.-]+$' } | Select-Object -First 1
            if (!$adminKey) { throw 'Kunci administrasi Supabase SIMBAR tidak ditemukan.' }
            $body = @{ email = $emailAddress; password = $plain; email_confirm = $true } | ConvertTo-Json -Compress
            $headers = @{ apikey = $adminKey; Authorization = "Bearer $adminKey" }
            $response = Invoke-RestMethod -Uri "$authUrl/admin/users" -Method Post -Headers $headers -Body $body -ContentType 'application/json' -MaximumRedirection 0 -TimeoutSec 20
            if ($response.email -cne $emailAddress) { throw 'Email akun Supabase berbeda.' }
            $authId = [guid]::Parse($response.id).ToString()
        }
    } catch {
        if ($_.Exception.Response) { throw ('Supabase Auth mengembalikan HTTP {0} saat menyiapkan akun Preview.' -f [int]$_.Exception.Response.StatusCode) }
        throw 'Supabase Auth gagal menyiapkan akun Preview. Periksa koneksi dan login CLI.'
    } finally {
        Remove-Variable plain, body, raw, keys, adminKey, headers, response -ErrorAction SilentlyContinue
    }

    $profileId = [long]$profile.id
    $linked = @(Invoke-SimbarSql -Write -Sql @"
UPDATE simbar.users u SET auth_user_id = '$authId'
WHERE u.id = $profileId AND lower(u.email) = '$emailAddress'
  AND u.is_approved AND u.role IN ('admin', 'admin_gudang', 'staff')
  AND (u.auth_user_id IS NULL OR u.auth_user_id = '$authId')
  AND EXISTS (SELECT 1 FROM auth.users a WHERE a.id = '$authId'
              AND lower(a.email) = '$emailAddress' AND a.deleted_at IS NULL
              AND a.email_confirmed_at IS NOT NULL)
RETURNING u.id;
"@)
    if ($linked.Count -ne 1) {
        throw 'Akun Supabase sudah ada, tetapi profil belum tertaut. Periksa profil lalu jalankan ulang dengan kata sandi yang sama.'
    }
    Write-Output 'Akun Preview SIMBAR siap. Masuk dengan email dan kata sandi yang baru Anda masukkan.'
} finally {
    if ($password) { $password.Dispose() }
    if ($confirmation) { $confirmation.Dispose() }
    Remove-Variable plain -ErrorAction SilentlyContinue
    if (Test-Path -LiteralPath $tempSql) { Remove-Item -LiteralPath $tempSql }
    $env:SUPABASE_HOME = $oldCliHome
    $env:SUPABASE_NO_KEYRING = $oldNoKeyring
    $env:SUPABASE_ACCESS_TOKEN = $oldAccessToken
}
