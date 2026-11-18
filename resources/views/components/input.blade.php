@props(['name', 'label', 'type' => 'text', 'value' => null])

<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-sm font-semibold text-ink-soft">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
           @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
           {{ $attributes->class([
               'block min-h-11 w-full rounded-control border bg-card px-3.5 text-[15px] text-ink placeholder:text-ink-muted focus:border-action focus:outline-none',
               'border-red-600' => $errors->has($name),
               'border-line-strong' => ! $errors->has($name),
           ]) }}>
    @error($name)
        <p id="{{ $name }}-error" class="text-[13px] font-semibold text-red-700">{{ $message }}</p>
    @enderror
</div>
