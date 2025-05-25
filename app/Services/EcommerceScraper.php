<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Str;

class EcommerceScraper
{
    protected $client;
    protected $baseUrl = 'https://defiletekstil.com';
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
     * Scrape up to $maxProducts from a local MHTML file containing all product cards.
     *
     * @param string $mhtmlPath The path to the MHTML file (e.g., storage/app/listing_full.mhtml)
     * @return array Array of up to $maxProducts products with title, price, unit price, product code, product definition, and image URLs
     */
    public function scrapeProducts($mhtmlPath)
    {
        $products = [];
        $maxProducts = 300;
        $detailUrls = [];
        $cardData = [];

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
        Log::info("Saved extracted HTML to storage/logs/{$htmlFile}");

        // Parse HTML
        $crawler = new Crawler($html);

        // Find product cards
        $productCards = $crawler->filter('.grid-cols-2 div[data-id]');
        Log::info("Found {$productCards->count()} elements with data-id in MHTML file");

        if ($productCards->count() === 0) {
            Log::warning("No elements found in MHTML file with selector '.grid-cols-2 div[data-id]'");
            $sampleHtml = substr($html, 0, 10000);
            Storage::put("debug_sample_{$htmlFile}", $sampleHtml);
            Log::debug("Saved sample HTML to storage/logs/debug_sample_{$htmlFile}");

            // Fallback to JSON-LD
            $detailUrls = $this->extractUrlsFromJsonLd($crawler);
            Log::info("Extracted detail URLs from JSON-LD: ", ['urls' => $detailUrls ?: ['None']]);
        } else {
            // Collect hrefs and card data
            $hrefs = [];

            $productCards->each(function (Crawler $card, $i) use (&$hrefs, &$cardData, &$detailUrls, $maxProducts) {
                if (count($detailUrls) >= $maxProducts) {
                    return;
                }
                try {
                    $dataId = $card->attr('data-id');
                    // Validate data-id as UUID
                    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $dataId)) {
                        Log::debug("Skipping non-UUID data-id for card #{$i}", ['data-id' => $dataId]);
                        return;
                    }

                    $link = $card->filter('a')->first();
                    $href = $link->count() > 0 ? $link->attr('href') : null;
                    $hrefs[$i] = ['data-id' => $dataId, 'href' => $href];

                    if ($href && $href !== '#') {
                        // Convert relative to absolute URL
                        $parsedUrl = parse_url($href);
                        if (!isset($parsedUrl['scheme'])) {
                            $href = ltrim($href, '/');
                            $absoluteUrl = rtrim($this->baseUrl, '/') . '/' . $href;
                        } else {
                            $absoluteUrl = $href;
                        }

                        // Extract base path for logging
                        $basePath = parse_url($absoluteUrl, PHP_URL_PATH);

                        // Extract title
                        $titleNode = $card->filter('h2.product-name')->first();
                        $title = $titleNode->count() > 0 ? trim($titleNode->text()) : null;

                        // Extract unit price
                        $unitPriceNode = $card->filter('div.text-xs.font-normal.text-gray-600')->first();
                        $unitPrice = null;
                        if ($unitPriceNode->count() > 0) {
                            $unitPriceText = trim($unitPriceNode->text());
                            $unitPrice = preg_replace('/[^\d,.]/', '', $unitPriceText);
                            $unitPrice = str_replace(',', '.', $unitPrice);
                            $unitPrice = is_numeric($unitPrice) ? (float)$unitPrice : null;
                        }

                        $cardData[$i] = [
                            'data_id' => $dataId,
                            'url' => $absoluteUrl,
                            'basePath' => $basePath,
                            'title' => $title,
                            'unit_price' => $unitPrice,
                        ];

                        // Add to detail URLs
                        $detailUrls[] = $absoluteUrl;
                    } else {
                        Log::warning("Invalid or missing href for product card #{$i}", ['data-id' => $dataId, 'href' => $href]);
                    }
                } catch (\Exception $e) {
                    Log::error("Error extracting data from product card #{$i}: {$e->getMessage()}", ['data-id' => $dataId]);
                }
            });

            // Log hrefs
            foreach ($hrefs as $i => $hrefData) {
                Log::debug("Raw href for card #{$i}", $hrefData);
            }

