# Skill 07: Video Shop

## Overview
Build video upload, HLS streaming, purchase flow, and my library page for purchased videos.

---

## Step 1: Video Controller

### `app/Http/Controllers/Api/VideoController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $videos = Video::with('user')
            ->where('is_published', true)
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->free, fn($q) => $q->where('is_free', true))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 12));

        return response()->json($videos);
    }

    public function show($id)
    {
        $video = Video::with(['user.painterProfile', 'user'])
            ->findOrFail($id);

        // Increment view count
        $video->increment('view_count');

        return response()->json($video);
    }

    public function dashboardIndex(Request $request)
    {
        $videos = Video::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($videos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'video' => 'required|file|mimes:mp4,webm,avi|max:512000',
            'thumbnail' => 'nullable|image|max:5120',
            'category' => 'required|in:technique,demo,tutorial,timelapse',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'is_free' => 'sometimes|boolean',
            'tags' => 'nullable|array',
        ]);

        // Store video file
        $videoPath = $request->file('video')->store('videos', 'public');

        // Generate thumbnail
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('videos/thumbnails', 'public');
        }

        // TODO: Process video with FFmpeg for HLS streaming
        $hlsUrl = null; // Will be set after processing

        $video = Video::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'video_url' => $videoPath,
            'thumbnail_url' => $thumbnailPath,
            'hls_url' => $hlsUrl,
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'USD',
            'category' => $validated['category'],
            'is_free' => $validated['is_free'] ?? false,
            'tags' => $validated['tags'] ?? null,
        ]);

        // TODO: Dispatch job to process video
        // ProcessVideoThumbnail::dispatch($video);

        return response()->json([
            'video' => $video,
            'message' => 'Video uploaded successfully',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $video = Video::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category' => 'sometimes|in:technique,demo,tutorial,timelapse',
            'price' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'is_free' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'tags' => 'nullable|array',
        ]);

        $video->update($validated);

        return response()->json([
            'video' => $video,
            'message' => 'Video updated successfully',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $video = Video::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Delete files from storage
        Storage::disk('public')->delete($video->video_url);
        if ($video->thumbnail_url) {
            Storage::disk('public')->delete($video->thumbnail_url);
        }

        $video->delete();

        return response()->json(['message' => 'Video deleted successfully']);
    }

    public function stream($id, Request $request)
    {
        $video = Video::findOrFail($id);

        // Check if user has purchased
        $hasPurchased = Purchase::where('user_id', $request->user()->id)
            ->where('purchasable_type', Video::class)
            ->where('purchasable_id', $video->id)
            ->exists();

        if (!$hasPurchased && !$video->is_free) {
            return response()->json(['message' => 'Purchase required'], 403);
        }

        // Return streaming URL
        $streamUrl = Storage::disk('s3')->temporaryUrl(
            $video->video_url,
            now()->addHours(2)
        );

        return response()->json([
            'url' => $streamUrl,
            'hls_url' => $video->hls_url,
        ]);
    }
}
```

---

## Step 2: Nuxt Video Components

### `components/shop/VideoCard.vue`

```vue
<template>
  <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition group">
    <NuxtLink :to="`/shop/${video.id}`">
      <div class="relative aspect-video bg-canvas-200 overflow-hidden">
        <img
          v-if="video.thumbnail_url"
          :src="video.thumbnail_url"
          :alt="video.title"
          class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
          loading="lazy"
        />
        <div v-else class="w-full h-full flex items-center justify-center">
          <Icon name="mdi:play-circle" class="w-16 h-16 text-canvas-400" />
        </div>

        <!-- Duration Badge -->
        <div v-if="video.duration" class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded">
          {{ formatDuration(video.duration) }}
        </div>

        <!-- Free Badge -->
        <div v-if="video.is_free" class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded font-medium">
          FREE
        </div>
      </div>
    </NuxtLink>

    <div class="p-5">
      <div class="flex items-start justify-between gap-2">
        <NuxtLink :to="`/shop/${video.id}`" class="flex-1">
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition line-clamp-2">
            {{ video.title }}
          </h3>
        </NuxtLink>
        <span v-if="!video.is_free" class="text-lg font-bold text-primary-600 whitespace-nowrap">
          ${{ video.price }}
        </span>
      </div>

      <p class="mt-2 text-sm text-canvas-500 line-clamp-2">{{ video.description }}</p>

      <div class="mt-4 flex items-center justify-between">
        <NuxtLink :to="`/painter/${video.user?.slug}`" class="flex items-center gap-2">
          <img
            :src="video.user?.avatar || '/images/default-avatar.jpg'"
            :alt="video.user?.name"
            class="w-8 h-8 rounded-full object-cover"
          />
          <span class="text-sm text-canvas-600">{{ video.user?.name }}</span>
        </NuxtLink>

        <div class="flex items-center gap-1 text-xs text-canvas-400">
          <Icon name="mdi:eye" class="w-4 h-4" />
          {{ video.view_count || 0 }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  video: any
}>()

