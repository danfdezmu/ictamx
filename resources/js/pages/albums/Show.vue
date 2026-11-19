<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AlbumController from '@/actions/App/Http/Controllers/AlbumController';
import AlbumPhotoController from '@/actions/App/Http/Controllers/AlbumPhotoController';
import GalleryPhotoController from '@/actions/App/Http/Controllers/GalleryPhotoController';
import PhotoCropper from '@/components/gallery/PhotoCropper.vue';
import TagPicker from '@/components/gallery/TagPicker.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/albums';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Álbumes',
                href: index(),
            },
        ],
    },
});

defineProps<{
    album: {
        id: number;
        name: string;
        tag: string;
    };
    tags: Array<{ id: number; name: string }>;
    photos: Array<{
        id: number;
        url: string;
        alt: string | null;
        tags: Array<{ id: number; name: string }>;
        can_delete: boolean;
    }>;
}>();

const cropBusy = ref(false);

function confirmDelete(event: MouseEvent): void {
    if (!confirm('¿Eliminar esta foto?')) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head :title="album.name" />

    <div class="flex flex-col gap-8 p-4">
        <Heading :title="album.name" description="La etiqueta del álbum queda en cada foto. Puedes sumar otras." />

        <Form
            v-bind="AlbumController.update.form(album.id)"
            class="grid gap-4 rounded-xl border p-4 md:grid-cols-[1fr_auto] md:items-end"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="album-name">Nombre del álbum</Label>
                <Input id="album-name" name="name" type="text" maxlength="80" required :default-value="album.name" />
                <InputError :message="errors.name" />
            </div>
            <Button type="submit" :disabled="processing">Guardar álbum</Button>
        </Form>

        <Form
            v-bind="AlbumPhotoController.store.form(album.id)"
            enctype="multipart/form-data"
            reset-on-success
            class="grid gap-4 rounded-xl border p-4"
            v-slot="{ errors, processing, wasSuccessful }"
        >
            <h2 class="text-base font-medium">Subir foto</h2>
            <p class="text-sm text-muted-foreground">
                Esta foto llevará la etiqueta <span class="font-medium text-foreground">{{ album.tag }}</span>.
            </p>
            <PhotoCropper :error="errors.image" :clear-when="wasSuccessful" @update:busy="cropBusy = $event">
                <div class="grid gap-2">
                    <Label for="alt">Descripción (opcional)</Label>
                    <Input id="alt" name="alt" type="text" maxlength="160" placeholder="Qué se ve en la foto" />
                    <InputError :message="errors.alt" />
                </div>
            </PhotoCropper>
            <TagPicker :tags="tags" :selected="[]" input-id="new-photo-tags" />
            <InputError :message="errors.tags" />
            <Button type="submit" :disabled="processing || cropBusy" class="w-fit">Agregar foto</Button>
        </Form>

        <div class="grid gap-4">
            <article v-for="photo in photos" :key="photo.id" class="grid gap-4 rounded-xl border p-4 md:grid-cols-[9rem_1fr]">
                <img :src="photo.url" :alt="photo.alt || ''" class="h-24 w-36 rounded-md object-cover" />
                <Form
                    v-bind="AlbumPhotoController.update.form({ album: album.id, galleryPhoto: photo.id })"
                    class="grid gap-3"
                    v-slot="{ errors, processing }"
                >
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="rounded-full bg-primary px-3 py-1 text-primary-foreground">{{ album.tag }}</span>
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`alt-${photo.id}`">Descripción (opcional)</Label>
                        <Input :id="`alt-${photo.id}`" name="alt" type="text" maxlength="160" :default-value="photo.alt || ''" />
                        <InputError :message="errors.alt" />
                    </div>
                    <TagPicker :tags="tags" :selected="photo.tags.map((tag) => tag.name)" :input-id="`tags-${photo.id}`" />
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="processing">Guardar etiquetas</Button>
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
                    </div>
                </Form>
            </article>
            <p v-if="photos.length === 0" class="text-sm text-muted-foreground">Este álbum todavía no tiene fotos.</p>
        </div>
    </div>
</template>
