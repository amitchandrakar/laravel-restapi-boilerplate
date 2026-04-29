# Product Page API Integration

This guide describes how to make the product detail page (e.g. `http://localhost:3000/product/executive-lunch-package`) dynamic using the API.

## API contract

| Item | Value |
|------|--------|
| **URL** | `{API_BASE}/api/v1/products/{slug}` |
| **Method** | `GET` |
| **URL param** | `slug` – product unique URL (e.g. `continental-breakfast-package-deal`, `executive-lunch-package`) |
| **Example** | `http://api.test/api/v1/products/continental-breakfast-package-deal` |

### Success response (200)

```json
{
  "success": true,
  "statusCode": 200,
  "message": "Product details fetched successfully",
  "data": {
    "product": {
      "id": 146,
      "name": "Continental Breakfast Package Deal",
      "slug": "continental-breakfast-package-deal",
      "dietary": [],
      "variants": [
        {
          "id": 162,
          "name": "Continental Breakfast Package Deal",
          "slug": "continental-breakfast-package-deal",
          "category": "breakfast-buffet-packages",
          "subcategory": "breakfast-buffet-packages-pastry-packages",
          "defaultImageUrl": "https://...",
          "imageUrls": ["https://..."],
          "shortDescription": "<p>...</p>",
          "sequence": 0,
          "basePrice": 178.5,
          "originalPrice": 178.5,
          "discountPercent": 0,
          "rating": 4.7,
          "dietaries": [],
          "servings": "Serves 10",
          "quantityInterval": 1,
          "options": [...],
          "nutritionalFacts": {...},
          "addons": [...],
          "reviews": {...}
        }
      ]
    }
  },
  "error": null,
  "meta": {...}
}
```

### Error response (404)

When the slug does not match a product:

```json
{
  "success": false,
  "statusCode": 404,
  "message": "Product not found.",
  "data": null,
  "error": {...},
  "meta": {...}
}
```

---

## Frontend integration

Use the **slug from the URL** (e.g. from the route `/product/[slug]`) to call the API and render the product.

### Next.js (App Router)

Assume route: `app/product/[slug]/page.tsx`.

```tsx
// app/product/[slug]/page.tsx
import { notFound } from 'next/navigation';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://api.test';

type ProductVariant = {
  id: number;
  name: string;
  slug: string;
  category: string;
  subcategory: string;
  defaultImageUrl: string | null;
  imageUrls: string[];
  shortDescription: string | null;
  basePrice: number;
  originalPrice: number;
  discountPercent: number;
  rating: number;
  servings: string | null;
  quantityInterval: number;
  options: Array<{
    id: number;
    name: string;
    type: string;
    required: boolean;
    maxSelections: number;
    selections: Array<{ id: number; name: string; imageUrl?: string; price: number; isFree: boolean; dietaries?: string[] }>;
  }>;
  addons: Array<{ id: number; name: string; price: number; selections: Array<{ id: number; name: string }> }>;
  nutritionalFacts: Record<string, string | number>;
  reviews: { items: unknown[]; pagination: Record<string, unknown> };
};

type Product = {
  id: number;
  name: string;
  slug: string;
  dietary: Array<{ id: number; name: string }>;
  variants: ProductVariant[];
};

type ApiResponse = {
  success: boolean;
  statusCode: number;
  message: string;
  data: { product: Product } | null;
};

export default async function ProductPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const res = await fetch(`${API_BASE}/api/v1/products/${encodeURIComponent(slug)}`, {
    cache: 'no-store', // or use revalidate as needed
  });

  if (!res.ok) {
    if (res.status === 404) notFound();
    throw new Error('Failed to load product');
  }

  const json: ApiResponse = await res.json();
  const product = json.data?.product;
  if (!product) notFound();

  const variant = product.variants[0]; // or let user pick variant

  return (
    <div>
      <h1>{product.name}</h1>
      <p>Slug: {product.slug}</p>
      {variant?.defaultImageUrl && (
        <img src={variant.defaultImageUrl} alt={product.name} />
      )}
      {variant?.shortDescription && (
        <div dangerouslySetInnerHTML={{ __html: variant.shortDescription }} />
      )}
      <p>From ${variant?.basePrice ?? 0}</p>
      {variant?.servings && <p>{variant.servings}</p>}
      {/* Render options, addons, etc. from variant */}
    </div>
  );
}
```

### Next.js with client-side fetch (e.g. `useEffect`)

If the product page is a client component and you use the slug from the router:

```tsx
'use client';

import { useParams } from 'next/navigation';
import { useEffect, useState } from 'react';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://api.test';

export default function ProductPage() {
  const params = useParams();
  const slug = params?.slug as string;
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) return;
    let cancelled = false;
    setLoading(true);
    setError(null);
    fetch(`${API_BASE}/api/v1/products/${encodeURIComponent(slug)}`)
      .then((res) => {
        if (!res.ok) {
          if (res.status === 404) throw new Error('Product not found');
          throw new Error('Failed to load product');
        }
        return res.json();
      })
      .then((json) => {
        if (!cancelled && json?.data?.product) setProduct(json.data.product);
      })
      .catch((e) => !cancelled && setError(e.message))
      .finally(() => !cancelled && setLoading(false));

    return () => { cancelled = true; };
  }, [slug]);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!product) return null;

  const variant = product.variants?.[0];
  return (
    <div>
      <h1>{product.name}</h1>
      {variant?.defaultImageUrl && (
        <img src={variant.defaultImageUrl} alt={product.name} />
      )}
      {variant?.shortDescription && (
        <div dangerouslySetInnerHTML={{ __html: variant.shortDescription }} />
      )}
      <p>From ${variant?.basePrice ?? 0}</p>
    </div>
  );
}
```

### Environment variable

Set the API base URL in the frontend (e.g. `.env.local`):

```env
NEXT_PUBLIC_API_URL=http://api.test
```

For production, use your real API base URL and ensure CORS allows your frontend origin (e.g. `localhost:3000` is already referenced in `SANCTUM_STATEFUL_DOMAINS` in the API repo).

---

## Summary

1. **Route**: Use a dynamic segment for the product slug (e.g. `/product/[slug]` or `/product/:slug`).
2. **Request**: `GET {API_BASE}/api/v1/products/{slug}` with the slug from the URL.
3. **Response**: Use `data.product` for the product object; `product.variants` for pricing, options, addons, and images.
4. **404**: If the API returns 404 or `data.product` is missing, show a “Product not found” or use your framework’s not-found page.

If your frontend lives in another repo, copy the relevant example (App Router or client-side) into your product page and point `API_BASE` to your API URL.
