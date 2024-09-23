# GMP Related Products WordPress Plugin

## Description

The **GMP Related Products** plugin allows you to display a grid of WooCommerce related products anywhere on your WordPress site using a simple shortcode. The plugin provides flexible options to filter related products, such as excluding out-of-stock or backorder items, and filtering by specific product brands.

## Features

- Display a related products grid using a shortcode.
- Filter related products based on current product, cart, or randomly selected products.
- Option to exclude out-of-stock and backorder products.
- Filter related products by specific brands selected in the plugin's admin settings.
- Easily searchable product list in the admin interface for enhanced management.

## Installation

1. Download the plugin zip file or clone the repository.
2. Upload the plugin to your WordPress site via the WordPress Admin Dashboard by navigating to **Plugins > Add New > Upload Plugin**.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. After activation, configure the plugin settings under **WOO Related Products** in the admin menu.

## Usage

To display the related products grid on any page or post, use the following shortcode:

```
[gmp_woo_related_products]
```

This will automatically fetch and display related products based on the current product, cart items, or random products.

## Admin Settings

Navigate to **WOO Related Products** in the admin dashboard to customize the plugin settings:

- **Exclude Out of Stock Products**: Option to hide products that are out of stock.
- **Exclude Backorder Products**: Option to exclude products on backorder.
- **Select Brands**: Choose specific brands to display related products from.

## Shortcode Example

To display a related products grid, simply add the following shortcode in any post, page, or widget:

```
[gmp_woo_related_products]
```

## Changelog

### 1.0.0
- Initial release with shortcode support and admin settings for filtering related products.

## Author

Developed by **GMP**.

## License

This plugin is released under the GPLv2 or later license.
