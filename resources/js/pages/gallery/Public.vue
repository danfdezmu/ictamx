<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicGalleryController from '@/actions/App/Http/Controllers/PublicGalleryController';
import { home } from '@/routes';

defineProps<{
    albums: Array<{
        id: number;
        name: string;
        photos_count: number;
    }>;
    photos: Array<{
        id: number;
        src: string;
        alt: string | null;
    }>;
    album: {
        id: number;
        name: string;
    } | null;
}>();

function sectionHref(hash: string): string {
    return `${home.url()}#${hash}`;
}
</script>

<template>
    <Head title="Galería" />

    <div class="min-h-screen bg-[#100e0c] text-[#f4efe6]">
        <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#100e0c]">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-5">
                <Link :href="home()" class="flex items-center">
                    <img src="/logo/icta_wh.png" alt="ICTA" class="h-11 w-auto" />
                </Link>
                <nav class="hidden items-center gap-8 text-sm text-[#f4efe6]/75 md:flex">
                    <a :href="sectionHref('visita')" class="hover:text-[#f4efe6]">Visita</a>
                    <a :href="sectionHref('ministerios')" class="hover:text-[#f4efe6]">Ministerios</a>
                    <a :href="sectionHref('redes')" class="hover:text-[#f4efe6]">Redes</a>
                    <a :href="sectionHref('contacto')" class="hover:text-[#f4efe6]">Contacto</a>
                    <Link :href="PublicGalleryController.index()" class="text-[#f4efe6]">Galería</Link>
                </nav>
                <Link
                    :href="home()"
                    class="rounded-full bg-[#f4efe6] px-4 py-2 text-sm font-medium text-[#100e0c]"
                >
                    Inicio
                </Link>
            </div>
        </header>

        <main class="relative overflow-hidden px-5 pt-28 pb-24">
            <div aria-hidden="true" class="section-glow" />
            <div class="relative mx-auto max-w-6xl">
                <p class="text-sm tracking-[0.22em] text-[#d4a574] uppercase">Galería</p>
                <h1 class="mt-3 font-serif text-4xl sm:text-6xl">
                    {{ album ? album.name : 'Fotos de la iglesia' }}
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-6 text-[#f4efe6]/65">
                    {{
                        album
                            ? 'Fotos de este álbum.'
                            : 'Elige un álbum o recorre todas las fotos.'
                    }}
                </p>

                <nav class="mt-8 flex flex-wrap gap-2" aria-label="Álbumes">
                    <Link
                        :href="PublicGalleryController.index()"
                        class="rounded-full px-4 py-2 text-sm"
                        :class="album === null ? 'bg-[#d4a574] text-[#100e0c]' : 'border border-white/15 text-[#f4efe6]/75'"
                    >
                        Todas
                    </Link>
                    <Link
                        v-for="item in albums"
                        :key="item.id"
                        :href="PublicGalleryController.show(item.id)"
                        class="rounded-full px-4 py-2 text-sm"
                        :class="album?.id === item.id ? 'bg-[#d4a574] text-[#100e0c]' : 'border border-white/15 text-[#f4efe6]/75'"
                    >
                        {{ item.name }}
                        <span class="text-xs opacity-70">{{ item.photos_count }}</span>
                    </Link>
                </nav>

                <p v-if="photos.length === 0" class="mt-16 text-sm text-[#f4efe6]/65">
                    {{ album ? 'Este álbum todavía no tiene fotos.' : 'Todavía no hay fotos.' }}
                </p>
                <div v-else class="mt-12 grid gap-6 sm:grid-cols-2">
                    <figure v-for="photo in photos" :key="photo.id" class="overflow-hidden rounded-3xl border border-white/10">
                        <img :src="photo.src" :alt="photo.alt || ''" class="aspect-video w-full object-cover" />
                        <figcaption v-if="photo.alt" class="px-5 py-4 text-sm text-[#f4efe6]/75">{{ photo.alt }}</figcaption>
                    </figure>
                </div>
            </div>
        </main>
    </div>
</template>
