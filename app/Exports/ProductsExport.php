<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $products;
    protected $includeImageUrls;

    public function __construct(array $products)
    {
        $this->products = $products;
        // Detect if image_urls is present in any product
        $this->includeImageUrls = !empty($products) && isset($products[0]['image_urls']);
    }

    public function collection()
    {
        return new Collection($this->products);
    }

    public function headings(): array
    {
        $headings = [
            'Title',
            'Price',
            'Unit Price',
            'Product Code',
            'Product Definition',
            'Detail URL',
        ];
        if ($this->includeImageUrls) {
            $headings[] = 'Image URLs';
        }
        return $headings;
    }

    public function map($product): array
    {
        $row = [
            $product['title'] ?? '',
            $product['price'] ?? '',
            $product['unit_price'] ?? '',
            $product['product_code'] ?? '',
            $product['product_definition'] ?? '',
            $product['detail_url'] ?? '',
        ];
        if ($this->includeImageUrls) {
            $row[] = isset($product['image_urls']) ? implode(',', $product['image_urls']) : '';
        }
        return $row;
    }
}