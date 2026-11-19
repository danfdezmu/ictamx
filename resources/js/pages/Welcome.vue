<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, type Directive } from 'vue';
import PublicGalleryController from '@/actions/App/Http/Controllers/PublicGalleryController';
import { dashboard, login } from '@/routes';

const props = defineProps<{
    church: string;
    gallery: Array<{
        src: string;
        alt: string | null;
    }>;
}>();

const page = usePage();
const menuOpen = ref(false);
const signedIn = computed(() => Boolean(page.props.auth?.user));

const instagram = 'https://www.instagram.com/ictamexico/';

const navigation = [
    { label: 'Inicio', href: '#inicio' },
    { label: 'Visita', href: '#visita' },
    { label: 'Ministerios', href: '#ministerios' },
    { label: 'Redes', href: '#redes' },
    { label: 'Contacto', href: '#contacto' },
];

const gatherings = [
    { day: 'Domingo', name: 'Culto', detail: '10:00 a.m.' },
    { day: 'Jueves', name: 'Culto', detail: '7:00 p.m.' },
];

const facebook = 'https://www.facebook.com/ictamx/';
const facebookVideo = 'https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fictamx%2Fvideos%2F2670572756711221%2F&show_text=false&width=500';
const mapEmbed = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3773.8567237008215!2d-99.18660982393169!3d18.93773495629707!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85ce755de70ffd09%3A0xf2e2380d8023fba9!2sIglesia%20Cristiana%20Tabern%C3%A1culo%20de%20Adoraci%C3%B3n!5e0!3m2!1ses!2smx!4v1795049728940!5m2!1ses!2smx';
const directions = 'https://www.google.com/maps/dir/?api=1&destination=Iglesia%20Cristiana%20Tabern%C3%A1culo%20de%20Adoraci%C3%B3n%2C%20Cuernavaca';

const ministries = [
    { name: 'Varones', text: 'Un espacio para los hombres de la iglesia.' },
    { name: 'Niños', text: 'Un espacio para los más pequeños mientras la iglesia se reúne.' },
    { name: 'Jóvenes', text: 'Una comunidad para crecer en la fe junto a otros.' },
    { name: 'Femenil', text: 'Un espacio para las mujeres de la iglesia.' },
];

const latestPhotos = computed(() => props.gallery.slice(0, 6));
const slideIndex = ref(0);
let slideTimer: ReturnType<typeof setInterval> | undefined;
let slideStartX = 0;

function closeMenu(): void {
    menuOpen.value = false;
}

function documentTop(element: HTMLElement): number {
    let top = 0;
    let current: HTMLElement | null = element;

    while (current !== null) {
        top += current.offsetTop;
        current = current.offsetParent instanceof HTMLElement ? current.offsetParent : null;
    }

    return top;
}

let scrollFrame = 0;
let glowFrame = 0;

function placeGlows(): void {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll<HTMLElement>('.hero-glow, .section-glow').forEach((glow) => {
        if (reduceMotion || glow.parentElement === null) {
            glow.style.transform = '';

            return;
        }

        const rect = glow.parentElement.getBoundingClientRect();
        const progress = (window.innerHeight / 2 - (rect.top + rect.height / 2)) / window.innerHeight;
        const shift = Math.max(-1, Math.min(1, progress));

        glow.style.transform = `translate(-50%, -50%) translate3d(${(shift * -10).toFixed(2)}%, ${(shift * 14).toFixed(2)}%, 0)`;
    });
}

function onGlowScroll(): void {
    cancelAnimationFrame(glowFrame);
    glowFrame = requestAnimationFrame(placeGlows);
}

function animateScroll(top: number): void {
    cancelAnimationFrame(scrollFrame);

    const start = window.scrollY;
    const distance = top - start;
    const duration = Math.min(900, Math.max(450, Math.abs(distance) * 0.35));
    const started = performance.now();

    const step = (now: number): void => {
        const progress = Math.min(1, (now - started) / duration);
        const eased = 1 - (1 - progress) ** 3;

        window.scrollTo(0, start + distance * eased);

        if (progress < 1) {
            scrollFrame = requestAnimationFrame(step);
        }
    };

    scrollFrame = requestAnimationFrame(step);
}

