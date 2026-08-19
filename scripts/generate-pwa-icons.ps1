param(
    [string]$Source = (Join-Path $PSScriptRoot '..\public\images\brand\prokejem-symbol-1024-transparent.png'),
    [string]$Destination = (Join-Path $PSScriptRoot '..\public\pwa')
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$sourcePath = (Resolve-Path -LiteralPath $Source).Path
New-Item -ItemType Directory -Path $Destination -Force | Out-Null
$destinationPath = (Resolve-Path -LiteralPath $Destination).Path
$sourceImage = [System.Drawing.Image]::FromFile($sourcePath)

function Export-ProkejemIcon {
    param(
        [int]$Size,
        [string]$FileName,
        [bool]$OpaqueBackground = $false,
        [double]$Scale = 1.0
    )

    $bitmap = New-Object System.Drawing.Bitmap $Size, $Size, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)

    try {
        $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceOver
        $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
        $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
        $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
        $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
        $graphics.Clear($(if ($OpaqueBackground) { [System.Drawing.Color]::White } else { [System.Drawing.Color]::Transparent }))

        $drawSize = [int][Math]::Round($Size * $Scale)
        $offset = [int][Math]::Round(($Size - $drawSize) / 2)
        $destinationRectangle = New-Object System.Drawing.Rectangle $offset, $offset, $drawSize, $drawSize
        $graphics.DrawImage($sourceImage, $destinationRectangle)

        $outputPath = Join-Path $destinationPath $FileName
        $bitmap.Save($outputPath, [System.Drawing.Imaging.ImageFormat]::Png)
    }
    finally {
        $graphics.Dispose()
        $bitmap.Dispose()
    }
}

try {
    Export-ProkejemIcon -Size 192 -FileName 'icon-192.png'
    Export-ProkejemIcon -Size 512 -FileName 'icon-512.png'
    Export-ProkejemIcon -Size 192 -FileName 'icon-maskable-192.png' -OpaqueBackground $true -Scale .82
    Export-ProkejemIcon -Size 512 -FileName 'icon-maskable-512.png' -OpaqueBackground $true -Scale .82
    Export-ProkejemIcon -Size 180 -FileName 'apple-touch-icon.png' -OpaqueBackground $true -Scale .92
}
finally {
    $sourceImage.Dispose()
}

Write-Output "PWA icons generated in $destinationPath"
