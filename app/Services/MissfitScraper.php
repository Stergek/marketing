<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MissfitExport;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Str;

class MissfitScraper
{
    protected $client;
    protected $baseUrl = 'https://missfit.com.tr';
    protected $mimeToExtension = [
        'image/webp' => '.webp',
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/gif' => '.gif',
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ],
        ]);
    }

    /**
     * Scrape up to $maxProducts from a local MHTML file containing product cards.
     *
     * @param string $mhtmlPath The path to the MHTML file (e.g., storage/app/misfit_listing.mhtml)
     * @return array Array of up to $maxProducts products with title, price, product code, image URLs, and detail URL
     */
    public function scrapeProducts($mhtmlPath)
    {
        $products = [];
        $maxProducts = 300;
        $detailUrls = [];

        // Resolve MHTML path relative to storage/app
        $mhtmlPath = str_replace('storage/app/', '', $mhtmlPath);
        $fullPath = storage_path('app/' . $mhtmlPath);

        Log::info("Loading MHTML file: {$fullPath}");

        // Check if MHTML file exists
        if (!Storage::exists($mhtmlPath)) {
            Log::error("MHTML file not found: {$fullPath}");
            return ['products' => $products];
        }

        // Read MHTML file
        $mhtmlContent = Storage::get($mhtmlPath);
        if (empty($mhtmlContent)) {
            Log::error("MHTML file is empty: {$fullPath}");
            return ['products' => $products];
        }

        // Extract HTML from MHTML
        $html = $this->extractHtmlFromMhtml($mhtmlContent);
        if (empty($html)) {
            Log::error("Failed to extract HTML from MHTML file: {$fullPath}");
            return ['products' => $products];
        }

        Log::info("Extracted HTML from MHTML file");

        // Save extracted HTML for debugging
        $htmlFile = 'listing_' . time() . '_mhtml.html';
        Storage::put($htmlFile, $html);
        Log::info("Saved extracted HTML to storage/app/{$htmlFile}");

        // Parse HTML
        $crawler = new Crawler($html);

        // Find product cards
        $productCards = $crawler->filter('.productListBottom a');
        Log::info("Found {$productCards->count()} product links in MHTML file");

        if ($productCards->count() === 0) {
            Log::warning("No product links found in MHTML file with selector '.productListBottom a'");
            $sampleHtml = substr($html, 0, 10000);
            Storage::put("debug_sample_{$htmlFile}", $sampleHtml);
            Log::debug("Saved sample HTML to storage/app/debug_sample_{$htmlFile}");
        } else {
            // Collect detail URLs
            $productCards->each(function (Crawler $link, $i) use (&$detailUrls, $maxProducts) {
                if (count($detailUrls) >= $maxProducts) {
                    return;
                }
                try {
                    $href = $link->attr('href');
                    if ($href && $href !== '#') {
                        // Convert relative to absolute URL
                        $parsedUrl = parse_url($href);
                        if (!isset($parsedUrl['scheme'])) {
                            $href = ltrim($href, '/');
                            $absoluteUrl = rtrim($this->baseUrl, '/') . '/' . $href;
                        } else {
                            $absoluteUrl = $href;
                        }
                        $detailUrls[] = $absoluteUrl;
                        Log::debug("Extracted detail URL #{$i}", ['url' => $absoluteUrl]);
                    } else {
                        Log::warning("Invalid or missing href for product link #{$i}", ['href' => $href]);
                    }
                } catch (\Exception $e) {
                    Log::error("Error extracting href from product link #{$i}: {$e->getMessage()}");
                }
            });
        }

        // Limit detail URLs to maxProducts
        $detailUrls = array_unique(array_slice($detailUrls, 0, $maxProducts));
        Log::info("Extracted detail URLs from MHTML file: ", ['urls' => $detailUrls ?: ['None'], 'count' => count($detailUrls)]);

        // Scrape each detail page
        foreach ($detailUrls as $index => $detailUrl) {
            $productData = $this->scrapeDetailPage($detailUrl, $index + 1);
            if ($productData) {
                $products[] = $productData;
            }
            sleep(1);
        }

        Log::info("Scraped " . count($products) . " products");
        return ['products' => $products];
    }

    /**
     * Extract HTML content from an MHTML file.
     *
     * @param string $mhtmlContent The raw MHTML content
     * @return string|null The extracted HTML or null if failed
     */
    protected function extractHtmlFromMhtml($mhtmlContent)
    {
        // Split MHTML into parts based on boundary
        preg_match('/Content-Type: multipart\/related;.*?boundary="([^"]+)"/is', $mhtmlContent, $boundaryMatch);
        if (!$boundaryMatch) {
            Log::error("No MIME boundary found in MHTML content");
            return null;
        }

        $boundary = $boundaryMatch[1];
        $parts = preg_split("/--{$boundary}(?:--)?/s", $mhtmlContent, -1, PREG_SPLIT_NO_EMPTY);

        // Find the HTML part
        foreach ($parts as $part) {
            if (preg_match('/Content-Type: text\/html/si', $part)) {
                // Extract content after headers
                $htmlStart = strpos($part, "\r\n\r\n");
                if ($htmlStart !== false) {
                    $html = substr($part, $htmlStart + 4);
                    // Decode if quoted-printable
                    if (preg_match('/Content-Transfer-Encoding: quoted-printable/si', $part)) {
                        $html = quoted_printable_decode($html);
                    }
                    // Ensure UTF-8
                    $html = mb_convert_encoding($html, 'UTF-8', 'auto');
                    return trim($html);
                }
            }
        }

        Log::error("No HTML content found in MHTML parts");
        return null;
    }

    /**
     * Export scraped products to an Excel file.
     *
     * @param array $products Array of products to export
     * @return string Path to the generated Excel file
     */
    public function exportToExcel(array $products): string
    {
        $timestamp = time();
        $fileName = "products_export_{$timestamp}.xlsx";
        $filePath = "exports/{$fileName}";

        try {
            Excel::store(new MissfitExport($products), $filePath, 'local');
            Log::info("Exported products to Excel", ['file' => storage_path("app/{$filePath}")]);
            return storage_path("app/{$filePath}");
        } catch (\Exception $e) {
            Log::error("Failed to export products to Excel: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Transliterate Turkish characters to ASCII equivalents.
     *
     * @param string $text Input text with potential Turkish characters
     * @return string Transliterated text
     */
    protected function transliterateTurkish($text)
    {
        $turkishMap = [
            'Ç' => 'C', 'ç' => 'c',
            'Ğ' => 'G', 'ğ' => 'g',
            'İ' => 'I', 'ı' => 'i',
            'Ö' => 'O', 'ö' => 'o',
            'Ş' => 'S', 'ş' => 's',
            'Ü' => 'U', 'ü' => 'u',
        ];
        return str_replace(
            array_keys($turkishMap),
            array_values($turkishMap),
            $text
        );
    }

    /**
     * Download images to a folder named by product code and title.
     *
     * @param array $imageUrls List of image URLs
     * @param string|null $productCode Product code (e.g., 1903)
     * @param string|null $title Product title (e.g., Korsajlı Beyaz Body)
     * @param int $cardIndex Card index for logging (e.g., 01)
     * @return void
     */
    protected function downloadImages($imageUrls, $productCode, $title, $cardIndex)
    {
        if (empty($imageUrls) || !$productCode || !$title) {
            Log::warning("Skipping image download: missing image URLs, product code, or title", [
                'cardIndex' => sprintf("%02d", $cardIndex),
                'productCode' => $productCode,
                'title' => $title,
                'imageCount' => count($imageUrls),
            ]);
            return;
        }

        // Create folder name
        $folderName = $productCode . '_' . $title;
        // Ensure UTF-8 encoding
        $folderName = mb_convert_encoding($folderName, 'UTF-8', 'auto');
        // Transliterate Turkish characters
        $folderName = $this->transliterateTurkish($folderName);
        // Sanitize: allow A-Z, a-z, 0-9, and _, replace others with _
        $folderName = preg_replace('/[^A-Za-z0-9_]/', '_', $folderName);
        // Remove multiple consecutive underscores
        $folderName = preg_replace('/_+/', '_', $folderName);
        // Trim leading/trailing underscores
        $folderName = trim($folderName, '_');

        // Log sanitized folder name
        Log::debug("Sanitized folder name for product", [
            'cardIndex' => sprintf("%02d", $cardIndex),
            'original' => $productCode . '_' . $title,
            'sanitized' => $folderName,
        ]);

        // Check if folder exists
        if (Storage::exists("images/{$folderName}")) {
            Log::info("Skipped image download for product {$folderName}: folder images/{$folderName} already exists", [
                'cardIndex' => sprintf("%02d", $cardIndex),
            ]);
            return;
        }

        // Create folder
        try {
            Storage::makeDirectory("images/{$folderName}");
            Log::info("Created folder images/{$folderName}", [
                'cardIndex' => sprintf("%02d", $cardIndex),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create folder images/{$folderName}: {$e->getMessage()}", [
                'cardIndex' => sprintf("%02d", $cardIndex),
            ]);
            return;
        }

        // Download images
        foreach ($imageUrls as $index => $url) {
            $imageNumber = $index + 1;
            try {
                $response = $this->client->get($url, ['timeout' => 5]);
                $contentType = $response->getHeaderLine('Content-Type');
                $extension = $this->mimeToExtension[$contentType] ?? '.webp';
                $filePath = "images/{$folderName}/image_{$imageNumber}{$extension}";

                Storage::put($filePath, $response->getBody());
                Log::info("Downloaded image {$imageNumber} ({$contentType}) for product {$folderName} to {$filePath}", [
                    'cardIndex' => sprintf("%02d", $cardIndex),
                    'url' => $url,
                ]);
            } catch (RequestException $e) {
                Log::error("Failed to download image {$imageNumber} for product {$folderName}: {$e->getMessage()}", [
                    'cardIndex' => sprintf("%02d", $cardIndex),
                    'url' => $url,
                ]);
            }
        }
    }

    /**
     * Scrape a product detail page for title, price, product code, and images.
     *
     * @param string $detailUrl The URL of the product detail page
     * @param int $cardIndex Index of the card for logging
     * @return array|null Product data or null if failed
     */
    protected function scrapeDetailPage($detailUrl, $cardIndex = 1)
    {
        Log::info("Scraping detail page: {$detailUrl}");

        try {
            // Fetch detail page with Browsershot
            $html = Browsershot::url($detailUrl)
                ->waitUntilNetworkIdle()
                ->waitForSelector('ul.thumbelina li img', ['timeout' => 15000])
                ->timeout(60)
                ->bodyHtml();

            // Save detail page HTML for debugging
            $detailHtmlFile = 'detail_' . md5($detailUrl) . '_' . time() . '.html';
            Storage::put($detailHtmlFile, $html);
            Log::debug("Saved detail page HTML to storage/app/{$detailHtmlFile}");

            $crawler = new Crawler($html);

            // Extract title
            $titleNode = $crawler->filter('div.productName h1')->first();
            $title = $titleNode->count() > 0 ? trim($titleNode->text()) : null;
            // Ensure title is UTF-8
            if ($title) {
                $title = mb_convert_encoding($title, 'UTF-8', 'auto');
            }

            // Extract price
            $priceNode = $crawler->filter('span#ContentPlaceHolder1_lblPrice.rpt-price')->first();
            $price = null;
            if ($priceNode->count() > 0) {
                $priceText = trim($priceNode->text());
                $price = preg_replace('/[^\d,.]/', '', $priceText);
                $price = str_replace(',', '.', $price);
                $price = is_numeric($price) ? (float)$price : null;
            }

            // Extract product code
            $productCodeNode = $crawler->filter('span#ContentPlaceHolder1_lblProductCode')->first();
            $productCode = $productCodeNode->count() > 0 ? trim($productCodeNode->text()) : null;

            // Extract image URLs
            $imageNodes = $crawler->filter('ul.thumbelina li img');
            $imageUrls = $imageNodes->each(function (Crawler $img) {
                $src = $img->attr('src');
                // Convert relative to absolute URL
                if ($src && !filter_var($src, FILTER_VALIDATE_URL)) {
                    $src = ltrim($src, '/');
                    $src = rtrim($this->baseUrl, '/') . '/' . $src;
                }
                return filter_var($src, FILTER_VALIDATE_URL) ? $src : null;
            });
            $imageUrls = array_unique(array_filter($imageUrls));

            // Log image URLs
            $imageCount = count($imageUrls);
            $imageLog = "Extracted image URLs for card " . sprintf("%02d", $cardIndex) . ":\n";
            foreach ($imageUrls as $i => $url) {
                $imageLog .= "image-url " . ($i + 1) . ": " . $url . "\n";
            }
            Log::debug($imageLog);
            if ($imageCount > 5) {
                Log::info("Extracted {$imageCount} image URLs for card " . sprintf("%02d", $cardIndex) . " (exceeded typical limit of 5)");
            }

            // Download images
            $this->downloadImages($imageUrls, $productCode, $title, $cardIndex);

            // Validation
            if (!$title || !$productCode || empty($imageUrls)) {
                Log::warning("Critical data missing for {$detailUrl}: title=" . ($title ?: 'missing') . ", product_code=" . ($productCode ?: 'missing') . ", images=" . count($imageUrls));
                return null;
            }
            if (!$price) {
                Log::warning("Price missing for {$detailUrl}, proceeding with null price");
            }

            $productData = [
                'title' => $title,
                'price' => $price,
                'product_code' => $productCode,
                'image_urls' => $imageUrls,
                'detail_url' => $detailUrl,
            ];

            // Log product data
            $productLog = "Extracted product " . sprintf("%02d", $cardIndex) . ":\n";
            $productLog .= "Title: " . ($title ?: 'None') . "\n";
            $productLog .= "Price: " . ($price ?? 'None') . "\n";
            $productLog .= "Product Code: " . ($productCode ?: 'None') . "\n";
            $productLog .= "Image Count: " . count($imageUrls) . "\n";
            $productLog .= "Detail URL: " . $detailUrl;
            Log::info($productLog);

            return $productData;

        } catch (\Exception $e) {
            Log::error("Failed to fetch detail page {$detailUrl}: {$e->getMessage()}");
            return null;
        }
    }
}