const formatDuration = (seconds: number): string => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}
</script>
```

### `components/shop/VideoPlayer.vue`

```vue
<template>
  <div class="relative bg-black rounded-2xl overflow-hidden">
    <video
      ref="videoEl"
      :src="src"
      :poster="poster"
      controls
      class="w-full aspect-video"
      @play="$emit('play')"
      @pause="$emit('pause')"
    >
      Your browser does not support the video tag.
    </video>

    <!-- Overlay for locked content -->
    <div
      v-if="locked"
      class="absolute inset-0 bg-black/80 flex flex-col items-center justify-center text-white"
    >
      <Icon name="mdi:lock" class="w-16 h-16 mb-4" />
      <p class="text-xl font-semibold mb-2">Purchase to Watch</p>
      <p class="text-canvas-300 mb-6">Buy this video to access the full content</p>
      <button
        @click="$emit('purchase')"
        class="px-8 py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition"
      >
        Buy for ${{ price }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  src?: string
  poster?: string
  locked?: boolean
  price?: number
}>()

defineEmits<{
  (e: 'play'): void
  (e: 'pause'): void
  (e: 'purchase'): void
}>()

const videoEl = ref<HTMLVideoElement>()
</script>
```

### `components/shop/PurchaseButton.vue`

```vue
<template>
  <div>
    <button
      v-if="!hasPurchased"
      @click="handlePurchase"
      :disabled="processing"
      class="w-full py-4 bg-primary-600 text-white rounded-xl font-semibold text-lg hover:bg-primary-700 transition disabled:opacity-50 flex items-center justify-center gap-2"
    >
      <Icon
        :name="processing ? 'mdi:loading' : 'mdi:cart-plus'"
        :class="[{ 'animate-spin': processing }]"
        class="w-5 h-5"
      />
      {{ processing ? 'Processing...' : `Purchase for $${price}` }}
    </button>

    <div v-else class="py-4 bg-green-50 text-green-700 rounded-xl text-center font-medium flex items-center justify-center gap-2">
      <Icon name="mdi:check-circle" class="w-5 h-5" />
      You own this video
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  itemId: number
  itemType: 'video'
  price: number
  hasPurchased: boolean
}>()

const config = useRuntimeConfig()
const processing = ref(false)
const emit = defineEmits<{ (e: 'purchased'): void }>()

