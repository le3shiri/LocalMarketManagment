<x-layouts.app title="Editar categoría">
    <div class="mx-auto max-w-lg">
        <a href="{{ route('admin.categories.index') }}" class="text-sm text-stone-500 hover:underline">← Volver a categorías</a>

        <x-card :title="'Editar '.$category->name" class="mt-3">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-input name="name" label="Nombre" :value="$category->name" required />
                <x-input name="description" label="Descripción" :value="$category->description" />
                <x-input name="sort_order" label="Orden" type="number" min="0" :value="$category->sort_order" />
                <x-button>Guardar cambios</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.app>
