<div class="space-y-1 sm:col-span-2">
    <label for="source_message" class="block text-sm font-semibold text-ink-soft">Mensaje original de WhatsApp</label>
    <textarea id="source_message" wire:model="source_message" rows="3" placeholder="Pega aquí el mensaje del cliente"
              class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]"></textarea>
    @error('source_message') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
</div>
<div class="space-y-1 sm:col-span-2">
    <label for="source_reference" class="block text-sm font-semibold text-ink-soft">Referencia de la conversación (opcional)</label>
    <input id="source_reference" type="text" wire:model="source_reference" placeholder="Ej.: chat de Marta, 18/11 09:42"
           class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
    @error('source_reference') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
</div>