            // Log card data
            foreach ($cardData as $i => $data) {
                Log::debug("Extracted card data #{$i}", $data);
            }
        }

        // Limit detail URLs to maxProducts
        $detailUrls = array_unique(array_slice($detailUrls, 0, $maxProducts));
        Log::info("Extracted detail URLs from MHTML file: ", ['urls' => $detailUrls ?: ['None'], 'count' => count($detailUrls)]);

        // Scrape each detail page
        foreach ($detailUrls as $index => $detailUrl) {
            $cardInfo = $cardData[array_search($detailUrl, array_column($cardData, 'url'))] ?? [];
            $productData = $this->scrapeDetailPage(
                $detailUrl,
                $cardInfo['title'] ?? null,
                $cardInfo['unit_price'] ?? null,
                $index + 1,
                $cardInfo['data_id'] ?? null
            );
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
            Excel::store(new ProductsExport($products), $filePath, 'local');
            Log::info("Exported products to Excel", ['file' => storage_path("app/{$filePath}")]);
            return storage_path("app/{$filePath}");
        } catch (\Exception $e) {
            Log::error("Failed to export products to Excel: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Download images to a folder named by product code and color.
     *
     * @param array $imageUrls List of image URLs
     * @param string|null $productCode Product code (e.g., DF2196)
     * @param string|null $title Product title for color extraction
     * @param int $cardIndex Card index for logging (e.g., 01)
     * @param string|null $dataId UUID for fallback folder naming
     * @return void
     */
    protected function downloadImages($imageUrls, $productCode, $title, $cardIndex, $dataId)
    {
        if (empty($imageUrls) || !$productCode) {
            Log::warning("Skipping image download: missing image URLs or product code", [
                'cardIndex' => sprintf("%02d", $cardIndex),
                'productCode' => $productCode,
                'imageCount' => count($imageUrls),
            ]);
            return;
        }

        // Extract color from title
        $color = null;
        if ($title && preg_match('/-\s*([A-ZÇĞİÖŞÜ]+)$/i', $title, $matches)) {
            $color = $matches[1];
            Log::debug("Extracted color from title", [
                'cardIndex' => sprintf("%02d", $cardIndex),
                'title' => $title,
                'color' => $color,
            ]);
        } else {
            Log::warning("Could not extract color from title, using data-id as fallback", [
                'cardIndex' => sprintf("%02d", $cardIndex),
                'title' => $title,
                'dataId' => $dataId,
            ]);
            $color = $dataId ?: 'UNKNOWN_' . sprintf("%02d", $cardIndex);
        }

        // Create folder name with Turkish character support
        $folderName = $productCode . '_' . $color;
        // Sanitize: allow A-Z, 0-9, ÇĞİÖŞÜ, and _, replace others with _
        $folderName = preg_replace('/[^A-Za-z0-9ÇĞİÖŞÜ_]/', '_', $folderName);
        // Remove multiple consecutive underscores
        $folderName = preg_replace('/_+/', '_', $folderName);
        // Trim leading/trailing underscores
        $folderName = trim($folderName, '_');

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
     * Scrape a product detail page for price, product code, product definition, and images.
     *
     * @param string $detailUrl The URL of the product detail page
     * @param string|null $listTitle Title from the listing page
     * @param float|null $unitPrice Unit price from the listing page
     * @param int $cardIndex Index of the card for logging
     * @param string|null $dataId UUID for folder naming
     * @return array|null Product data or null if failed
     */
    protected function scrapeDetailPage($detailUrl, $listTitle = null, $unitPrice = null, $cardIndex = 1, $dataId = null)
    {
        Log::info("Scraping detail page: {$detailUrl}");

        try {
            // Fetch detail page with Browsershot to render JavaScript
            $html = Browsershot::url($detailUrl)
                ->waitUntilNetworkIdle()
                ->waitForSelector('div.slick-list img', ['timeout' => 15000])
                ->timeout(60)
                ->bodyHtml();

            // Save detail page HTML for debugging
            $detailHtmlFile = 'detail_' . md5($detailUrl) . '_' . time() . '.html';
            Storage::put($detailHtmlFile, $html);
            Log::debug("Saved detail page HTML to storage/logs/{$detailHtmlFile}");

            $crawler = new Crawler($html);

            // Use listing page title if provided, else fall back to detail page
            $title = $listTitle;
            if (!$title) {
                $titleNode = $crawler->filter('h1.product-name')->first();
                $title = $titleNode->count() > 0 ? trim($titleNode->text()) : null;
            }

            // Extract price from DOM
            $priceNode = $crawler->filter('div.text-gray-600.font-normal.text-base')->first();
            $price = null;
            if ($priceNode->count() > 0) {
                $priceText = trim($priceNode->text());
                $price = preg_replace('/[^\d,.]/', '', $priceText);
                $price = str_replace(',', '.', $price);
                $price = is_numeric($price) ? (float)$price : null;
            }

            // Fallback to JSON-LD for price
            if (!$price) {
                $jsonLdNodes = $crawler->filter('script[type="application/ld+json"]');
                foreach ($jsonLdNodes as $node) {
                    $json = json_decode($node->textContent, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($json['@type']) && $json['@type'] === 'Product' && isset($json['offers']['price'])) {
                        $price = (float)$json['offers']['price'];
                        break;
                    }
                }
            }

            // Extract product code
            $productCodeNode = $crawler->filter('div.categories-detail span:nth-child(2)')->first();
            $productCode = $productCodeNode->count() > 0 ? trim($productCodeNode->text()) : null;

            // Extract product definition
            $definitionNodes = $crawler->filter('div.tab-content p');
            $productDefinition = null;
            if ($definitionNodes->count() > 0) {
                $definitionTexts = $definitionNodes->each(function (Crawler $node) {
                    return trim($node->text());
                });
                $productDefinition = trim(implode(' ', $definitionTexts));
            }

            // Extract images (no limit)
            $imageNodes = $crawler->filter('div.slick-list div.slick-slide:not(.slick-cloned) img');
            $imageUrls = $imageNodes->each(function (Crawler $img) {
                $src = $img->attr('src') ?: $img->attr('data-src');
                return filter_var($src, FILTER_VALIDATE_URL) && strpos($src, 'myikas.com') !== false ? $src : null;
            });
            $imageUrls = array_unique(array_filter($imageUrls));

            // Log image URLs and check for exceeding previous limit
            $imageCount = count($imageUrls);
            $imageLog = "Extracted image URLs for card " . sprintf("%02d", $cardIndex) . ":\n";
            foreach ($imageUrls as $i => $url) {
                $imageLog .= "image-url " . ($i + 1) . ": " . $url . "\n";
            }
            Log::debug($imageLog);
            if ($imageCount > 5) {
                Log::info("Extracted {$imageCount} image URLs for card " . sprintf("%02d", $cardIndex) . " (exceeded previous limit of 5)");
            }

            // Download images
            $this->downloadImages($imageUrls, $productCode, $title, $cardIndex, $dataId);

            // Fallback to JSON-LD for images if fewer than 2
            if (count($imageUrls) < 2) {
                Log::warning("Few images found on detail page {$detailUrl}, attempting JSON-LD fallback");
                $jsonLdImages = $this->extractImagesFromJsonLd($crawler);
                if ($jsonLdImages) {
                    $imageUrls = array_unique(array_merge($imageUrls, $jsonLdImages));
                    $imageLog = "Updated image URLs with JSON-LD for card " . sprintf("%02d", $cardIndex) . ":\n";
                    foreach ($imageUrls as $i => $url) {
                        $imageLog .= "image-url " . ($i + 1) . ": " . $url . "\n";
                    }
                    Log::debug($imageLog);
                    $this->downloadImages($jsonLdImages, $productCode, $title, $cardIndex, $dataId);
                }
            }

            // Fallback to listing page image if none found
            if (empty($imageUrls)) {
                Log::warning("No images found on detail page {$detailUrl}, attempting listing page fallback");
                $listingImage = $this->getListingImage($detailUrl);
                $imageUrls = $listingImage ? [$listingImage] : [];
                $imageLog = "Fallback image URLs for card " . sprintf("%02d", $cardIndex) . ":\n";
                foreach ($imageUrls as $i => $url) {
                    $imageLog .= "image-url " . ($i + 1) . ": " . $url . "\n";
                }
                Log::debug($imageLog);
                $this->downloadImages($imageUrls, $productCode, $title, $cardIndex, $dataId);
            }

            // Relaxed validation
            if (!$title || empty($imageUrls)) {
                Log::warning("Critical data missing for {$detailUrl}: title=" . ($title ?: 'missing') . ", images=" . count($imageUrls));
                return null;
            }
            if (!$price) {
                Log::warning("Price missing for {$detailUrl}, proceeding with null price");
            }

            $productData = [
                'title' => $title,
                'price' => $price,
                'unit_price' => $unitPrice,
                'product_code' => $productCode,
                'product_definition' => $productDefinition,
                'image_urls' => $imageUrls,
                'detail_url' => $detailUrl,
            ];

            // Log product data in requested format
            $productLog = "Extracted product " . sprintf("%02d", $cardIndex) . ":\n";
            $productLog .= "Title: " . ($title ?: 'None') . "\n";
            $productLog .= "Price: " . ($price ?? 'None') . "\n";
            $productLog .= "Unit Price: " . ($unitPrice ?? 'None') . "\n";
            $productLog .= "Product Code: " . ($productCode ?: 'None') . "\n";
            $productLog .= "Product Definition: " . ($productDefinition ?: 'None') . "\n";
            $productLog .= "Image Count: " . count($imageUrls) . "\n";
            $productLog .= "Detail URL: " . $detailUrl;
            Log::info($productLog);

            return $productData;

        } catch (\Exception $e) {
            Log::error("Failed to fetch detail page {$detailUrl}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Fetch the listing page image as a fallback.
     *
     * @param string $detailUrl The URL of the product detail page
     * @return string|null The image URL or null if not found
     */
    protected function getListingImage($detailUrl)
    {
        try {
            $html = Browsershot::url('https://defiletekstil.com/tr/toptan-bluz')
                ->waitUntilNetworkIdle()
                ->waitForSelector('.grid-cols-2 div[data-id] > a', ['timeout' => 15000])
                ->timeout(60)
                ->bodyHtml();
            $crawler = new Crawler($html);

            $path = parse_url($detailUrl, PHP_URL_PATH);
            $imageNode = $crawler->filter('.grid-cols-2 div[data-id] a[href*="' . $path . '"] img.category-products-image')->first();
            if ($imageNode->count() > 0) {
                $src = $imageNode->attr('src') ?: $imageNode->attr('data-src');
                return filter_var($src, FILTER_VALIDATE_URL) ? $src : null;
            }
            return null;
        } catch (\Exception $e) {
            Log::error("Failed to fetch listing page for fallback image: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Extract product URLs from JSON-LD as a fallback.
     *
     * @param Crawler $crawler The crawler instance with listing page HTML
     * @return array List of product URLs
     */
    protected function extractUrlsFromJsonLd(Crawler $crawler)
    {
        $urls = [];
        try {
            $jsonLdNodes = $crawler->filter('script[type="application/ld+json"]');
            Log::debug("Found {$jsonLdNodes->count()} JSON-LD scripts");
            foreach ($jsonLdNodes as $node) {
                $json = json_decode($node->textContent, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($json['@type']) && $json['@type'] === 'ItemList' && isset($json['itemListElement'])) {
                    foreach ($json['itemListElement'] as $item) {
                        if (isset($item['item']['offers']['url'])) {
                            $urls[] = $item['item']['offers']['url'];
                            Log::debug("Found JSON-LD product URL: {$item['item']['offers']['url']}");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error parsing JSON-LD: {$e->getMessage()}");
        }
        return array_unique(array_filter($urls));
    }

    /**
     * Extract image URLs from JSON-LD as a fallback.
     *
     * @param Crawler $crawler The crawler instance with detail page HTML
     * @return array List of image URLs
     */
    protected function extractImagesFromJsonLd(Crawler $crawler)
    {
        $images = [];
        try {
            $jsonLdNodes = $crawler->filter('script[type="application/ld+json"]');
            foreach ($jsonLdNodes as $node) {
                $json = json_decode($node->textContent, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($json['@type']) && $json['@type'] === 'Product') {
                    if (isset($json['image'])) {
                        $image = $json['image'];
                        if (is_array($image)) {
                            $images = array_merge($images, $image);
                        } else {
                            $images[] = $image;
                        }
                    }
                }
            }
            $images = array_filter($images, function ($url) {
                return filter_var($url, FILTER_VALIDATE_URL) && strpos($url, 'myikas.com') !== false;
            });
            Log::debug("Found JSON-LD images", ['images' => $images]);
        } catch (\Exception $e) {
            Log::error("Error parsing JSON-LD images: {$e->getMessage()}");
        }
        return array_unique($images);
    }
}