<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    tags: Array<{ id: number; name: string }>;
    selected: string[];
    inputId: string;
}>();
</script>

<template>
    <div class="grid gap-3">
        <div v-if="tags.length > 0" class="flex flex-wrap gap-2">
            <label
                v-for="tag in tags"
                :key="tag.id"
                class="flex items-center gap-2 rounded-full border px-3 py-1 text-sm"
            >
                <input type="checkbox" name="tags[]" :value="tag.name" :checked="selected.includes(tag.name)" />
                {{ tag.name }}
            </label>
        </div>
        <div class="grid gap-2">
            <Label :for="inputId">Otras etiquetas</Label>
            <Input :id="inputId" name="new_tags" type="text" placeholder="adoración, celebración" />
        </div>
    </div>
</template>
