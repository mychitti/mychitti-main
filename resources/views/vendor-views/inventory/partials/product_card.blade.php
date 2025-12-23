 <style>
     /* product card */
     .product-card {
         background: white;
         border-radius: 16px;
         box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
         overflow: hidden;
         width: 320px;
         transition: transform 0.3s ease, box-shadow 0.3s ease;
     }

     .product-card:hover {
         transform: translateY(-5px);
         box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
     }

     .product-image {
         width: 100%;
         height: 200px;
         background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
         position: relative;
         overflow: hidden;
     }

     .product-image img {
         width: 100%;
         height: 100%;
         object-fit: cover;
     }

     .product-image::before {
         content: '';
         position: absolute;
         top: 0;
         left: 0;
         right: 0;
         bottom: 0;
         background: rgba(0, 0, 0, 0.1);
         z-index: 1;
     }

     .product-content {
         padding: 20px;
     }

     .product-name {
         font-size: 20px;
         font-weight: 700;
         color: #2d3748;
         margin-bottom: 8px;
         line-height: 1.3;
     }

     .product-sku {
         font-size: 12px;
         color: #718096;
         background: #f7fafc;
         padding: 4px 8px;
         border-radius: 6px;
         display: inline-block;
         margin-bottom: 16px;
         font-weight: 500;
     }

     .product-details {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 12px;
         margin-bottom: 16px;
     }

     .detail-item {
         display: flex;
         flex-direction: column;
     }

     .detail-label {
         font-size: 11px;
         color: #a0aec0;
         text-transform: uppercase;
         font-weight: 600;
         letter-spacing: 0.5px;
         margin-bottom: 4px;
     }

     .detail-value {
         font-size: 14px;
         color: #4a5568;
         font-weight: 600;
     }

     .pricing-section {
         background: #f8fafc;
         padding: 16px;
         border-radius: 12px;
         margin-bottom: 16px;
     }

     .pricing-grid {
         display: grid;
         grid-template-columns: repeat(3, 1fr);
         gap: 12px;
     }

     .price-item {
         text-align: center;
     }

     .price-label {
         font-size: 10px;
         color: #718096;
         text-transform: uppercase;
         font-weight: 600;
         letter-spacing: 0.5px;
         margin-bottom: 4px;
     }

     .price-value {
         font-size: 14px;
         font-weight: 700;
     }

     .mrp {
         color: #e53e3e;
         text-decoration: line-through;
     }

     .selling-price {
         color: #38a169;
     }

     .landing-price {
         color: #3182ce;
     }

     .variations {
         margin-top: 16px;
     }

     .variations-label {
         font-size: 12px;
         color: #4a5568;
         font-weight: 600;
         margin-bottom: 8px;
     }

     .variation-tags {
         display: flex;
         flex-wrap: wrap;
         gap: 6px;
     }

     .variation-tag {
         background: #e6fffa;
         color: #234e52;
         padding: 4px 10px;
         border-radius: 20px;
         font-size: 11px;
         font-weight: 500;
         border: 1px solid #81e6d9;
     }

     .stock-indicator {
         position: absolute;
         top: 12px;
         right: 12px;
         background: rgba(255, 255, 255, 0.9);
         padding: 4px 8px;
         border-radius: 6px;
         font-size: 11px;
         font-weight: 600;
         color: #38a169;
         backdrop-filter: blur(10px);
     }

     .stock-indicator.danger {
         color: #a13838ff;

     }
 </style>
 <div class="product-card">
     <div class="product-image">
         <div class="product-placeholder">
             <img class=" onerror-image"
                 src="{{ \App\CentralLogics\Helpers::onerror_image_helper($item['image'], asset('storage/app/public/inventory-item/') . '/' . $item['image'], asset('public/assets/admin/img/160x160/img2.jpg'), 'inventory-item/') }}"
                 data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                 alt="{{ $item->item_name }} image">
         </div>
         @if ($item->stock)
             <div class="stock-indicator">In Stock</div>
         @else
             <div class="stock-indicator">Out of Stock</div>
         @endif
     </div>

     <div class="product-content">
         <h3 class="product-name">{{ $item->item_name }}</h3>
         <div class="product-sku">SKU: {{ $item->sku_id }}</div>

         <div class="product-details">
             <div class="detail-item">
                 <span class="detail-label">Quantity</span>
                 <span class="detail-value">{{ $item->stock }} {{ $item->itemunit?->unit }}</span>
             </div>
             <div class="detail-item">
                 <span class="detail-label">Storage</span>
                 <span class="detail-value">{{ ucfirst($item->storage_unit?->full_hierarchy_name) }}</span>
             </div>
         </div>

         <div class="pricing-section">
             <div class="pricing-grid">
                 <div class="price-item">
                     <div class="price-label">MRP</div>
                     <div class="price-value mrp">{{ _price($item->mrp) }}</div>
                 </div>
                 <div class="price-item">
                     <div class="price-label">Selling</div>
                     <div class="price-value selling-price">{{ _price($item->selling_price) }}</div>
                 </div>
                 <div class="price-item">
                     <div class="price-label">Landing</div>
                     <div class="price-value landing-price">{{ _price($item->landing_price) }}</div>
                 </div>
             </div>
         </div>
         @php $variations  = json_decode($item->variations); @endphp
         @if ($variations)
             <div class="variations">
                 <div class="variations-label">Available Variations</div>
                 <div class="variation-tags">
                     @foreach ($variations as $key2 => $vr)
                         <span class="variation-tag">{{ ucwords($vr->type) }}</span>
                     @endforeach
                 </div>
             </div>
         @endif
     </div>
 </div>