function scrollToSection(event: MouseEvent): void {
    const link = event.target instanceof Element ? event.target.closest('a') : null;

    if (!(link instanceof HTMLAnchorElement)) {
        return;
    }

    const href = link.getAttribute('href');

    if (href === null || !href.startsWith('#')) {
        return;
    }

    const target = document.getElementById(href.slice(1));

    if (target === null) {
        return;
    }

    event.preventDefault();

    const margin = Number.parseFloat(getComputedStyle(target).scrollMarginTop);
    const top = Math.max(0, documentTop(target) - (Number.isNaN(margin) ? 0 : margin));

    history.pushState(null, '', href);

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        cancelAnimationFrame(scrollFrame);
        window.scrollTo(0, top);

        return;
    }

    animateScroll(top);
}

function showSlide(next: number): void {
    const total = latestPhotos.value.length;

    if (total === 0) {
        return;
    }

    slideIndex.value = (next + total) % total;
}

function nextSlide(): void {
    showSlide(slideIndex.value + 1);
}

function previousSlide(): void {
    showSlide(slideIndex.value - 1);
}

function stopSlideTimer(): void {
    if (slideTimer === undefined) {
        return;
    }

    clearInterval(slideTimer);
    slideTimer = undefined;
}

function startSlideTimer(): void {
    stopSlideTimer();

    if (latestPhotos.value.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    slideTimer = setInterval(nextSlide, 6000);
}

function onSlidePointerDown(event: PointerEvent): void {
    slideStartX = event.clientX;
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function onSlidePointerUp(event: PointerEvent): void {
    const delta = event.clientX - slideStartX;

    if (Math.abs(delta) < 48) {
        return;
    }

    if (delta < 0) {
        nextSlide();
    } else {
        previousSlide();
    }

    startSlideTimer();
}

function onSlideFocusOut(event: FocusEvent): void {
    const next = event.relatedTarget;

    if (next instanceof Node && event.currentTarget instanceof Node && event.currentTarget.contains(next)) {
        return;
    }

    startSlideTimer();
}

function onSlideVisibilityChange(): void {
    if (document.hidden) {
        stopSlideTimer();

        return;
    }

    startSlideTimer();
}

const revealObservers = new WeakMap<HTMLElement, IntersectionObserver>();

const vReveal: Directive<HTMLElement> = {
    mounted(el) {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) {
                    return;
                }

                el.classList.add('is-visible');
                observer.disconnect();
                revealObservers.delete(el);
            },
            { rootMargin: '0px 0px -12% 0px', threshold: 0.01 },
        );

        revealObservers.set(el, observer);
        observer.observe(el);
    },
    unmounted(el) {
        revealObservers.get(el)?.disconnect();
        revealObservers.delete(el);
    },
};

onMounted(() => {
    startSlideTimer();
    placeGlows();
    document.addEventListener('visibilitychange', onSlideVisibilityChange);
    window.addEventListener('scroll', onGlowScroll, { passive: true });
    window.addEventListener('resize', onGlowScroll);
});

onUnmounted(() => {
    stopSlideTimer();
    cancelAnimationFrame(glowFrame);
    document.removeEventListener('visibilitychange', onSlideVisibilityChange);
    window.removeEventListener('scroll', onGlowScroll);
    window.removeEventListener('resize', onGlowScroll);
});
</script>

