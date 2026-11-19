<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AlbumController from '@/actions/App/Http/Controllers/AlbumController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show } from '@/routes/albums';

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
    albums: Array<{
        id: number;
        name: string;
        photos_count: number;
    }>;
}>();
</script>

<template>
    <Head title="Álbumes" />

    <div class="flex flex-col gap-8 p-4">
        <Heading title="Álbumes" description="Cada álbum pone su nombre como etiqueta en todas sus fotos." />

        <Form
            v-bind="AlbumController.store.form()"
            reset-on-success
            class="grid gap-4 rounded-xl border p-4 md:grid-cols-[1fr_auto] md:items-end"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Nombre</Label>
                <Input id="name" name="name" type="text" maxlength="80" required placeholder="Jóvenes" />
                <InputError :message="errors.name" />
            </div>
            <Button type="submit" :disabled="processing">Crear álbum</Button>
        </Form>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Álbum</th>
                        <th class="px-4 py-3 font-medium">Fotos</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="albums.length === 0">
                        <td colspan="2" class="px-4 py-8 text-muted-foreground">Todavía no hay álbumes.</td>
                    </tr>
                    <tr v-for="album in albums" :key="album.id" class="border-b last:border-b-0">
                        <td class="px-4 py-3">
                            <Link :href="show(album.id)" class="font-medium underline-offset-4 hover:underline">
                                {{ album.name }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">{{ album.photos_count }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
