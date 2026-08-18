<?php

declare(strict_types=1);

namespace App\Integrations\ClientOrders\Models;

use App\Domain\Ordering\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A partner's own product code rarely matches our SKU, and we don't control
 * their catalogue. This is the lookup table between the two, per partner.
 */
class PartnerProductMapping extends Model
{
    /** @use HasFactory<\Database\Factories\Integrations\ClientOrders\PartnerProductMappingFactory> */
    use HasFactory;

    protected $fillable = ['partner_key', 'partner_sku', 'product_id'];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
