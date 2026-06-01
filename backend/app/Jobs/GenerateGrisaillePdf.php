<?php

namespace App\Jobs;

use App\Models\GrisailleAnalysis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateGrisaillePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public GrisailleAnalysis $analysis
    ) {}

    public function handle(): void
    {
        $analysis = $this->analysis;

        $imageUrl = Storage::disk('public')->url($analysis->original_image_url);

        $data = [
            'analysis' => $analysis,
            'value_map' => $analysis->value_map,
            'value_percentages' => $analysis->value_percentages,
            'palette' => $analysis->palette_recommendation,
            'glazings' => $analysis->glazing_suggestions,
            'image_url' => $imageUrl,
            'generated_at' => now()->format('F j, Y \a\t g:i A'),
        ];

        $pdf = Pdf::loadView('pdf.grisaille-report', $data)
            ->setPaper('a4', 'portrait');

        $pdfPath = "grisaille/{$analysis->id}/report.pdf";

        Storage::disk('public')->makeDirectory("grisaille/{$analysis->id}");
        Storage::disk('public')->put($pdfPath, $pdf->output());

        $analysis->update(['pdf_url' => $pdfPath]);
    }
}
