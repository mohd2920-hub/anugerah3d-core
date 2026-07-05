@extends('public.layouts.app')

@section('title', 'Anugerah3D | Dari Idea Menjadi Realiti')
@section('description', 'Anugerah3D creates custom 3D printed gifts, event items, corporate gifts and personal products.')

@section('content')
    <section class="relative isolate overflow-hidden bg-zinc-950 text-white">
        <img
            src="{{ asset('images/anugerah3d-hero.png') }}"
            alt="Desktop 3D printer producing custom gift items"
            class="absolute inset-0 h-full w-full object-cover"
        >
        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(15,23,42,0.86)_0%,rgba(15,23,42,0.68)_39%,rgba(15,23,42,0.24)_72%,rgba(15,23,42,0.16)_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#f8faf8] to-transparent"></div>

        <div class="relative mx-auto flex min-h-[84svh] max-w-7xl items-center px-5 pb-20 pt-28 sm:px-8 lg:min-h-[760px]">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-100">Custom 3D Printing Studio</p>
                <h1 class="mt-6 max-w-3xl text-5xl font-semibold leading-[1.02] tracking-normal text-white sm:text-6xl lg:text-7xl">
                    Anugerah3D
                    <span class="mt-3 block text-3xl font-medium text-teal-50 sm:text-4xl lg:text-5xl">Dari Idea Menjadi Realiti</span>
                </h1>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-zinc-100 sm:text-xl">
                    Custom 3D printing products for gifts, business, events and personal use.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="#products" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-white px-6 text-sm font-semibold text-zinc-950 shadow-lg shadow-zinc-950/20 transition hover:-translate-y-0.5 hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-zinc-900">
                        View Products
                    </a>
                    <a href="#contact" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-6 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/18 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-zinc-900">
                        Get Quotation
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="px-5 py-20 sm:px-8 lg:py-28">
        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">About Anugerah3D</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-normal text-zinc-950 sm:text-4xl">Creative custom prints, made personal.</h2>
            </div>
            <div class="grid gap-6 text-base leading-8 text-zinc-600 sm:text-lg">
                <p>
                    Anugerah3D provides custom 3D printed products for customers who want something meaningful, practical and unique. From personal gifts to corporate souvenirs, every item can be shaped around your idea, name, theme or event.
                </p>
                <p>
                    We focus on creativity, quality and customization, helping individuals, small businesses and event teams turn simple ideas into finished printed products.
                </p>
            </div>
        </div>
    </section>

    <section id="products" class="bg-white px-5 py-20 sm:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Product Categories</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-normal text-zinc-950 sm:text-4xl">Simple choices for gifts, events and business needs.</h2>
            </div>

            <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($productCategories as $category)
                    <x-public.product-category-card
                        :accent="$category['accent']"
                        :description="$category['description']"
                        :index="$loop->iteration"
                        :name="$category['name']"
                    />
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 py-20 sm:px-8 lg:py-28">
        <div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Why Choose Us</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-normal text-zinc-950 sm:text-4xl">A friendly print partner for personal and business ideas.</h2>
                <p class="mt-5 text-lg leading-8 text-zinc-600">
                    We keep the process clear and approachable, from idea sharing to finished print.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($reasons as $reason)
                    <x-public.reason-item :reason="$reason" />
                @endforeach
            </div>
        </div>
    </section>

    <section id="process" class="bg-zinc-950 px-5 py-20 text-white sm:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-200">How It Works</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-normal sm:text-4xl">From idea to printed product in four simple steps.</h2>
            </div>

            <div class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($steps as $step)
                    <x-public.process-step-card
                        :description="$step['description']"
                        :step-number="$loop->iteration"
                        :title="$step['title']"
                    />
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white px-5 py-20 sm:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Gallery</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-normal text-zinc-950 sm:text-4xl">Product photo placeholders for future uploads.</h2>
                </div>
                <p class="max-w-sm text-sm leading-6 text-zinc-500">Real customer photos can be added here later without changing the page structure.</p>
            </div>

            <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($galleryItems as $item)
                    <x-public.gallery-card :title="$item" />
                @endforeach
            </div>
        </div>
    </section>

    <section id="contact" class="px-5 py-20 sm:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl overflow-hidden rounded-lg bg-zinc-950 text-white shadow-2xl shadow-zinc-900/15">
            <div class="grid gap-10 p-8 sm:p-10 lg:grid-cols-[1.1fr_0.9fr] lg:p-14">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-200">Contact</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-normal sm:text-4xl">Ready to print your idea?</h2>
                    <p class="mt-5 max-w-2xl text-lg leading-8 text-zinc-300">
                        Send your idea, product reference or event requirement. We will help review the request and prepare a simple quotation.
                    </p>
                </div>

                <div class="flex flex-col justify-center gap-4">
                    <a href="https://wa.me/60000000000" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-teal-300 px-6 text-sm font-semibold text-zinc-950 transition hover:-translate-y-0.5 hover:bg-teal-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:ring-offset-2 focus:ring-offset-zinc-950">
                        WhatsApp Us
                    </a>
                    <a href="mailto:hello@anugerah3d.com?subject=Quotation%20Request" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-white/15 bg-white/10 px-6 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-zinc-950">
                        Request Quotation
                    </a>
                    <p class="text-center text-sm leading-6 text-zinc-400">WhatsApp and quotation links are placeholders until the official contact details are confirmed.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
