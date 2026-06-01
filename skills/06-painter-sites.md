# Skill 06: Painter Sites & Gallery

## Overview
Build public painter profiles, galleries with masonry layout, and painter dashboard for managing paintings, videos, and projects.

---

## Step 1: Painter Controller

### `app/Http/Controllers/Api/PainterController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PainterProfile;
use Illuminate\Http\Request;

class PainterController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'painter')
            ->with('painterProfile')
            ->withCount('paintings')
            ->withCount('videos');

        if ($request->featured) {
            $query->whereHas('painterProfile', fn($q) => $q->where('is_featured', true));
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('bio', 'like', "%{$request->search}%");
            });
        }

        if ($request->specialty) {
            $query->whereHas('painterProfile', function ($q) use ($request) {
                $q->whereJsonContains('specialties', $request->specialty);
            });
        }

        $painters = $query->orderBy('name')
            ->paginate($request->get('limit', 12));

        return response()->json($painters);
    }

    public function show($slug)
    {
        $painter = User::where('slug', $slug)
            ->where('role', 'painter')
            ->with('painterProfile')
            ->withCount('paintings')
            ->withCount('videos')
            ->firstOrFail();

        return response()->json(['painter' => $painter]);
    }

    public function paintings(Request $request, $slug)
    {
        $painter = User::where('slug', $slug)->where('role', 'painter')->firstOrFail();

        $paintings = $painter->paintings()
            ->with('images')
            ->when($request->style, fn($q) => $q->where('style', $request->style))
            ->when($request->medium, fn($q) => $q->where('medium', $request->medium))
            ->when($request->available, fn($q) => $q->where('is_available', true))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 12));

        return response()->json($paintings);
    }

    public function videos(Request $request, $slug)
    {
        $painter = User::where('slug', $slug)->where('role', 'painter')->firstOrFail();

        $videos = $painter->videos()
            ->where('is_published', true)
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 12));

        return response()->json($videos);
    }

    public function stripeOnboard(Request $request)
    {
        $user = $request->user();

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $account = $stripe->accounts->create([
            'type' => 'express',
            'email' => $user->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
        ]);

        $user->update(['stripe_account_id' => $account->id]);

        $accountLink = $stripe->accountLinks->create([
            'account' => $account->id,
            'refresh_url' => route('stripe.refresh'),
            'return_url' => route('stripe.return'),
            'type' => 'account_onboarding',
        ]);

        return response()->json(['url' => $accountLink->url]);
    }

    public function stripeStatus(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json(['connected' => false]);
        }

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $account = $stripe->accounts->retrieve($user->stripe_account_id);

        return response()->json([
            'connected' => $account->charges_enabled && $account->payouts_enabled,
            'details_submitted' => $account->details_submitted,
        ]);
    }

    public function stripeRefresh(Request $request)
    {
        return $this->stripeOnboard($request);
    }
}
```

---

## Step 2: Painting Controller

### `app/Http/Controllers/Api/PaintingController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Painting;
use App\Models\PaintingImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaintingController extends Controller
{
    public function index(Request $request)
    {
        $paintings = Painting::with(['images', 'user'])
            ->where('is_available', true)
            ->when($request->style, fn($q) => $q->where('style', $request->style))
            ->when($request->medium, fn($q) => $q->where('medium', $request->medium))
            ->when($request->min_price, fn($q) => $q->where('price', '>=', $request->min_price))
            ->when($request->max_price, fn($q) => $q->where('price', '<=', $request->max_price))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 12));

        return response()->json($paintings);
    }

    public function show($id)
    {
        $painting = Painting::with(['images', 'user.painterProfile'])
            ->findOrFail($id);

        return response()->json($painting);
    }

    public function dashboardIndex(Request $request)
    {
        $paintings = Painting::where('user_id', $request->user()->id)
            ->with('images')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($paintings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'medium' => 'nullable|string',
            'style' => 'nullable|string',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'year_created' => 'nullable|integer|min:1900|max:' . date('Y'),
            'tags' => 'nullable|array',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|max:10240',
        ]);

        $painting = Painting::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'medium' => $validated['medium'] ?? null,
            'style' => $validated['style'] ?? null,
            'width' => $validated['width'] ?? null,
            'height' => $validated['height'] ?? null,
            'depth' => $validated['depth'] ?? null,
            'price' => $validated['price'] ?? null,
            'currency' => $validated['currency'] ?? 'USD',
            'year_created' => $validated['year_created'] ?? null,
            'tags' => $validated['tags'] ?? null,
        ]);

        // Handle image uploads
        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('paintings', 'public');
            $thumbnailPath = $this->createThumbnail($image);

            PaintingImage::create([
                'painting_id' => $painting->id,
                'image_url' => $path,
                'thumbnail_url' => $thumbnailPath,
                'sort_order' => $index,
                'is_primary' => $index === 0,
            ]);
        }

        return response()->json([
            'painting' => $painting->load('images'),
            'message' => 'Painting created successfully',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $painting = Painting::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'medium' => 'nullable|string',
            'style' => 'nullable|string',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'year_created' => 'nullable|integer',
            'tags' => 'nullable|array',
            'is_available' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        $painting->update($validated);

        // Handle new images if uploaded
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('paintings', 'public');
                $thumbnailPath = $this->createThumbnail($image);

                PaintingImage::create([
                    'painting_id' => $painting->id,
                    'image_url' => $path,
                    'thumbnail_url' => $thumbnailPath,
                    'sort_order' => $painting->images()->count() + $index,
                    'is_primary' => false,
                ]);
            }
        }

        return response()->json([
            'painting' => $painting->load('images'),
            'message' => 'Painting updated successfully',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $painting = Painting::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Delete images from storage
        foreach ($painting->images as $image) {
            Storage::disk('public')->delete($image->image_url);
            if ($image->thumbnail_url) {
                Storage::disk('public')->delete($image->thumbnail_url);
            }
        }

        $painting->delete();

        return response()->json(['message' => 'Painting deleted successfully']);
    }

    private function createThumbnail($image, $maxDimension = 400): string
    {
        // Using Intervention Image for thumbnail generation
        $img = \Intervention\Image\Facades\Image::make($image);
        $img->fit($maxDimension, $maxDimension, function ($constraint) {
            $constraint->upsize();
        });

        $thumbnailName = 'thumb_' . $image->hashName();
        $thumbnailPath = "paintings/thumbnails/{$thumbnailName}";

        \Intervention\Image\Facades\Image::make($img)
            ->save(storage_path("app/public/{$thumbnailPath}"));

        return $thumbnailPath;
    }
}
```

---

## Step 3: Public Painter Profile Page

### `pages/painter/[slug]/index.vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <!-- Profile Header -->
    <div class="bg-white border-b border-canvas-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-8">
          <!-- Avatar -->
          <div class="w-32 h-32 rounded-2xl overflow-hidden bg-canvas-200 shadow-lg flex-shrink-0">
            <img
              :src="painter.avatar || '/images/default-avatar.jpg'"
              :alt="painter.name"
              class="w-full h-full object-cover"
            />
          </div>

          <!-- Info -->
          <div class="flex-1">
            <h1 class="text-3xl font-serif font-bold text-canvas-900">
              {{ painter.name }}
            </h1>
            <p v-if="painter.painter_profile?.location" class="mt-1 text-canvas-500 flex items-center gap-2">
              <Icon name="mdi:map-marker" class="w-4 h-4" />
              {{ painter.painter_profile.location }}
            </p>
            <p v-if="painter.bio" class="mt-3 text-canvas-600 max-w-2xl leading-relaxed">
              {{ painter.bio }}
            </p>

            <!-- Stats -->
            <div class="mt-4 flex gap-6">
              <div class="text-center">
                <p class="text-2xl font-bold text-canvas-900">{{ painter.paintings_count }}</p>
                <p class="text-sm text-canvas-500">Paintings</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-canvas-900">{{ painter.videos_count }}</p>
                <p class="text-sm text-canvas-500">Videos</p>
              </div>
            </div>

            <!-- Specialties -->
            <div v-if="painter.painter_profile?.specialties" class="mt-4 flex flex-wrap gap-2">
              <span
                v-for="specialty in painter.painter_profile.specialties"
                :key="specialty"
                class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm font-medium"
              >
                {{ specialty }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white border-b border-canvas-200 sticky top-16 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex gap-8">
          <NuxtLink
            :to="`/painter/${slug}`"
            :class="[
              'py-4 text-sm font-medium border-b-2 transition',
              activeTab === 'gallery'
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-canvas-500 hover:text-canvas-700'
            ]"
          >
            Gallery
          </NuxtLink>
          <NuxtLink
            :to="`/painter/${slug}/videos`"
            :class="[
              'py-4 text-sm font-medium border-b-2 transition',
              activeTab === 'videos'
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-canvas-500 hover:text-canvas-700'
            ]"
          >
            Videos
          </NuxtLink>
          <NuxtLink
            :to="`/painter/${slug}/projects`"
            :class="[
              'py-4 text-sm font-medium border-b-2 transition',
              activeTab === 'projects'
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-canvas-500 hover:text-canvas-700'
            ]"
          >
            Projects
          </NuxtLink>
        </nav>
      </div>
    </div>

    <!-- Gallery Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Filters -->
      <div class="flex flex-wrap items-center gap-4 mb-8">
        <select
          v-model="filters.style"
          @change="fetchPaintings"
          class="px-4 py-2 border border-canvas-300 rounded-lg text-sm"
        >
          <option value="">All Styles</option>
          <option value="portrait">Portrait</option>
          <option value="landscape">Landscape</option>
          <option value="still-life">Still Life</option>
          <option value="abstract">Abstract</option>
          <option value="impressionism">Impressionism</option>
        </select>

        <select
          v-model="filters.medium"
          @change="fetchPaintings"
          class="px-4 py-2 border border-canvas-300 rounded-lg text-sm"
        >
          <option value="">All Mediums</option>
          <option value="oil">Oil</option>
          <option value="acrylic">Acrylic</option>
          <option value="watercolor">Watercolor</option>
          <option value="mixed">Mixed Media</option>
        </select>

        <label class="flex items-center gap-2 text-sm text-canvas-600">
          <input
            v-model="filters.available"
            @change="fetchPaintings"
            type="checkbox"
            class="rounded border-canvas-300"
          />
          Available for sale only
        </label>
      </div>

      <!-- Masonry Grid -->
      <div v-if="paintings.length" class="columns-1 sm:columns-2 lg:columns-3 gap-6">
        <div
          v-for="painting in paintings"
          :key="painting.id"
          class="break-inside-avoid mb-6"
        >
          <div
            class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition group cursor-pointer"
            @click="openLightbox(painting)"
          >
            <div class="relative overflow-hidden">
              <img
                :src="painting.images[0]?.thumbnail_url || painting.images[0]?.image_url"
                :alt="painting.title"
                class="w-full object-cover group-hover:scale-105 transition duration-500"
                loading="lazy"
              />
              <div v-if="painting.is_available && painting.price" class="absolute bottom-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full">
                <span class="font-semibold text-canvas-900">${{ painting.price }}</span>
              </div>
            </div>
            <div class="p-4">
              <h3 class="font-semibold text-canvas-900">{{ painting.title }}</h3>
              <p class="text-sm text-canvas-500 mt-1">
                {{ painting.medium }} · {{ painting.year_created }}
              </p>
              <p v-if="painting.dimensions" class="text-xs text-canvas-400 mt-1">
                {{ painting.dimensions }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16">
        <Icon name="mdi:image-off" class="w-16 h-16 text-canvas-300 mx-auto" />
        <p class="mt-4 text-canvas-500">No paintings found matching your filters.</p>
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="text-center mt-8">
        <button
          @click="loadMore"
          :disabled="loading"
          class="px-8 py-3 border border-canvas-300 rounded-lg font-medium text-canvas-700 hover:border-primary-400 transition disabled:opacity-50"
        >
          {{ loading ? 'Loading...' : 'Load More' }}
        </button>
      </div>
    </div>

    <!-- Lightbox -->
    <GalleryPaintingLightbox
      v-if="selectedPainting"
      :painting="selectedPainting"
      @close="selectedPainting = null"
    />
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()

const slug = computed(() => route.params.slug as string)

// Fetch painter profile
const { data: painterData } = await useFetch<{ painter: any }>(
  `${config.public.apiUrl}/painters/${slug.value}`
)

const painter = computed(() => painterData.value?.painter || {})

// Fetch paintings
const paintings = ref<any[]>([])
const page = ref(1)
const hasMore = ref(true)
const loading = ref(false)

const filters = reactive({
  style: '',
  medium: '',
  available: false,
})

const fetchPaintings = async (reset = true) => {
  if (reset) {
    page.value = 1
    paintings.value = []
  }

  loading.value = true
  try {
    const params = new URLSearchParams()
    params.append('page', page.value.toString())
    if (filters.style) params.append('style', filters.style)
    if (filters.medium) params.append('medium', filters.medium)
    if (filters.available) params.append('available', '1')

    const data = await $fetch<any>(
      `${config.public.apiUrl}/painters/${slug.value}/paintings?${params}`
    )

    if (reset) {
      paintings.value = data.data
    } else {
      paintings.value.push(...data.data)
    }

    hasMore.value = data.next_page_url !== null
  } finally {
    loading.value = false
  }
}

const loadMore = () => {
  page.value++
  fetchPaintings(false)
}

await fetchPaintings()

// Lightbox
const selectedPainting = ref<any>(null)

const openLightbox = (painting: any) => {
  selectedPainting.value = painting
}

// Tab state
const activeTab = computed(() => {
  if (route.path.includes('/videos')) return 'videos'
  if (route.path.includes('/projects')) return 'projects'
  return 'gallery'
})
</script>
```

---

## Step 4: Painter Dashboard

### `pages/dashboard/index.vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-serif font-bold text-canvas-900">Dashboard</h1>
          <p class="mt-1 text-canvas-600">Welcome back, {{ user?.name }}</p>
        </div>
        <NuxtLink
          to="/dashboard/paintings/create"
          class="flex items-center gap-2 px-5 py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition"
        >
          <Icon name="mdi:plus" class="w-5 h-5" />
          Add Painting
        </NuxtLink>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
              <Icon name="mdi:image-multiple" class="w-6 h-6 text-primary-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-canvas-900">{{ stats.paintings }}</p>
              <p class="text-sm text-canvas-500">Paintings</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
              <Icon name="mdi:video" class="w-6 h-6 text-primary-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-canvas-900">{{ stats.videos }}</p>
              <p class="text-sm text-canvas-500">Videos</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
              <Icon name="mdi:currency-usd" class="w-6 h-6 text-primary-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-canvas-900">${{ stats.revenue }}</p>
              <p class="text-sm text-canvas-500">Revenue</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
              <Icon name="mdi:cart" class="w-6 h-6 text-primary-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-canvas-900">{{ stats.orders }}</p>
              <p class="text-sm text-canvas-500">Orders</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <NuxtLink
          to="/dashboard/paintings"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition group"
        >
          <Icon name="mdi:image" class="w-8 h-8 text-primary-600 mb-3" />
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">Manage Paintings</h3>
          <p class="text-sm text-canvas-500 mt-1">Upload and organize your artwork</p>
        </NuxtLink>

        <NuxtLink
          to="/dashboard/videos"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition group"
        >
          <Icon name="mdi:video-outline" class="w-8 h-8 text-primary-600 mb-3" />
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">Manage Videos</h3>
          <p class="text-sm text-canvas-500 mt-1">Upload tutorials and techniques</p>
        </NuxtLink>

        <NuxtLink
          to="/dashboard/projects"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition group"
        >
          <Icon name="mdi:hammer-wrench" class="w-8 h-8 text-primary-600 mb-3" />
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">Manage Projects</h3>
          <p class="text-sm text-canvas-500 mt-1">Track works in progress</p>
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'painter' })

