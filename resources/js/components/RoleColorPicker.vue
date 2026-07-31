<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Label } from '@/components/ui/label';

type Props = {
    modelValue: string | null;
    label: string;
    name: string;
    variant: 'bg' | 'text';
    required?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    required: false,
    modelValue: null,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// Predefined Tailwind palette tuned for role badges.
const palette = [
    { name: 'Slate', bg: 'bg-slate-100', text: 'text-slate-800' },
    { name: 'Gray', bg: 'bg-gray-100', text: 'text-gray-800' },
    { name: 'Zinc', bg: 'bg-zinc-100', text: 'text-zinc-800' },
    { name: 'Neutral', bg: 'bg-neutral-100', text: 'text-neutral-800' },
    { name: 'Stone', bg: 'bg-stone-100', text: 'text-stone-800' },
    { name: 'Red', bg: 'bg-red-50', text: 'text-red-800' },
    { name: 'Orange', bg: 'bg-orange-50', text: 'text-orange-800' },
    { name: 'Amber', bg: 'bg-amber-50', text: 'text-amber-800' },
    { name: 'Yellow', bg: 'bg-yellow-50', text: 'text-yellow-800' },
    { name: 'Lime', bg: 'bg-lime-50', text: 'text-lime-800' },
    { name: 'Green', bg: 'bg-green-50', text: 'text-green-800' },
    { name: 'Emerald', bg: 'bg-emerald-50', text: 'text-emerald-800' },
    { name: 'Teal', bg: 'bg-teal-50', text: 'text-teal-800' },
    { name: 'Cyan', bg: 'bg-cyan-50', text: 'text-cyan-800' },
    { name: 'Sky', bg: 'bg-sky-50', text: 'text-sky-800' },
    { name: 'Blue', bg: 'bg-blue-50', text: 'text-blue-800' },
    { name: 'Indigo', bg: 'bg-indigo-50', text: 'text-indigo-800' },
    { name: 'Violet', bg: 'bg-violet-50', text: 'text-violet-800' },
    { name: 'Purple', bg: 'bg-purple-50', text: 'text-purple-800' },
    { name: 'Fuchsia', bg: 'bg-fuchsia-50', text: 'text-fuchsia-800' },
    { name: 'Pink', bg: 'bg-pink-50', text: 'text-pink-800' },
    { name: 'Rose', bg: 'bg-rose-50', text: 'text-rose-800' },
] as const;

const currentValue = ref<string>(props.modelValue ?? '');

watch(
    () => props.modelValue,
    (newValue) => {
        currentValue.value = newValue ?? '';
    },
);

function valueFor(entry: (typeof palette)[number]): string {
    return props.variant === 'bg' ? entry.bg : entry.text;
}

const selectedEntry = computed(() => {
    if (!currentValue.value) {
        return null;
    }

    return (
        palette.find((p) => valueFor(p) === currentValue.value) ?? null
    );
});

const previewClass = computed(() => {
    const entry = selectedEntry.value;

    if (!entry) {
        return 'border-muted-foreground/30 bg-muted text-muted-foreground';
    }

    return `${entry.bg} ${entry.text} border-transparent`;
});

function onChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    currentValue.value = target.value;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <div class="grid gap-2">
        <Label :for="name">{{ label }}</Label>
        <div class="flex items-center gap-2">
            <!-- Native <select> so the value is submitted with the form
                 via its `name` attribute. Wrapped in our own styling. -->
            <div class="relative w-full">
                <select
                    :id="name"
                    :name="name"
                    :value="currentValue"
                    :required="required"
                    class="flex h-9 w-full appearance-none rounded-md border border-input bg-transparent px-3 py-1 pr-8 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    @change="onChange"
                >
                    <option value="" disabled>
                        Selecciona un color
                    </option>
                    <option
                        v-for="entry in palette"
                        :key="entry.name"
                        :value="valueFor(entry)"
                    >
                        {{ entry.name }}
                    </option>
                </select>
                <span
                    class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-muted-foreground"
                    aria-hidden="true"
                >
                    ▾
                </span>
            </div>
            <span
                v-if="selectedEntry"
                class="inline-flex items-center rounded-md border px-2 py-1 text-xs font-medium"
                :class="previewClass"
            >
                {{ selectedEntry.name }}
            </span>
        </div>
    </div>
</template>