const handlePurchase = async () => {
  processing.value = true
  try {
    const { clientSecret } = await $fetch<{ clientSecret: string }>(
      `${config.public.apiUrl}/orders/checkout`,
      {
        method: 'POST',
        body: {
          type: props.itemType,
          item_id: props.itemId,
        },
        withCredentials: true,
      }
    )

    // Redirect to Stripe Checkout or open modal
    // Using Stripe.js to confirm payment
    const stripe = await loadStripe(config.public.stripeKey)
    await stripe?.redirectToCheckout({ sessionId: clientSecret })

    emit('purchased')
  } catch (error) {
    console.error('Purchase failed:', error)
  } finally {
    processing.value = false
  }
}
</script>
```

---

## Step 3: Shop Pages

### `pages/shop/index.vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20 pb-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-serif font-bold text-canvas-900">Video Shop</h1>
        <p class="mt-3 text-lg text-canvas-600">
          Learn techniques from professional painters
        </p>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap items-center gap-4 mb-8">
        <button
          v-for="cat in categories"
          :key="cat.value"
          @click="selectedCategory = cat.value"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition',
            selectedCategory === cat.value
              ? 'bg-primary-600 text-white'
              : 'bg-white text-canvas-600 border border-canvas-200 hover:border-primary-400'
          ]"
        >
          {{ cat.label }}
        </button>

        <label class="flex items-center gap-2 ml-auto text-sm text-canvas-600">
          <input v-model="freeOnly" type="checkbox" class="rounded border-canvas-300" />
          Free only
        </label>
      </div>

      <!-- Video Grid -->
      <div v-if="videos.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <ShopVideoCard
          v-for="video in videos"
          :key="video.id"
          :video="video"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16">
        <Icon name="mdi:video-off" class="w-16 h-16 text-canvas-300 mx-auto" />
        <p class="mt-4 text-canvas-500">No videos found</p>
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="text-center mt-8">
        <button
          @click="loadMore"
          :disabled="loading"
          class="px-8 py-3 border border-canvas-300 rounded-lg font-medium text-canvas-700 hover:border-primary-400 transition"
        >
          {{ loading ? 'Loading...' : 'Load More' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default' })

const config = useRuntimeConfig()

const categories = [
  { label: 'All', value: '' },
  { label: 'Techniques', value: 'technique' },
  { label: 'Demos', value: 'demo' },
  { label: 'Tutorials', value: 'tutorial' },
  { label: 'Timelapses', value: 'timelapse' },
]

const selectedCategory = ref('')
const freeOnly = ref(false)
const videos = ref<any[]>([])
const page = ref(1)
const hasMore = ref(true)
const loading = ref(false)

const fetchVideos = async (reset = true) => {
  if (reset) {
    page.value = 1
    videos.value = []
  }

  loading.value = true
  try {
    const params = new URLSearchParams()
    params.append('page', page.value.toString())
    if (selectedCategory.value) params.append('category', selectedCategory.value)
    if (freeOnly.value) params.append('free', '1')

    const data = await $fetch<any>(`${config.public.apiUrl}/videos?${params}`)

    if (reset) {
      videos.value = data.data
    } else {
      videos.value.push(...data.data)
    }

    hasMore.value = data.next_page_url !== null
  } finally {
    loading.value = false
  }
}

const loadMore = () => {
  page.value++
  fetchVideos(false)
}

watch([selectedCategory, freeOnly], () => fetchVideos())

await fetchVideos()
</script>
```

### `pages/shop/[id].vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20 pb-16">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Video Player -->
      <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-8">
        <ShopVideoPlayer
          :src="streamUrl"
          :poster="video.thumbnail_url"
          :locked="!hasPurchased && !video.is_free"
          :price="video.price"
          @purchase="handlePurchase"
        />
      </div>

      <!-- Video Info -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
          <h1 class="text-3xl font-serif font-bold text-canvas-900">{{ video.title }}</h1>

          <div class="mt-4 flex items-center gap-4">
            <NuxtLink :to="`/painter/${video.user?.slug}`" class="flex items-center gap-3">
              <img
                :src="video.user?.avatar || '/images/default-avatar.jpg'"
                :alt="video.user?.name"
                class="w-12 h-12 rounded-full object-cover"
              />
              <div>
                <p class="font-medium text-canvas-900">{{ video.user?.name }}</p>
                <p class="text-sm text-canvas-500">Painter</p>
              </div>
            </NuxtLink>
          </div>

          <div class="mt-6 text-canvas-700 leading-relaxed whitespace-pre-line">
            {{ video.description }}
          </div>

          <!-- Tags -->
          <div v-if="video.tags?.length" class="mt-6 flex flex-wrap gap-2">
            <span
              v-for="tag in video.tags"
              :key="tag"
              class="px-3 py-1 bg-canvas-100 text-canvas-600 rounded-full text-sm"
            >
              {{ tag }}
            </span>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-24">
            <div class="text-center mb-6">
              <p v-if="video.is_free" class="text-2xl font-bold text-green-600">Free</p>
              <p v-else class="text-3xl font-bold text-canvas-900">${{ video.price }}</p>
            </div>

            <ShopPurchaseButton
              :item-id="video.id"
              item-type="video"
              :price="video.price"
              :has-purchased="hasPurchased"
              @purchased="hasPurchased = true"
            />

            <div class="mt-6 space-y-3 text-sm text-canvas-600">
              <div class="flex justify-between">
                <span>Duration</span>
                <span class="font-medium text-canvas-900">{{ formatDuration(video.duration) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Category</span>
                <span class="font-medium text-canvas-900 capitalize">{{ video.category }}</span>
              </div>
              <div class="flex justify-between">
                <span>Views</span>
                <span class="font-medium text-canvas-900">{{ video.view_count }}</span>
              </div>
              <div class="flex justify-between">
                <span>Purchases</span>
                <span class="font-medium text-canvas-900">{{ video.purchase_count }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()

const videoId = computed(() => route.params.id as string)

const { data: videoData } = await useFetch<any>(
  `${config.public.apiUrl}/videos/${videoId.value}`
)

const video = computed(() => videoData.value || {})

const hasPurchased = ref(false)
const streamUrl = ref<string | null>(null)

// Check if user has purchased
const { data: purchaseData } = await useFetch<{ has_purchased: boolean }>(
  `${config.public.apiUrl}/videos/${videoId.value}/check-purchase`,
  { withCredentials: true }
)

hasPurchased.value = purchaseData.value?.has_purchased || false

// Get stream URL if purchased
if (hasPurchased.value || video.value.is_free) {
  const { data: streamData } = await useFetch<{ url: string }>(
    `${config.public.apiUrl}/videos/${videoId.value}/stream`,
    { withCredentials: true }
  )
  streamUrl.value = streamData.value?.url || null
}

const handlePurchase = async () => {
  // Redirect to checkout
  navigateTo(`/checkout?video=${videoId.value}`)
}

const formatDuration = (seconds: number): string => {
  if (!seconds) return '0:00'
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}
</script>
```

---

## Step 4: My Library Page

### `pages/dashboard/library.vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-8">My Library</h1>

      <div v-if="purchases.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="purchase in purchases"
          :key="purchase.id"
          class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition"
        >
          <NuxtLink :to="`/shop/${purchase.purchasable?.id}`">
            <div class="aspect-video bg-canvas-200 overflow-hidden">
              <img
                :src="purchase.purchasable?.thumbnail_url || '/images/default-video.jpg'"
                :alt="purchase.purchasable?.title"
                class="w-full h-full object-cover"
                loading="lazy"
              />
            </div>
            <div class="p-5">
              <h3 class="font-semibold text-canvas-900 line-clamp-2">
                {{ purchase.purchasable?.title }}
              </h3>
              <p class="mt-2 text-sm text-canvas-500">
                by {{ purchase.purchasable?.user?.name }}
              </p>
              <p class="mt-1 text-xs text-canvas-400">
                Purchased {{ new Date(purchase.created_at).toLocaleDateString() }}
              </p>
            </div>
          </NuxtLink>
        </div>
      </div>

      <div v-else class="text-center py-16">
        <Icon name="mdi:library" class="w-16 h-16 text-canvas-300 mx-auto" />
        <p class="mt-4 text-canvas-500">Your library is empty</p>
        <NuxtLink to="/shop" class="mt-4 inline-block text-primary-600 font-medium hover:text-primary-700">
          Browse the shop
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()

const { data: purchasesData } = await useFetch<any>(
  `${config.public.apiUrl}/purchases`,
  { withCredentials: true }
)

const purchases = computed(() => purchasesData.value?.data || [])
</script>
```

---

## Verification Checklist

- [ ] Video upload works
- [ ] Thumbnail generation works
- [ ] Video streaming with auth works
- [ ] Purchase flow completes
- [ ] Library shows purchased videos
- [ ] Free videos are accessible without purchase
- [ ] View count increments

---

## Next Steps

Proceed to [Skill 08: Marketplace Payments](./08-marketplace-payments.md) for Stripe integration.