const { user } = useAuth()
const config = useRuntimeConfig()

const { data: statsData } = await useFetch<any>(
  `${config.public.apiUrl}/dashboard/stats`,
  { withCredentials: true }
)

const stats = computed(() => statsData.value || {
  paintings: 0,
  videos: 0,
  revenue: '0.00',
  orders: 0,
})
</script>
```

---

## Step 5: Painting Form Component

### `pages/dashboard/paintings/create.vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-8">Add New Painting</h1>

      <form @submit.prevent="submitPainting" class="space-y-8">
        <!-- Image Upload -->
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Images</h2>
          <div
            class="border-2 border-dashed border-canvas-300 rounded-xl p-8 text-center hover:border-primary-400 transition"
            @dragover.prevent
            @drop.prevent="handleDrop"
          >
            <input
              ref="fileInput"
              type="file"
              accept="image/*"
              multiple
              class="hidden"
              @change="handleFiles"
            />
            <Icon name="mdi:cloud-upload" class="w-12 h-12 text-canvas-400 mx-auto mb-3" />
            <p class="text-canvas-600">
              Drop images here or
              <button type="button" @click="$refs.fileInput.click()" class="text-primary-600 font-medium">
                browse
              </button>
            </p>
          </div>

          <!-- Preview Grid -->
          <div v-if="previews.length" class="mt-4 grid grid-cols-4 gap-3">
            <div v-for="(preview, index) in previews" :key="index" class="relative aspect-square rounded-lg overflow-hidden">
              <img :src="preview" class="w-full h-full object-cover" />
              <button
                type="button"
                @click="removeImage(index)"
                class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs"
              >
                ×
              </button>
            </div>
          </div>
        </div>

        <!-- Details -->
        <div class="bg-white rounded-2xl p-6 shadow-sm space-y-6">
          <h2 class="text-lg font-semibold text-canvas-900">Details</h2>

          <div>
            <label class="block text-sm font-medium text-canvas-700 mb-2">Title *</label>
            <input v-model="form.title" type="text" required class="input" placeholder="Painting title" />
          </div>

          <div>
            <label class="block text-sm font-medium text-canvas-700 mb-2">Description</label>
            <textarea v-model="form.description" rows="3" class="input" placeholder="About this painting..." />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-2">Medium</label>
              <select v-model="form.medium" class="input">
                <option value="">Select medium</option>
                <option value="oil">Oil</option>
                <option value="acrylic">Acrylic</option>
                <option value="watercolor">Watercolor</option>
                <option value="mixed">Mixed Media</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-2">Style</label>
              <select v-model="form.style" class="input">
                <option value="">Select style</option>
                <option value="portrait">Portrait</option>
                <option value="landscape">Landscape</option>
                <option value="still-life">Still Life</option>
                <option value="abstract">Abstract</option>
                <option value="impressionism">Impressionism</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-2">Width (cm)</label>
              <input v-model="form.width" type="number" step="0.1" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-2">Height (cm)</label>
              <input v-model="form.height" type="number" step="0.1" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-2">Year</label>
              <input v-model="form.year_created" type="number" class="input" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-2">Price</label>
              <input v-model="form.price" type="number" step="0.01" class="input" placeholder="0.00" />
            </div>
            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-2">Currency</label>
              <select v-model="form.currency" class="input">
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="GBP">GBP</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <div class="flex gap-4">
          <button
            type="submit"
            :disabled="submitting"
            class="flex-1 py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition disabled:opacity-50"
          >
            {{ submitting ? 'Saving...' : 'Save Painting' }}
          </button>
          <NuxtLink
            to="/dashboard/paintings"
            class="px-8 py-3 border border-canvas-300 rounded-lg font-medium text-canvas-700 hover:border-primary-400 transition"
          >
            Cancel
          </NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'painter', layout: 'dashboard' })

