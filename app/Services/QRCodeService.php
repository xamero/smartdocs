<?php

namespace App\Services;

use App\Models\Document;
use App\Models\QRCode;
use App\Models\QRScanLog;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QRCodeService
{
    public function __construct(
        protected TrackingNumberService $trackingNumberService
    ) {}

    public function generateForDocument(Document $document): QRCode
    {
        $code = Str::random(32);
        $hash = hash('sha256', $document->tracking_number.$code.now()->timestamp);
        $verificationUrl = url('/documents/verify/'.$code);

        $qrCode = QRCode::create([
            'document_id' => $document->id,
            'code' => $code,
            'hash' => $hash,
            'verification_url' => $verificationUrl,
            'metadata' => [
                'tracking_number' => $document->tracking_number,
                'title' => $document->title,
                'status' => $document->status,
                'generated_at' => now()->toIso8601String(),
            ],
            'is_active' => true,
        ]);

        // Generate QR code image (requires QR code library)
        $this->generateImage($qrCode);

        return $qrCode;
    }

    protected function generateImage(QRCode $qrCode): void
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $qrCode->verification_url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10
        );

        $result = $builder->build();

        $path = 'qr-codes/'.$qrCode->id.'.png';
        Storage::disk('public')->put($path, $result->getString());

        $qrCode->update(['image_path' => $path]);
    }

    public function regenerate(QRCode $qrCode): QRCode
    {
        $oldCode = $qrCode->code;

        $qrCode->update([
            'is_active' => false,
            'is_regenerated' => true,
        ]);

        $newQrCode = $this->generateForDocument($qrCode->document);
        $newQrCode->update([
            'regenerated_from_id' => $qrCode->id,
        ]);

        return $newQrCode;
    }

    public function verify(string $code): ?QRCode
    {
        $qrCode = QRCode::where('code', $code)
            ->where('is_active', true)
            ->first();

        if ($qrCode) {
            $qrCode->increment('scan_count');
            $qrCode->update(['last_scanned_at' => now()]);

            QRScanLog::create([
                'qr_code_id' => $qrCode->id,
                'document_id' => $qrCode->document_id,
                'scanned_by_type' => auth()->check() ? 'user' : 'public',
                'scanned_by_user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'scan_location' => null,
                'scan_type' => 'physical',
                'merged_document_id' => null,
                'scanned_at' => now(),
            ]);
        }

        return $qrCode;
    }

    public function getVerificationUrl(QRCode $qrCode): string
    {
        return $qrCode->verification_url;
    }
}
