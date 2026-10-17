# Saurabh_Preorder

Pre-order / backorder for out-of-stock and upcoming products for Magento 2.4 (Luma and Hyvä).

## Installation

```bash
bin/magento module:enable Saurabh_Preorder
bin/magento setup:upgrade
bin/magento setup:di:compile          # production mode only
bin/magento setup:static-content:deploy   # production mode only
bin/magento cache:flush
```

## Configuration

**Stores → Configuration → Saurabh Extensions → Pre Order**

| Group | Options |
|---|---|
| General | Enable Pre Orders, Allow Pre Orders for Products with Zero and Below Zero Qty, Disable Pre Order for Items with Qty Above Zero, Allow Mixed Carts, analytics Pending / Revenue order statuses |
| Display Options | Default Add to Cart Button Text, Default Pre Order Note (`{attribute_code}` / `{release_date}` variables), Pre-Order Note Output Place (CSS selectors), Assign Pre-Order Status, Show Pre-Order Note on Category Pages, Pre-Order Warning in Order, Pre-Order Warning in Shopping Cart (`%1$s` name, `%2$s` pre-ordered qty), Pre-Order Warning for Below Zero Qty (`%1$s` name, `%2$s` available qty) |
| Release Date | Use Release Date, Release Date Attribute (any date attribute), Release Date Format, Change Product Backorders Value on Release Date (cron), Pre-Order Note State on Release Date (Hidden / Replaced with default note) |
| Additional Options | Detect Complex Products Which Consist only of Pre-Order Components, Automatically Add Warning to Transactional Emails |
| Pre-Order Release Notification | Order Status, Notification Template, Email Sender |

The module is built on native backorders, so it only affects products with **Stock Status = In Stock**.

## Enabling pre-order on products

* **Single product:** Catalog → Products → edit → *Advanced Inventory* → untick *Use Config Settings* under
  *Backorders* → **Allow Pre-Orders**. The *Pre-Order* fieldset in the same modal holds
  *Pre-Order Cart Button*, *Pre-Order Note* and *Release Date* (empty = configuration defaults).
* **Mass action:** product grid → select products → *Update attributes* → *Advanced Inventory* tab →
  *Backorders* = Allow Pre-Orders; the *Pre-Order Cart Button / Note / Release Date* attributes are on the *Attributes* tab.
* **Configurable / grouped / bundle:** enable pre-order on the child simple products.
* Backorders = *Allow Pre-Orders* is stored as value `3` in `cataloginventory_stock_item.backorders`.
  MSI treats any value other than *No Backorders* as backorders enabled.

## Features

* Pre-order button text and note on product, category and search pages (configurable swatches/dropdowns, grouped rows, bundle).
* Pre-order note as an item option in cart, mini cart, checkout summary, customer account order view, admin order/invoice/shipment/credit memo, transactional emails.
* Note is saved per order item (`sales_order_item.saurabh_preorder_note`) – later product changes affect new orders only.
* Cart warnings, below-zero restriction (blocks checkout), optional mixed-cart restriction.
* Sales → Orders grid: **Pre Order** column with Yes/No filter.
* Order statuses *Pre-Order Pending* / *Pre-Order Processing* (Assign Pre-Order Status). Admin manual status changes away from a pre-order status pause automatic transitions until the admin sets a pre-order status again.
* Catalog / Cart Price Rules: condition **Pre-Order (Saurabh Pre-Order)** is Yes/No.
* Release date: `{release_date}` variable, daily cron `saurabh_preorder_release_date` (backorders change + page cache refresh).
* Release notification: product grid → Actions → **Send Pre-Order Release Notification** (products whose Backorders are no longer *Allow Pre-Orders*).
* Reports → Pre Order → **Pre Order Analytics** (totals, pending, revenue, products, customers, statuses).

## Hyvä / CSP / accessibility

Storefront output is a JSON config in a `data-saurabh-preorder` attribute plus one framework-free script
(`view/frontend/web/js/preorder.js`):

* Luma: loaded through `text/x-magento-init` (RequireJS).
* Hyvä: detected via the theme inheritance (`Hyva/*`) and loaded with `<script src defer>` – no inline JS, no `eval`,
  no inline styles, so it works with Hyvä Theme and Hyvä Checkout strict CSP.
  Cart/checkout use native item options, so they work in Hyvä Checkout as well.
* Notes use `role="status"` / `aria-live="polite"`; button `aria-label`/`title` are updated with the label;
  colours meet WCAG AA contrast.

## GraphQL (read)

```graphql
{
  products(filter: {sku: {eq: "24-MB01"}}) {
    items { sku saurabh_preorder { is_preorder cart_button_label note release_date } }
  }
}
```

`saurabh_preorder` is also available on `CartItemInterface` and `OrderItemInterface`.

## REST

`GET /V1/products/{sku}` / `PUT /V1/products/{sku}`:

* `extension_attributes.stock_item.backorders` / `use_config_backorders` (`3` = Allow Pre-Orders)
* `custom_attributes`: `saurabh_preorder_cart_label`, `saurabh_preorder_note`, `saurabh_preorder_release_date`

```json
{
  "product": {
    "sku": "24-MB01",
    "extension_attributes": {"stock_item": {"use_config_backorders": false, "backorders": 3, "is_in_stock": true}},
    "custom_attributes": [
      {"attribute_code": "saurabh_preorder_note", "value": "Ships on {release_date}"},
      {"attribute_code": "saurabh_preorder_release_date", "value": "2026-12-01"}
    ]
  }
}
```

## License

Copyright (C) 2026 Saurabh Gupta

This program is free software: you can redistribute it and/or modify it under the terms of the
GNU General Public License as published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
[GNU General Public License](LICENSE) for more details.
