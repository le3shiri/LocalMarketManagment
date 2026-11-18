<x-layouts.app :title="$product->exists ? 'Editar producto' : 'Nuevo producto'">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('admin.products.index') }}" class="text-sm text-stone-500 hover:underline">← Volver a productos</a>

        <x-card :title="$product->exists ? 'Editar '.$product->name : 'Nuevo producto'" class="mt-3">
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
                  class="grid gap-4 sm:grid-cols-2">
                @csrf
                @if ($product->exists) @method('PUT') @endif

                <x-input name="name" label="Nombre" :value="$product->name" required />
                <x-input name="sku" label="SKU (código interno)" :value="$product->sku" required />

                <div class="space-y-1">
                    <label for="category_id" class="block text-sm font-medium text-stone-700">Categoría</label>
                    <select id="category_id" name="category_id" required class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        <option value="">Elige una categoría…</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1">
                    <label for="unit_type" class="block text-sm font-medium text-stone-700">Tipo de venta</label>
                    <select id="unit_type" name="unit_type" class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        @foreach ($unitTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('unit_type', $product->unit_type?->value) === $type->value)>{{ $type->label() }} (€/{{ $type->suffix() }})</option>
                        @endforeach
                    </select>
                    @error('unit_type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <x-input name="price" label="Precio (€)" inputmode="decimal" :value="$product->price" required />
                <x-input name="location" label="Ubicación (pasillo, estante, cámara)" :value="$product->location" />

                <div class="space-y-1 sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-stone-700">Descripción</label>
                    <textarea id="description" name="description" rows="3" class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">{{ old('description', $product->description) }}</textarea>
                    @error('description') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1">
                    <label for="image" class="block text-sm font-medium text-stone-700">Imagen (JPG, PNG o WebP, máx. 2 MB)</label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                    @error('image') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="" class="mt-2 h-24 w-24 rounded-lg object-cover">
                    @endif
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_perishable" value="0">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_perishable" value="1" class="rounded border-stone-300 text-emerald-600" @checked(old('is_perishable', $product->is_perishable))>
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