const config = useRuntimeConfig()
const router = useRouter()

const fileInput = ref<HTMLInputElement>()
const files = ref<File[]>([])
const previews = ref<string[]>([])
const submitting = ref(false)

const form = reactive({
  title: '',
  description: '',
  medium: '',
  style: '',
  width: '',
  height: '',
  year_created: '',
  price: '',
  currency: 'USD',
})

const handleFiles = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files) {
    addFiles(Array.from(input.files))
  }
}

const handleDrop = (event: DragEvent) => {
  if (event.dataTransfer?.files) {
    addFiles(Array.from(event.dataTransfer.files))
  }
}

const addFiles = (newFiles: File[]) => {
  for (const file of newFiles.slice(0, 10 - files.value.length)) {
    files.value.push(file)
    const reader = new FileReader()
    reader.onload = (e) => previews.value.push(e.target?.result as string)
    reader.readAsDataURL(file)
  }
}

const removeImage = (index: number) => {
  files.value.splice(index, 1)
  previews.value.splice(index, 1)
}

const submitPainting = async () => {
  submitting.value = true
  try {
    const formData = new FormData()
    Object.entries(form).forEach(([key, value]) => {
      if (value) formData.append(key, value)
    })
    files.value.forEach((file) => formData.append('images[]', file))

    await $fetch(`${config.public.apiUrl}/dashboard/paintings`, {
      method: 'POST',
      body: formData,
      withCredentials: true,
    })

    router.push('/dashboard/paintings')
  } catch (error) {
    console.error('Failed to save painting:', error)
  } finally {
    submitting.value = false
  }
}
</script>
```

---

## Verification Checklist

- [ ] Painter profile displays correctly
- [ ] Gallery loads with masonry layout
- [ ] Filters work (style, medium, availability)
- [ ] Lightbox opens on painting click
- [ ] Dashboard stats display
- [ ] Painting creation form works
- [ ] Image upload with preview works
- [ ] Painter can edit/delete paintings

---

## Next Steps

Proceed to [Skill 07: Video Shop](./07-video-shop.md) for video upload and streaming.
