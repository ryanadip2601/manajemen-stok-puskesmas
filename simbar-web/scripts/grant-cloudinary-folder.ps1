$ErrorActionPreference = 'Stop'

$apiBase = 'https://api.cloudinary.com/v1_1/wxpvv6ox'
$folderName = 'SIMBAR'
$roleId = 'cld::role::folder::contributor'

$appKey = ([string](Read-Host 'API Key SIMBAR (salin angka di kolom API Key pada baris SIMBAR)')).Trim()
if ($appKey -notmatch '^[0-9]+$') {
    throw 'Kolom SIMBAR perlu angka dari kolom API Key pada baris SIMBAR di Cloudinary.'
}

$adminKey = ([string](Read-Host 'API Key Master Admin (salin angka dari baris key Master Admin)')).Trim()
if ($adminKey -notmatch '^[0-9]+$') {
    throw 'Kolom Master Admin perlu angka dari kolom API Key. API Secret diminta setelah ini.'
}
if ($appKey -eq $adminKey) {
    throw 'Kedua kolom berisi API Key yang sama. Untuk kolom kedua, pilih key lain yang berperan Master Admin di wxpvv6ox.'
}

$adminSecret = Read-Host 'API Secret untuk API Key Master Admin (input disembunyikan)' -AsSecureString
if (!$adminSecret -or $adminSecret.Length -eq 0) {
    throw 'API Secret Master Admin wajib diisi.'
}

function Get-SimbarFolder {
    $result = Invoke-RestMethod @cloudRequest -Method Get -Uri "$apiBase/folders"
    $result.folders | Where-Object { $_.path -ceq $folderName }
}

$step = 'membaca folder Cloudinary'
try {
    $credential = [System.Net.NetworkCredential]::new($adminKey, $adminSecret)
    $auth = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes("${adminKey}:$($credential.Password)"))
    $cloudRequest = @{
        Headers = @{ Authorization = "Basic $auth" }
        MaximumRedirection = 0
        TimeoutSec = 30
        ErrorAction = 'Stop'
    }

    $folder = Get-SimbarFolder
    if (!$folder) {
        $step = 'membuat folder SIMBAR'
        $null = Invoke-RestMethod @cloudRequest -Method Post -Uri "$apiBase/folders/$folderName"
        $step = 'membaca ID folder SIMBAR'
        $folder = Get-SimbarFolder
    }
    if (!$folder.external_id) {
        throw 'ID folder SIMBAR tidak tersedia.'
    }

    $step = 'memberikan role Contributor pada folder SIMBAR'
    $folderId = [Uri]::EscapeDataString($folder.external_id)
    $body = @{
        principal = @{ id = $appKey; type = 'apiKey' }
        operation = 'add'
        roles = @($roleId)
    } | ConvertTo-Json -Depth 3 -Compress

    $null = Invoke-RestMethod @cloudRequest -Method Post -Uri "$apiBase/folder_operations/invite/$folderId" `
        -ContentType 'application/json' -Body $body
    Write-Output 'Cloudinary menerima pengaturan izin Contributor untuk API key SIMBAR.'
} catch {
    $response = $_.Exception.Response
    if ($response) {
        throw ('Cloudinary mengembalikan HTTP {0} saat {1}.' -f [int]$response.StatusCode, $step)
    }
    throw "Gagal saat $step. Periksa koneksi atau pengaturan akun Cloudinary wxpvv6ox."
} finally {
    $adminSecret.Dispose()
    Remove-Variable adminSecret, credential, auth, cloudRequest -ErrorAction SilentlyContinue
}
