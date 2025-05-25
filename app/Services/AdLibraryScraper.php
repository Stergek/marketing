<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdLibraryScraper
{
    /**
     * Process HTML content for an advertiser page to extract all ad cards.
     *
     * @param string $html The HTML content of the advertiser page (from MHTML)
     * @return array Array with all ads' data (Library ID, start date, platforms, creative body, CTA, destination, image/video URLs)
     */
    public function processHtml($html)
    {
        $crawler = new Crawler($html);
        $ads = [];

        // Find all ad card containers
        $containers = $crawler->filter('div.xh8yej3');
        Log::info("Found {$containers->count()} ad card containers");

        if ($containers->count() === 0) {
            Log::warning("No ad card containers found");
            return ['ads' => []];
        }

        // Process each container
        $containers->each(function (Crawler $container) use (&$ads) {
            // Extract Library ID
            // Note: Can use div.x3nfvp2.x1e56ztr or div span.x8t9es0.xw23nyj if xt0e3qv is inconsistent
            $libraryIdNode = $container->filter('div.xt0e3qv span.x8t9es0.xw23nyj')->reduce(function (Crawler $node) {
                return stripos($node->text(), 'Library ID') !== false || stripos($node->text(), 'Ad ID') !== false;
            })->first();
            
            $libraryId = null;
            if ($libraryIdNode->count() > 0) {
                $libraryIdText = trim($libraryIdNode->text());
                if (preg_match('/(?:Library|Ad)\s*ID:?\s*(\d+)/i', $libraryIdText, $matches)) {
                    $libraryId = $matches[1];
                }
            }

            if (!$libraryId) {
                Log::warning("No valid Library ID found for container");
                return;
            }

            $adData = [
                'library_id' => $libraryId,
                'start_date' => null,
                'platforms' => [],
                'snapshot_urls' => [],
                'creative_body' => null,
                'cta' => null,
                'destination' => null,
            ];

            // Extract start date
            $startDateNode = $container->filter('div.x3nfvp2.x1e56ztr span.x8t9es0.xw23nyj')->reduce(function (Crawler $node) {
                return stripos($node->text(), 'Started running on') !== false;
            })->first();
            if ($startDateNode->count() > 0) {
                $dateText = trim($startDateNode->text());
                if (preg_match('/Started running on\s+(.+?)(?:\s+·|$)/', $dateText, $matches)) {
                    $dateStr = trim($matches[1]);
                    $adData['start_date'] = $this->parseDate($dateStr);
                }
            }

            // Extract platforms
            $platformsNode = $container->filter('div:contains("Platforms")')->first();
            if ($platformsNode->count() > 0) {
                $platformIcons = $container->filter('div.x3nfvp2.x1e56ztr div.xtwfq29, div:contains("Platforms") ~ div div');
                $platforms = [];
                $platformIcons->each(function (Crawler $icon) use (&$platforms) {
                    $style = $icon->attr('style');
                    if (preg_match('/mask-position:\s*0px\s*-(\d+)px/', $style, $matches)) {
                        $position = (int)$matches[1];
                        if ($position === 1188) {
                            $platforms[] = 'Facebook';
                        } elseif ($position === 1201) {
                            $platforms[] = 'Instagram';
                        }
                    }
                });
                $platforms = array_unique(array_filter($platforms));
                $adData['platforms'] = $platforms ?: ['Instagram'];
            } else {
                $adData['platforms'] = ['Instagram'];
            }

            // Extract creative body
            $creativeBodyNode = $container->filter('div.x6ikm8r.x10wlt62 div._4ik4._4ik5 span')->reduce(function (Crawler $node) {
                $text = trim($node->text());
                return !empty($text) && stripos($node->text(), 'Sponsored') === false;
            })->first();
            if ($creativeBodyNode->count() > 0) {
                $text = trim($creativeBodyNode->text());
                $text = preg_replace('/\n{2,}/', "\n", $text);
                $adData['creative_body'] = $text;
            }

            // Extract CTA
            $ctaNode = $container->filter('div.x6ikm8r.x10wlt62 a span:contains("Shop Now"), div.x6ikm8r.x10wlt62 a span:contains("Visit Instagram Profile")')->first();
            if ($ctaNode->count() > 0) {
                $ctaText = trim($ctaNode->text());
                $adData['cta'] = (strlen($ctaText) <= 20 || stripos($ctaText, 'Visit Instagram') !== false || stripos($ctaText, 'Shop Now') !== false) ? $ctaText : 'Unknown';
            }

            // Extract destination
            // Note: Original selector used div.x6s0dn4.x2izyaf
            $destNodes = $container->filter('div._7jyg._7jyh a.x1hl2dhg.x1lku1pv.x8t9es0.x1fvot60.xxio538.xjnfcd9.xq9mrsl.x1yc453h.x1h4wwuj.x1fcty0u.x1lliihq[rel="nofollow noreferrer"][href*="u="][target="_blank"]');
            if ($destNodes->count() > 0) {
                Log::debug("Found {$destNodes->count()} destination links for Library ID {$adData['library_id']}"); // Temporary for debugging
                $destNode = $destNodes->first();
                $href = $destNode->attr('href');

                $decodedUrl = null;
                if (preg_match('/u=([^&]+)/', $href, $matches)) {
                    $decodedUrl = urldecode($matches[1]);
                } elseif (filter_var($href, FILTER_VALIDATE_URL)) {
                    $decodedUrl = $href;
                }

                if ($decodedUrl && filter_var($decodedUrl, FILTER_VALIDATE_URL)) {
                    $adData['destination'] = $decodedUrl;
                } else {
                    // Fallback
                    $destNodes = $container->filter('a.x1hl2dhg.x1lku1pv.x8t9es0.x1fvot60.xxio538.xjnfcd9.xq9mrsl.x1yc453h.x1h4wwuj.x1fcty0u.x1lliihq[rel="nofollow noreferrer"][href*="u="][target="_blank"]');
                    $destNodes->each(function (Crawler $destNode) use (&$adData) {
                        $href = $destNode->attr('href');
                        if (preg_match('/u=([^&]+)/', $href, $matches)) {
                            $decodedUrl = urldecode($matches[1]);
                            if (filter_var($decodedUrl, FILTER_VALIDATE_URL) && $adData['destination'] === null) {
                                $adData['destination'] = $decodedUrl;
                                return false;
                            }
                        }
                    });
                    if (!$adData['destination']) {
                        Log::warning("No valid destination found for Library ID {$adData['library_id']}");
                    }
                }
            } else {
                // Fallback
                $destNodes = $container->filter('a.x1hl2dhg.x1lku1pv.x8t9es0.x1fvot60.xxio538.xjnfcd9.xq9mrsl.x1yc453h.x1h4wwuj.x1fcty0u.x1lliihq[rel="nofollow noreferrer"][href*="u="][target="_blank"]');
                $destNodes->each(function (Crawler $destNode) use (&$adData) {
                    $href = $destNode->attr('href');
                    if (preg_match('/u=([^&]+)/', $href, $matches)) {
                        $decodedUrl = urldecode($matches[1]);
                        if (filter_var($decodedUrl, FILTER_VALIDATE_URL) && $adData['destination'] === null) {
                            $adData['destination'] = $decodedUrl;
                            return false;
                        }
                    }
                });
                if (!$adData['destination']) {
                    Log::warning("No valid destination found for Library ID {$adData['library_id']}");
                }
            }

            // Extract images
            $imageNodes = $container->filter('div.x1ywc1zp.x78zum5.xl56j7k.x1e56ztr.x1277o0a img');
            if ($imageNodes->count() > 0) {
                $imageUrls = $imageNodes->each(function (Crawler $img) {
                    $src = $img->attr('src');
                    return filter_var($src, FILTER_VALIDATE_URL) && strpos($src, 's600x600') !== false ? $src : null;
                });
                $imageUrls = array_unique(array_filter($imageUrls));
                $imageUrls = array_slice($imageUrls, 0, 1);
                $adData['snapshot_urls'] = $imageUrls;
            }

            // Extract videos
            $videoNodes = $container->filter('video[src], video > source, div.x1qjc9v5 video');
            if ($videoNodes->count() > 0) {
                $videoUrls = $videoNodes->each(function (Crawler $node) {
                    if ($node->nodeName() === 'video' && $node->attr('src')) {
                        return filter_var($node->attr('src'), FILTER_VALIDATE_URL) ? $node->attr('src') : null;
                    } elseif ($node->nodeName() === 'source' && $node->attr('src')) {
                        return filter_var($node->attr('src'), FILTER_VALIDATE_URL) ? $node->attr('src') : null;
                    }
                    return null;
                });
                $videoUrls = array_filter($videoUrls);
                $adData['snapshot_urls'] = array_merge($adData['snapshot_urls'], $videoUrls);
            }

            // Log summary
            Log::info("Extracted ad card", [
                'Library ID' => $adData['library_id'],
                'Destination' => $adData['destination'] ?? 'None',
                'Start Date' => $adData['start_date'] ?? 'None',
                'Platforms' => implode(', ', $adData['platforms']),
                'Image Count' => count(array_filter($adData['snapshot_urls'], fn($url) => strpos($url, '.jpg') !== false)),
                'Video Count' => count(array_filter($adData['snapshot_urls'], fn($url) => strpos($url, '.mp4') !== false)),
                'Snapshot URLs' => $adData['snapshot_urls'] ?: ['None'],
                'CTA' => $adData['cta'] ?? 'None',
            ]);

            $ads[] = $adData;
        });

        Log::info("Processed " . count($ads) . " ad cards");
        return ['ads' => $ads];
    }

    /**
     * Parse a date string into a standardized format.
     *
     * @param string $dateStr The date string (e.g., "May 9, 2025")
     * @return string|null The parsed date in Y-m-d format or null if invalid
     */
    protected function parseDate($dateStr)
    {
        try {
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}