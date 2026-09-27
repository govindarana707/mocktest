@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false])

<label class="grid gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
    <span>{{ $label }}</span>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @required($required)
        {{ $attributes->merge(['class' => 'focus-ring rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-950 placeholder:text-slate-400 focus:border-brand-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white']) }}
    >
    @error($name)
        <span class="text-xs font-medium text-rose-600" role="alert">{{ $message }}</span>
    @enderror
</label>
