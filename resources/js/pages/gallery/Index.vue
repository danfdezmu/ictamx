<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import GalleryPhotoController from '@/actions/App/Http/Controllers/GalleryPhotoController';
import Heading from '@/components/Heading.vue';
import { index as albumIndex, show as albumShow } from '@/routes/albums';
import { index } from '@/routes/gallery';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Galería',
                href: index(),
            },
        ],
    },
});

defineProps<{
    photos: Array<{
        id: number;
        url: string;
        alt: string | null;
        author: string;
        album_id: number | null;
        album: string | null;
        tags: Array<{ id: number; name: string }>;
        created_at: string;
        can_delete: boolean;
    }>;
    tags: Array<{ id: number; name: string }>;
    active_tag: number | null;
}>();

function confirmDelete(event: MouseEvent): void {
    if (!confirm('¿Eliminar esta foto?')) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head title="Galería" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Galería"
                description="Filtra por etiqueta. El slide público sigue mostrando las seis fotos más recientes."
            />
            <Link :href="albumIndex()" class="text-sm underline-offset-4 hover:underline">Álbumes</Link>
        </div>

        <div class="flex flex-wrap gap-2">
            <Link
                :href="index()"
                class="rounded-full border px-3 py-1 text-sm"
                :class="active_tag === null ? 'bg-primary text-primary-foreground' : ''"
            >
                Todas
            </Link>
            <Link
                v-for="tag in tags"
                :key="tag.id"
                :href="index({ query: { etiqueta: tag.id } })"
                class="rounded-full border px-3 py-1 text-sm"
                :class="active_tag === tag.id ? 'bg-primary text-primary-foreground' : ''"
            >
                {{ tag.name }}
            </Link>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="border-b text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Foto</th>
                        <th class="px-4 py-3 font-medium">Descripción</th>
                        <th class="px-4 py-3 font-medium">Álbum</th>
                        <th class="px-4 py-3 font-medium">Etiquetas</th>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="photos.length === 0">
                        <td colspan="6" class="px-4 py-8 text-muted-foreground">
                            {{ active_tag === null ? 'Todavía no hay fotos.' : 'No hay fotos con esa etiqueta.' }}
                        </td>
                    </tr>
                    <tr v-for="photo in photos" :key="photo.id" class="border-b last:border-b-0">
                        <td class="px-4 py-3">
                            <img :src="photo.url" :alt="photo.alt || ''" class="h-16 w-28 rounded-md object-cover" />
                        </td>
                        <td class="px-4 py-3">{{ photo.alt }}</td>
                        <td class="px-4 py-3">
                            <Link v-if="photo.album_id" :href="albumShow(photo.album_id)" class="underline-offset-4 hover:underline">
                                {{ photo.album }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">
                            <span v-for="tag in photo.tags" :key="tag.id" class="mr-2 inline-block">{{ tag.name }}</span>
                        </td>
                        <td class="px-4 py-3">{{ photo.created_at }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                v-if="photo.can_delete"
                                :href="GalleryPhotoController.destroy.url(photo.id)"
                                method="delete"
                                as="button"
                                class="text-sm text-destructive"
                                @click="confirmDelete"
                            >
                                Eliminar
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
