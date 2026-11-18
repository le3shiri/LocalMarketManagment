<x-layouts.app title="Categorías">
    <h1 class="mb-6 text-[28px] font-extrabold tracking-tight text-ink">Categorías</h1>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="overflow-x-auto rounded-card border border-line bg-white lg:col-span-2">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                    <tr>
                        <th class="px-4 py-3">Orden</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Productos</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="px-4 py-3 text-ink-muted">{{ $category->sort_order }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-ink">{{ $category->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $category->description }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $category->products_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="rounded-control px-2 py-1 text-action hover:bg-action-soft">Editar</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm({{ Js::from('¿Eliminar la categoría '.$category->name.'?') }})">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-control px-2 py-1 text-red-600 hover:bg-red-50">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-ink-muted">Todavía no hay categorías.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-card title="Nueva categoría">
            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4">
                @csrf
                <x-input name="name" label="Nombre" required />
                <x-input name="description" label="Descripción" />
                <x-input name="sort_order" label="Orden" type="number" min="0" value="0" />
                <x-button class="w-full">Crear categoría</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.app>
