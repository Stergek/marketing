<?php

namespace App\Filament\Resources\GlobalAdvertiserResource\Pages;

use App\Filament\Resources\GlobalAdvertiserResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\AdLibraryScraper;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;

class EditGlobalAdvertiser extends EditRecord
{
    protected static string $resource = GlobalAdvertiserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetchAndDownload')
                ->label('Fetch and Download MHTML')
                ->color('secondary')
                ->action(function () {
                    $record = $this->getRecord();
                    $url = "https://www.facebook.com/ads/library/?active_status=active&ad_type=all&country=ALL&is_targeted_country=false&media_type=all&search_type=page&view_all_page_id={$record->page_id}";
                    $outputPath = storage_path('app/public/') . $record->name . '.mhtml';

                    if (!file_exists(storage_path('app/public'))) {
                        mkdir(storage_path('app/public'), 0755, true);
                    }

                    $scriptPath = base_path('scrape.js');
                    $command = "node {$scriptPath} " . escapeshellarg($url) . " " . escapeshellarg($outputPath) . " 2>&1";
                    Log::info("Running command: $command");

                    $output = [];
                    $returnVar = 0;
                    exec($command, $output, $returnVar);

                    if ($returnVar !== 0) {
                        Log::error("Puppeteer error: " . implode("\n", $output));
                        Notification::make()
                            ->title('Error')
                            ->body('Failed to fetch the page. Check logs for details.')
                            ->danger()
                            ->send();
                        return;
                    }

                    Log::info("Puppeteer output: " . implode("\n", $output));
                    if (file_exists($outputPath)) {
                        Notification::make()
                            ->title('Success')
                            ->body('Page fetched and saved as MHTML. Download it, then upload below.')
                            ->success()
                            ->send();

                        $this->dispatch('download-file', [
                            'path' => 'public/' . $record->name . '.mhtml',
                            'name' => $record->name . '.mhtml',
                        ]);
                    } else {
                        Notification::make()
                            ->title('Error')
                            ->body('File was not created. Check logs for details.')
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('uploadMhtml')
                ->label('Upload MHTML')
                ->color('primary')
                ->form([
                    FileUpload::make('mhtml_file')
                        ->label('Upload MHTML File')
                        ->acceptedFileTypes(['message/rfc822', 'multipart/related'])
                        ->disk('public')
                        ->directory('uploads')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    Log::info("Starting MHTML upload processing for {$record->name}, page_id: {$record->page_id}");

                    $filePath = $data['mhtml_file'];
                    $mhtmlContent = Storage::disk('public')->get($filePath);

                    $parsed = $this->parseMhtml($mhtmlContent);
                    $html = $parsed['html'];
                    $mhtmlImages = $parsed['images'];
                    Log::info("Parsed HTML length: " . strlen($html));
                    Log::info("Extracted " . count($mhtmlImages) . " image URLs from MHTML");

                    // Save HTML snippet to file
                    $snippetPath = storage_path('app/public/html_snippet_' . $record->name . '_' . time() . '.html');
                    Storage::disk('public')->put('html_snippet_' . $record->name . '_' . time() . '.html', substr($html, 0, 10000));
                    Log::info("Saved HTML snippet to: {$snippetPath}");

                    if (stripos($html, 'Library ID') !== false || stripos($html, 'Ad ID') !== false) {
                        Log::info("Library ID or Ad ID text found in HTML");
                    } else {
                        Log::warning("No Library ID or Ad ID text found in HTML");
                    }

                    if (stripos($html, 'Started running on') !== false) {
                        Log::info("Start date text found in HTML");
                    } else {
                        Log::warning("No start date text found in HTML");
                    }

                    $scraper = new AdLibraryScraper();
                    $result = $scraper->processHtml($html);

                    if (!empty($mhtmlImages) && !empty($result['ads']) && empty($result['ads'][0]['snapshot_urls'])) {
                        $result['ads'][0]['snapshot_urls'] = array_slice($mhtmlImages, 0, 3);
                        Log::info("Assigned MHTML images to Library ID {$result['ads'][0]['library_id']}: " . implode(', ', $result['ads'][0]['snapshot_urls']));
                    }

                    if (!empty($result['ads'])) {
                        $ad = $result['ads'][0];
                        $summary = "Library ID: {$ad['library_id']}, Start Date: " . ($ad['start_date'] ?? 'None') . ", Images: " . count($ad['snapshot_urls']);
                        Log::info("Extracted first ad for {$record->name}: {$summary}");
                        Notification::make()
                            ->title('Success')
                            ->body("Extracted ad: {$summary}")
                            ->success()
                            ->send();
                    } else {
                        Log::warning("Failed to extract any ads for {$record->name}");
                        Notification::make()
                            ->title('Error')
                            ->body('Failed to extract any ads. Check logs for details.')
                            ->danger()
                            ->send();
                    }

                    Storage::disk('public')->delete($filePath);
                }),
        ];
    }

    protected function parseMhtml($mhtmlContent)
    {
        $mhtmlContent = quoted_printable_decode($mhtmlContent);
        $boundary = preg_match('/boundary="(.+?)"/', $mhtmlContent, $matches) ? $matches[1] : null;
        if (!$boundary) {
            Log::error("Could not find MHTML boundary");
            return ['html' => '', 'images' => []];
        }

        $parts = explode("--" . $boundary, $mhtmlContent);
        $html = '';
        $images = [];
        foreach ($parts as $part) {
            if (strpos($part, 'Content-Type: text/html') !== false) {
                $start = strpos($part, '<html');
                $end = strrpos($part, '</html>') + 7;
                if ($start !== false && $end !== false) {
                    $html = substr($part, $start, $end - $start);
                    $html = str_replace('=3D', '=', $html);
                }
            } elseif (strpos($part, 'Content-Type: image/') !== false && preg_match('/Content-Location: (https?:\/\/[^\r\n]+)/', $part, $matches)) {
                $imageUrl = $matches[1];
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $images[] = $imageUrl;
                }
            }
        }

        if (empty($html)) {
            Log::error("Could not extract HTML from MHTML");
        }
        return ['html' => $html, 'images' => $images];
    }
}