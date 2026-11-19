<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    error?: string;
    clearWhen?: boolean;
}>();

const emit = defineEmits<{
    'update:busy': [busy: boolean];
}>();

const fileField = ref<{ $el?: HTMLInputElement } | null>(null);
const frame = ref<HTMLDivElement | null>(null);
const source = ref<HTMLImageElement | null>(null);
const previewUrl = ref<string | null>(null);
const readError = ref<string | null>(null);
const outputName = ref('foto.jpg');
const ready = ref(false);
const settling = ref(false);
const zoom = ref(1);
const focusX = ref(0);
const focusY = ref(0);
const left = ref(0);
const top = ref(0);
const displayWidth = ref(0);
const displayHeight = ref(0);

let naturalWidth = 0;
let naturalHeight = 0;
let dragging = false;
let dragPointerX = 0;
let dragPointerY = 0;
let dragFocusX = 0;
let dragFocusY = 0;
let dragScale = 1;
let exportGeneration = 0;
let assignedAt = 0;
let resizeObserver: ResizeObserver | null = null;

const outputWidth = 1600;

watch([previewUrl, ready, settling], () => {
    emit('update:busy', previewUrl.value !== null && (!ready.value || settling.value));
});

watch(
    () => props.clearWhen,
    (clear) => {
        if (clear) {
            clearPreview();
        }
    },
);

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    revokePreview();
});

function inputElement(): HTMLInputElement | null {
    return fileField.value?.$el ?? null;
}

function revokePreview(): void {
    if (previewUrl.value !== null) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
}

function clearPreview(): void {
    exportGeneration += 1;
    revokePreview();
    resizeObserver?.disconnect();
    resizeObserver = null;
    readError.value = null;
    ready.value = false;
    settling.value = false;
    zoom.value = 1;
    displayWidth.value = 0;
    displayHeight.value = 0;
    naturalWidth = 0;
    naturalHeight = 0;
}

function jpegName(name: string): string {
    const base = name.replace(/\.[^.]+$/, '').trim();

    return `${base === '' ? 'foto' : base}.jpg`;
}

function onPick(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (file && file.lastModified === assignedAt && file.name === outputName.value) {
        return;
    }

    clearPreview();

    if (!file) {
        return;
    }

    outputName.value = jpegName(file.name);
    previewUrl.value = URL.createObjectURL(file);
    settling.value = true;
}

function onImageLoad(): void {
    const image = source.value;

    if (!image || image.naturalWidth === 0 || image.naturalHeight === 0) {
        failRead();

        return;
    }

    naturalWidth = image.naturalWidth;
    naturalHeight = image.naturalHeight;
    focusX.value = naturalWidth / 2;
    focusY.value = naturalHeight / 2;
    zoom.value = 1;
    readError.value = null;

    requestAnimationFrame(() => {
        applyLayout();
        observeFrame();
        void exportCrop();
    });
}

function failRead(): void {
    const input = inputElement();

    if (input) {
        input.value = '';
    }

    clearPreview();
    readError.value = 'No se pudo leer esa imagen. Usa JPG, PNG o WebP.';
}

function observeFrame(): void {
    resizeObserver?.disconnect();

    if (!frame.value) {
        return;
    }

    resizeObserver = new ResizeObserver(() => {
        applyLayout();

        if (!dragging) {
            void exportCrop();
        }
    });
    resizeObserver.observe(frame.value);
}

function applyLayout(): void {
    const width = frame.value?.clientWidth ?? 0;
    const height = frame.value?.clientHeight ?? 0;

    if (width === 0 || height === 0 || naturalWidth === 0 || naturalHeight === 0) {
        return;
    }

    const scale = Math.max(width / naturalWidth, height / naturalHeight) * zoom.value;
    const nextWidth = naturalWidth * scale;
    const nextHeight = naturalHeight * scale;
    const minX = width - nextWidth;
    const minY = height - nextHeight;
    const x = Math.min(0, Math.max(minX, width / 2 - focusX.value * scale));
    const y = Math.min(0, Math.max(minY, height / 2 - focusY.value * scale));

    focusX.value = (width / 2 - x) / scale;
    focusY.value = (height / 2 - y) / scale;
    left.value = x;
    top.value = y;
    displayWidth.value = nextWidth;
    displayHeight.value = nextHeight;
}

function onPointerDown(event: PointerEvent): void {
    if (displayWidth.value === 0 || naturalWidth === 0) {
        return;
    }

    dragging = true;
    settling.value = true;
    dragPointerX = event.clientX;
    dragPointerY = event.clientY;
    dragFocusX = focusX.value;
    dragFocusY = focusY.value;
    dragScale = displayWidth.value / naturalWidth;

    try {
        frame.value?.setPointerCapture(event.pointerId);
    } catch {
        // The drag still follows later pointer events when capture is unavailable.
    }
}

function onPointerMove(event: PointerEvent): void {
    if (!dragging || dragScale === 0) {
        return;
    }

    focusX.value = dragFocusX - (event.clientX - dragPointerX) / dragScale;
    focusY.value = dragFocusY - (event.clientY - dragPointerY) / dragScale;
    applyLayout();
}

function onPointerUp(): void {
    if (!dragging) {
        return;
    }

    dragging = false;
    void exportCrop();
}

