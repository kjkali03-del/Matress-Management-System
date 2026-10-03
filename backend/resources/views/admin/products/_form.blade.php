<div class="product-form-grid">
    <div class="product-field">
        <label for="name">Product name *</label>
        <input id="name" name="name" value="{{ old('name', $product->name ?? '') }}" required>
    </div>
    <div class="product-field">
        <label for="sku">SKU</label>
        <input id="sku" name="sku" value="{{ old('sku', $product->sku ?? '') }}" placeholder="e.g. GOLD-56-10">
    </div>
    <div class="product-field">
        <label for="product_category_id">Category *</label>
        <select id="product_category_id" name="product_category_id" required>
            <option value="">Select category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('product_category_id', $product->product_category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="product-field">
        <label for="size">Size / Variant</label>
        <input id="size" name="size" value="{{ old('size', $product->size ?? '') }}" placeholder="5x6 Inch 10">
    </div>
    <div class="product-field">
        <label for="price">Selling price (TSh) *</label>
        <input id="price" type="number" min="0" step="0.01" name="price" value="{{ old('price', $product->price ?? '') }}" required>
    </div>
    <div class="product-field">
        <label for="cost_price">Cost price (TSh)</label>
        <input id="cost_price" type="number" min="0" step="0.01" name="cost_price" value="{{ old('cost_price', $product->cost_price ?? 0) }}">
    </div>
    <div class="product-field">
        <label for="stock_quantity">Stock quantity</label>
        <input id="stock_quantity" type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}">
    </div>
    <div class="product-field">
        <label for="reorder_level">Reorder level</label>
        <input id="reorder_level" type="number" min="0" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level ?? 0) }}">
    </div>

    <div class="product-field product-field--full wgp-product-image-field">
        <label for="image">Product image</label>
        <label class="wgp-image-drop" for="image">
            <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-preview-input>
            <span class="wgp-image-preview" data-image-preview>
                @if(isset($product) && !empty($product->image_path))<img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}">@else<span class="wgp-image-placeholder">＋<small>Upload product image</small></span>@endif
            </span>
            <span><strong>Upload product image</strong><small>JPG, PNG or WebP · max 5 MB</small></span>
        </label>
    </div>

    <div class="product-field product-field--full">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="product-field product-field--full">
        <label class="product-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Product is active and available for sale</label>
    </div>
</div>