<template>
    <Head />

    <div class="min-h-screen bg-[#100e0c] text-[#f4efe6]" @click="scrollToSection">
        <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#100e0c]">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-5">
                <a href="#inicio" class="flex items-center">
                    <img src="/logo/icta_wh.png" alt="ICTA" class="h-11 w-auto" />
                </a>
                <nav class="hidden items-center gap-6 text-sm text-[#f4efe6]/75 md:flex">
                    <a v-for="item in navigation" :key="item.href" :href="item.href" class="hover:text-[#f4efe6]">
                        {{ item.label }}
                    </a>
                    <Link :href="PublicGalleryController.index()" class="hover:text-[#f4efe6]">Galería</Link>
                </nav>
                <div class="hidden md:block">
                    <a
                        href="#visita"
                        class="rounded-full bg-[#f4efe6] px-4 py-2 text-sm font-medium text-[#100e0c]"
                    >
                        Planifica tu visita
                    </a>
                </div>
                <button
                    type="button"
                    class="text-sm md:hidden"
                    :aria-expanded="menuOpen"
                    @click="menuOpen = !menuOpen"
                >
                    {{ menuOpen ? 'Cerrar' : 'Menú' }}
                </button>
            </div>
            <div v-if="menuOpen" class="border-t border-white/10 px-5 py-4 md:hidden">
                <nav class="flex flex-col gap-3 text-sm">
                    <a v-for="item in navigation" :key="item.href" :href="item.href" @click="closeMenu">
                        {{ item.label }}
                    </a>
                    <Link :href="PublicGalleryController.index()" @click="closeMenu">Galería</Link>
                    <a href="#visita" @click="closeMenu">Planifica tu visita</a>
                </nav>
            </div>
        </header>

        <main id="inicio">
            <section class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 pt-24 pb-16">
                <img
                    src="/logo/icta_bw.png"
                    alt=""
                    class="pointer-events-none absolute top-1/2 left-1/2 h-auto w-[min(150vw,64rem)] -translate-x-1/2 -translate-y-1/2 opacity-[0.12]"
                />
                <div aria-hidden="true" class="hero-glow" />
                <div class="relative mx-auto flex w-full max-w-5xl flex-col items-center text-center">
                    <img src="/logo/icta_wh.png" alt="" class="h-40 w-auto sm:h-52 md:h-60" />
                    <h1 class="mt-8 font-serif leading-none tracking-tight">
                        <span class="block text-xs tracking-[0.35em] text-[#d4a574] uppercase sm:text-sm">
                            Iglesia Cristiana
                        </span>
                        <span class="mt-4 block text-4xl sm:text-5xl lg:text-6xl">Tabernáculo de Adoración</span>
                    </h1>
                    <p class="mt-8 max-w-4xl font-serif text-2xl text-[#d4a574] italic sm:text-4xl">
                        Donde la presencia de Dios es una delicia
                    </p>
                    <p class="mt-4 text-sm tracking-[0.22em] text-[#f4efe6]/70 uppercase">Cuernavaca, Morelos</p>
                    <div class="mt-10 flex flex-wrap items-center justify-center gap-5">
                        <a
                            href="#visita"
                            class="rounded-full bg-[#d4a574] px-4 py-2 text-xs font-medium text-[#100e0c]"
                        >
                            Planifica tu visita
                        </a>
                        <a href="#ministerios" class="text-xs text-[#f4efe6]/75 underline decoration-white/30 underline-offset-4">
                            Conocer la iglesia
                        </a>
                    </div>
                </div>
            </section>

            <section id="visita" v-reveal class="reveal relative mx-auto max-w-6xl scroll-mt-24 overflow-hidden px-5 py-24">
                <div aria-hidden="true" class="section-glow" />
                <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:items-stretch">
                    <div class="flex flex-col">
                        <p class="text-sm tracking-[0.22em] text-[#d4a574] uppercase">Visita</p>
                        <h2 class="mt-3 font-serif text-4xl sm:text-5xl">Te esperamos</h2>
                        <p class="mt-4 text-sm leading-6 text-[#f4efe6]/65">
                            Estamos en Cuernavaca, Morelos. Llega unos minutos antes. Alguien en la entrada te recibe si es tu primera vez.
                        </p>
                        <a
                            :href="directions"
                            target="_blank"
                            rel="noopener"
                            class="mt-6 inline-flex w-fit rounded-full bg-[#d4a574] px-4 py-2 text-xs font-medium text-[#100e0c]"
                        >
                            Cómo llegar
                        </a>
                        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:mt-auto lg:grid-cols-1 lg:pt-8">
                            <article
                                v-for="gathering in gatherings"
                                :key="gathering.day"
                                class="rounded-3xl border border-white/10 bg-white/5 p-6"
                            >
                                <p class="text-sm tracking-[0.18em] text-[#d4a574] uppercase">{{ gathering.day }}</p>
                                <h3 class="mt-4 font-serif text-2xl">{{ gathering.name }}</h3>
                                <p class="mt-3 text-sm text-[#f4efe6]/65">{{ gathering.detail }}</p>
                            </article>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-3xl border border-white/10">
                        <iframe
                            :src="mapEmbed"
                            title="Mapa de Iglesia Cristiana Tabernáculo de Adoración"
                            class="aspect-[4/3] h-full min-h-80 w-full border-0 lg:min-h-full"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen
                        />
                    </div>
                </div>
            </section>

            <section id="ministerios" v-reveal class="reveal relative scroll-mt-24 overflow-hidden border-y border-white/10 bg-[#171410]">
                <div aria-hidden="true" class="section-glow" />
                <div class="mx-auto max-w-6xl px-5 py-24">
                    <p class="text-sm tracking-[0.22em] text-[#d4a574] uppercase">Ministerios</p>
                    <h2 class="mt-3 max-w-2xl font-serif text-4xl sm:text-5xl">Formas de participar</h2>
                    <div class="mt-12 grid gap-px overflow-hidden rounded-3xl border border-white/10 bg-white/10 sm:grid-cols-2">
                        <article v-for="ministry in ministries" :key="ministry.name" class="bg-[#171410] p-8">
                            <h3 class="font-serif text-3xl">{{ ministry.name }}</h3>
                            <p class="mt-3 max-w-sm text-sm leading-6 text-[#f4efe6]/65">{{ ministry.text }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section v-reveal class="reveal relative mx-auto max-w-6xl overflow-hidden px-5 py-28">
                <div aria-hidden="true" class="section-glow" />
                <img
                    src="/logo/icta_bw.png"
                    alt=""
                    class="pointer-events-none absolute top-1/2 right-0 h-56 -translate-y-1/2 opacity-25 sm:h-72"
                />
                <p class="relative max-w-3xl font-serif text-4xl leading-tight sm:text-6xl">
                    Ven como estás. Aquí hay un lugar para ti.
                </p>
            </section>

            <section v-if="latestPhotos.length > 0" v-reveal class="reveal relative overflow-hidden border-t border-white/10">
                <div aria-hidden="true" class="section-glow" />
                <div class="mx-auto max-w-6xl px-5 py-16">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <p class="text-sm tracking-[0.22em] text-[#d4a574] uppercase">Galería</p>
                        <Link
                            :href="PublicGalleryController.index()"
                            class="text-sm text-[#f4efe6]/75 underline decoration-white/30 underline-offset-4"
                        >
                            Ver la galería
                        </Link>
                    </div>
                    <div
                        class="relative mx-auto mt-8 max-w-4xl"
                        role="region"
                        aria-roledescription="carrusel"
                        aria-label="Galería"
                        @mouseenter="stopSlideTimer"
                        @mouseleave="startSlideTimer"
                        @focusin="stopSlideTimer"
                        @focusout="onSlideFocusOut"
                    >
                        <div class="overflow-hidden rounded-3xl touch-pan-y" @pointerdown="onSlidePointerDown" @pointerup="onSlidePointerUp">
                            <div
                                class="flex motion-safe:transition-transform motion-safe:duration-700 motion-safe:ease-out"
                                :style="{ transform: `translateX(-${slideIndex * 100}%)` }"
                            >
                                <img
                                    v-for="(photo, photoIndex) in latestPhotos"
                                    :key="photo.src"
                                    :src="photo.src"
                                    :alt="photo.alt || ''"
                                    :aria-hidden="photoIndex !== slideIndex"
                                    class="aspect-video w-full shrink-0 object-cover"
                                    draggable="false"
                                />
                            </div>
                        </div>
                        <template v-if="latestPhotos.length > 1">
                            <button
                                type="button"
                                class="absolute top-1/2 left-3 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-[#100e0c]/70 text-[#f4efe6]"
                                aria-label="Foto anterior"
                                @click="previousSlide"
                            >
                                <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path d="M12 4 L6 10 L12 16" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="absolute top-1/2 right-3 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-[#100e0c]/70 text-[#f4efe6]"
                                aria-label="Foto siguiente"
                                @click="nextSlide"
                            >
                                <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path d="M8 4 L14 10 L8 16" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div class="mt-4 flex justify-center gap-2">
                                <button
                                    v-for="(photo, photoIndex) in latestPhotos"
                                    :key="photo.src"
                                    type="button"
                                    class="h-1.5 rounded-full motion-safe:transition-[width,background-color] motion-safe:duration-300"
                                    :class="photoIndex === slideIndex ? 'w-6 bg-[#d4a574]' : 'w-1.5 bg-white/30'"
                                    :aria-label="`Foto ${photoIndex + 1}`"
                                    :aria-current="photoIndex === slideIndex ? 'true' : undefined"
                                    @click="showSlide(photoIndex)"
                                />
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <section id="redes" v-reveal class="reveal relative scroll-mt-24 overflow-hidden border-t border-white/10">
                <div aria-hidden="true" class="section-glow" />
                <div class="mx-auto max-w-6xl px-5 py-16">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-sm tracking-[0.22em] text-[#d4a574] uppercase">Redes</p>
                            <h2 class="mt-3 font-serif text-4xl sm:text-5xl">@ictamexico</h2>
                        </div>
                        <div class="flex gap-4 text-sm text-[#f4efe6]/75">
                            <a :href="facebook" target="_blank" rel="noopener" class="underline decoration-white/30 underline-offset-4">
                                Facebook
                            </a>
                            <a :href="instagram" target="_blank" rel="noopener" class="underline decoration-white/30 underline-offset-4">
                                Instagram
                            </a>
                        </div>
                    </div>
                    <iframe
                        :src="facebookVideo"
                        title="Video de Facebook"
                        scrolling="no"
                        class="mx-auto mt-8 aspect-video h-auto w-full max-w-2xl rounded-2xl bg-[#100e0c]"
                        allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                        allowfullscreen
                    />
                </div>
            </section>

            <section id="contacto" v-reveal class="reveal relative scroll-mt-24 overflow-hidden border-t border-white/10">
                <div aria-hidden="true" class="section-glow" />
                <div class="mx-auto grid max-w-6xl gap-12 px-5 py-24 md:grid-cols-2">
                    <div>
                        <p class="text-sm tracking-[0.22em] text-[#d4a574] uppercase">Contacto</p>
                        <h2 class="mt-3 font-serif text-4xl sm:text-5xl">{{ props.church }}</h2>
                    </div>
                    <div class="flex flex-col justify-end gap-3 text-[#f4efe6]/75">
                        <p class="font-serif text-3xl text-[#f4efe6]">Cuernavaca, Morelos</p>
                        <p>Domingo 10:00 a.m. · Jueves 7:00 p.m.</p>
                        <a :href="instagram" target="_blank" rel="noopener" class="underline decoration-white/30 underline-offset-4">
                            @ictamexico
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/10">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-8 text-sm text-[#f4efe6]/55">
                <p class="flex items-center gap-3">
                    <img src="/logo/icta_wh.png" alt="" class="h-8 w-auto" />
                    <span>{{ props.church }}</span>
                </p>
                <Link v-if="signedIn" :href="dashboard()" class="hover:text-[#f4efe6]">Panel</Link>
                <Link v-else :href="login()" class="hover:text-[#f4efe6]">Acceso del equipo</Link>
            </div>
        </footer>
    </div>
</template>
