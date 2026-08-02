<script setup lang="ts">
import { Check, ChevronDown, Globe } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const { locale, locales, setLocale, t } = useI18n();
const open = ref<boolean>(false);
</script>

<template>
    <DropdownMenu v-model:open="open">
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="sm"
                :aria-label="t('common.actions') + ': ' + t('sidebar.appearance')"
                class="gap-2"
            >
                <Globe class="h-4 w-4" />
                <span class="hidden text-sm font-medium sm:inline">
                    {{ locales.find((l) => l.code === locale)?.label ?? locale }}
                </span>
                <ChevronDown class="h-3 w-3 opacity-60" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="min-w-[10rem]">
            <DropdownMenuItem
                v-for="entry in locales"
                :key="entry.code"
                @click="setLocale(entry.code)"
                class="cursor-pointer"
            >
                <span class="mr-2 text-base">{{ entry.flag }}</span>
                <span class="flex-1">{{ entry.label }}</span>
                <Check v-if="entry.code === locale" class="h-4 w-4 text-teal-500" />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
