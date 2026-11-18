<div class="space-y-1 sm:col-span-2">
    <label for="source_message" class="block text-sm font-medium text-stone-700">Mensaje original de WhatsApp</label>
    <textarea id="source_message" wire:model="source_message" rows="3" placeholder="Pega aquí el mensaje del cliente"
              class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm"></textarea>
    @error('source_message') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
</div>
<div class="space-y-1 sm:col-span-2">
    <label for="source_reference" class="block text-sm font-medium text-stone-700">Referencia de la conversación (opcional)</label>
    <input id="source_reference" type="text" wire:model="source_reference" placeholder="Ej.: chat de Marta, 18/11 09:42"
           class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
    @error('source_reference') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
</div>