function onKeyDown(event: KeyboardEvent): void {
    const step = event.shiftKey ? 48 : 16;
    const moves: Record<string, [number, number]> = {
        ArrowLeft: [step, 0],
        ArrowRight: [-step, 0],
        ArrowUp: [0, step],
        ArrowDown: [0, -step],
    };
    const move = moves[event.key];
    const scale = naturalWidth === 0 ? 0 : displayWidth.value / naturalWidth;

    if (!move || scale === 0) {
        return;
    }

    event.preventDefault();
    settling.value = true;
    focusX.value += move[0] / scale;
    focusY.value += move[1] / scale;
    applyLayout();
}

function onKeyUp(event: KeyboardEvent): void {
    if (event.key.startsWith('Arrow')) {
        void exportCrop();
    }
}

function onZoom(event: Event): void {
    zoom.value = Number((event.target as HTMLInputElement).value);
    settling.value = true;
    applyLayout();
}

function recenter(): void {
    focusX.value = naturalWidth / 2;
    focusY.value = naturalHeight / 2;
    zoom.value = 1;
    settling.value = true;
    applyLayout();
    void exportCrop();
}

async function exportCrop(): Promise<void> {
    const generation = ++exportGeneration;
    const image = source.value;
    const width = frame.value?.clientWidth ?? 0;
    const height = frame.value?.clientHeight ?? 0;

    if (!image || width === 0 || height === 0 || displayWidth.value === 0 || naturalWidth === 0) {
        if (generation === exportGeneration) {
            settling.value = false;
        }

        return;
    }

    const scale = displayWidth.value / naturalWidth;
    const cropX = Math.max(0, -left.value / scale);
    const cropY = Math.max(0, -top.value / scale);
    const cropWidth = Math.min(naturalWidth - cropX, width / scale);
    const cropHeight = Math.min(naturalHeight - cropY, height / scale);

    if (cropWidth < 1 || cropHeight < 1) {
        if (generation === exportGeneration) {
            settling.value = false;
        }

        return;
    }

    const canvasWidth = Math.min(outputWidth, Math.round(cropWidth));
    const canvas = document.createElement('canvas');
    canvas.width = canvasWidth;
    canvas.height = Math.round((canvasWidth * 9) / 16);

    const context = canvas.getContext('2d');

    if (!context) {
        if (generation === exportGeneration) {
            settling.value = false;
        }

        return;
    }

    context.drawImage(image, cropX, cropY, cropWidth, cropHeight, 0, 0, canvas.width, canvas.height);

    const blob = await new Promise<Blob | null>((resolve) => {
        canvas.toBlob(resolve, 'image/jpeg', 0.9);
    });

    if (generation !== exportGeneration) {
        return;
    }

    if (blob === null) {
        settling.value = false;
        readError.value = 'No se pudo preparar el recorte. Intenta con otra imagen.';

        return;
    }

    assignedAt = Date.now();
    const file = new File([blob], outputName.value, { type: 'image/jpeg', lastModified: assignedAt });
    const input = inputElement();

    if (!input) {
        settling.value = false;

        return;
    }

    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
    ready.value = true;
    settling.value = false;
}
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="grid gap-2">
            <Label for="image">Imagen</Label>
            <Input
                id="image"
                ref="fileField"
                type="file"
                name="image"
                accept="image/jpeg,image/png,image/webp"
                required
                @change="onPick"
            />
            <InputError :message="error || readError || undefined" />
        </div>
        <slot />
        <div v-if="previewUrl" class="grid gap-3 md:col-span-2">
            <p class="text-sm text-muted-foreground">Así se verá en la galería. Arrastra la foto para elegir la parte visible.</p>
            <div
                ref="frame"
                class="relative aspect-video w-full touch-none overflow-hidden rounded-3xl bg-[#100e0c] outline-none focus-visible:ring-2 focus-visible:ring-ring"
                tabindex="0"
                role="application"
                aria-label="Recorte de la foto. Arrastra para mover la parte visible."
                @pointerdown="onPointerDown"
                @pointermove="onPointerMove"
                @pointerup="onPointerUp"
                @pointercancel="onPointerUp"
                @keydown="onKeyDown"
                @keyup="onKeyUp"
            >
                <img
                    ref="source"
                    :src="previewUrl"
                    alt=""
                    draggable="false"
                    class="absolute top-0 left-0 max-w-none cursor-grab select-none active:cursor-grabbing"
                    :class="displayWidth === 0 ? 'invisible' : ''"
                    :style="{
                        width: `${displayWidth}px`,
                        height: `${displayHeight}px`,
                        transform: `translate(${left}px, ${top}px)`,
                    }"
                    @load="onImageLoad"
                />
            </div>
            <div class="flex items-center gap-3">
                <Label for="crop-zoom" class="shrink-0">Acercar</Label>
                <input
                    id="crop-zoom"
                    :value="zoom"
                    type="range"
                    min="1"
                    max="3"
                    step="0.01"
                    class="h-2 w-full cursor-pointer accent-primary"
                    @input="onZoom"
                    @change="exportCrop"
                />
                <button type="button" class="shrink-0 text-sm text-muted-foreground underline-offset-4 hover:underline" @click="recenter">
                    Centrar
                </button>
            </div>
        </div>
    </div>
</template>
