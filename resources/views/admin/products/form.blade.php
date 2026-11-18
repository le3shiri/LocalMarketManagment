<x-layouts.app :title="$product->exists ? 'Editar producto' : 'Nuevo producto'">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-11 items-center text-[15px] font-bold text-action hover:underline">← Volver a productos</a>

        <x-card :title="$product->exists ? 'Editar '.$product->name : 'Nuevo producto'" class="mt-3">
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
                  class="grid gap-4 sm:grid-cols-2">
                @csrf
                @if ($product->exists) @method('PUT') @endif

                <x-input name="name" label="Nombre" :value="$product->name" required />
                <x-input name="sku" label="SKU (código interno)" :value="$product->sku" required />
                <x-input name="barcode" label="Código de barras (EAN-13, opcional)" inputmode="numeric" maxlength="13" :value="$product->barcode" />

                <div class="space-y-1">
                    <label for="category_id" class="block text-sm font-semibold text-ink-soft">Categoría</label>
                    <select id="category_id" name="category_id" required class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        <option value="">Elige una categoría…</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1">
                    <label for="unit_type" class="block text-sm font-semibold text-ink-soft">Tipo de venta</label>
                    <select id="unit_type" name="unit_type" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        @foreach ($unitTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('unit_type', $product->unit_type?->value) === $type->value)>{{ $type->label() }} (€/{{ $type->suffix() }})</option>
                        @endforeach
                    </select>
                    @error('unit_type') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                </div>

                <x-input name="price" label="Precio con IVA (€)" inputmode="decimal" :value="$product->price" required />

                <div class="space-y-1">
                    <label for="vat_rate" class="block text-sm font-semibold text-ink-soft">IVA incluido</label>
                    <select id="vat_rate" name="vat_rate" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        @foreach ([4 => 'Superreducido 4 % (pan, leche, huevos, fruta, verdura)', 10 => 'Reducido 10 % (resto de alimentación)', 21 => 'General 21 %'] as $rate => $text)
                            <option value="{{ $rate }}" @selected((int) old('vat_rate', $product->vat_rate ?? 10) === $rate)>{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('vat_rate') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                </div>
                <x-input name="location" label="Ubicación (pasillo, estante, cámara)" :value="$product->location" />

                <div class="space-y-1 sm:col-span-2">
                    <label for="description" class="block text-sm font-semibold text-ink-soft">Descripción</label>
                    <textarea id="description" name="description" rows="3" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">{{ old('description', $product->description) }}</textarea>
                    @error('description') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1">
                    <label for="image" class="block text-sm font-semibold text-ink-soft">Imagen (JPG, PNG o WebP, máx. 2 MB)</label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-ink-soft file:mr-3 file:min-h-11 file:cursor-pointer file:rounded-control file:border file:border-line-strong file:bg-card file:px-4 file:text-sm file:font-bold file:text-ink hover:file:bg-sunken">
                    @error('image') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="" class="mt-2 h-24 w-24 rounded-control object-cover">
                    @endif
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_perishable" value="0">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_perishable" value="1" class="h-5 w-5 rounded border-line-strong accent-action" @checked(old('is_perishable', $product->is_perishable))>
                        Producto perecedero (se controla por lote y fecha de caducidad)
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <x-button>{{ $product->exists ? 'Guardar cambios' : 'Crear producto' }}</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
