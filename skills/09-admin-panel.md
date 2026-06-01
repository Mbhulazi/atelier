# Skill 09: Admin Panel

## Overview
Build admin dashboard for user management, painting moderation, platform statistics, and dispute resolution.

---

## Step 1: Admin Controller

### `app/Http/Controllers/Api/AdminController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Painting;
use App\Models\Order;
use App\Models\Video;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_painters' => User::where('role', 'painter')->count(),
            'total_collectors' => User::where('role', 'collector')->count(),
            'total_paintings' => Painting::count(),
            'total_videos' => Video::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'completed')->sum('amount'),
            'total_commission' => Order::where('status', 'completed')->sum('commission_amount'),
            'monthly_revenue' => Order::where('status', 'completed')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'new_users_today' => User::where('created_at', '>=', today())->count(),
            'new_users_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
        ];

        return response()->json($stats);
    }

    public function users(Request $request)
    {
        $users = User::query()
            ->with('roles')
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($sq) use ($request) {
                    $sq->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 20));

        return response()->json($users);
    }

    public function updateUserRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role' => 'required|in:collector,painter,admin',
        ]);

        $user->syncRoles([$validated['role']]);
        $user->update(['role' => $validated['role']]);

        return response()->json([
            'user' => $user->load('roles'),
            'message' => 'Role updated successfully',
        ]);
    }

    public function deleteUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent admin from deleting themselves
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 422);
        }

        // Delete user's files
        if ($user->avatar) {
            \Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function paintings(Request $request)
    {
        $paintings = Painting::with('user')
            ->when($request->status, function ($q) use ($request) {
                if ($request->status === 'flagged') {
                    $q->where('is_featured', false)
                      ->where('created_at', '<', now()->subDays(7));
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 20));

        return response()->json($paintings);
    }

    public function toggleFeaturedPainting($id)
    {
        $painting = Painting::findOrFail($id);
        $painting->update(['is_featured' => !$painting->is_featured]);

        return response()->json([
            'painting' => $painting,
            'message' => $painting->is_featured ? 'Painting featured' : 'Painting unfeatured',
        ]);
    }

    public function recentOrders(Request $request)
    {
        $orders = Order::with(['buyer', 'painter'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 20));

        return response()->json($orders);
    }

    public function revenueChart(Request $request)
    {
        $days = $request->get('days', 30);

        $revenue = Order::where('status', 'completed')
            ->where('paid_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as revenue, SUM(commission_amount) as commission')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($revenue);
    }
}
```

---

## Step 2: Admin Pages

### `pages/admin/index.vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-8">Admin Dashboard</h1>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
              <Icon name="mdi:account-group" class="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-canvas-900">{{ stats.total_users }}</p>
              <p class="text-sm text-canvas-500">Total Users</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
              <Icon name="mdi:palette" class="w-6 h-6 text-primary-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-canvas-900">{{ stats.total_painters }}</p>
              <p class="text-sm text-canvas-500">Painters</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
              <Icon name="mdi:currency-usd" class="w-6 h-6 text-green-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-canvas-900">${{ formatMoney(stats.total_revenue) }}</p>
              <p class="text-sm text-canvas-500">Total Revenue</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
              <Icon name="mdi:chart-line" class="w-6 h-6 text-yellow-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-canvas-900">${{ formatMoney(stats.monthly_revenue) }}</p>
              <p class="text-sm text-canvas-500">This Month</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <NuxtLink
          to="/admin/users"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition group"
        >
          <Icon name="mdi:account-multiple" class="w-8 h-8 text-primary-600 mb-3" />
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">Manage Users</h3>
          <p class="text-sm text-canvas-500 mt-1">View and manage all users</p>
        </NuxtLink>

        <NuxtLink
          to="/admin/paintings"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition group"
        >
          <Icon name="mdi:image-multiple" class="w-8 h-8 text-primary-600 mb-3" />
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">Paintings</h3>
          <p class="text-sm text-canvas-500 mt-1">Review and moderate paintings</p>
        </NuxtLink>

        <NuxtLink
          to="/admin/orders"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition group"
        >
          <Icon name="mdi:cart" class="w-8 h-8 text-primary-600 mb-3" />
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">Orders</h3>
          <p class="text-sm text-canvas-500 mt-1">View recent orders and disputes</p>
        </NuxtLink>
      </div>

      <!-- Revenue Chart -->
      <div class="bg-white rounded-2xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-canvas-900 mb-4">Revenue (Last 30 Days)</h2>
        <div class="h-64">
          <canvas ref="chartCanvas" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin' })

const config = useRuntimeConfig()

const { data: statsData } = await useFetch<any>(
  `${config.public.apiUrl}/admin/stats`,
  { withCredentials: true }
)

const stats = computed(() => statsData.value || {})

const formatMoney = (amount: number) => {
  return (amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Chart
const chartCanvas = ref<HTMLCanvasElement>()
let chart: any = null

onMounted(async () => {
  const { data: revenueData } = await useFetch<any[]>(
    `${config.public.apiUrl}/admin/revenue-chart`,
    { withCredentials: true }
  )

  if (chartCanvas.value && revenueData.value) {
    const { Chart, registerables } = await import('chart.js')
    Chart.register(...registerables)

    chart = new Chart(chartCanvas.value, {
      type: 'line',
      data: {
        labels: revenueData.value.map((d: any) => d.date),
        datasets: [
          {
            label: 'Revenue',
            data: revenueData.value.map((d: any) => d.revenue),
            borderColor: '#d68222',
            backgroundColor: 'rgba(214, 130, 34, 0.1)',
            fill: true,
            tension: 0.4,
          },
          {
            label: 'Commission',
            data: revenueData.value.map((d: any) => d.commission),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: (value: any) => '$' + value,
            },
          },
        },
      },
    })
  }
})

onUnmounted(() => {
  chart?.destroy()
})
</script>
```

### `pages/admin/users.vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-serif font-bold text-canvas-900">Manage Users</h1>

        <div class="flex gap-4">
          <input
            v-model="search"
            type="text"
            placeholder="Search users..."
            class="px-4 py-2 border border-canvas-300 rounded-lg text-sm w-64"
          />
          <select
            v-model="roleFilter"
            class="px-4 py-2 border border-canvas-300 rounded-lg text-sm"
          >
            <option value="">All Roles</option>
            <option value="painter">Painters</option>
            <option value="collector">Collectors</option>
            <option value="admin">Admins</option>
          </select>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full">
          <thead class="bg-canvas-50 border-b border-canvas-200">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-medium text-canvas-600">User</th>
              <th class="px-6 py-4 text-left text-sm font-medium text-canvas-600">Role</th>
              <th class="px-6 py-4 text-left text-sm font-medium text-canvas-600">Joined</th>
              <th class="px-6 py-4 text-right text-sm font-medium text-canvas-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-canvas-100">
            <tr v-for="user in users" :key="user.id" class="hover:bg-canvas-50">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <img
                    :src="user.avatar || '/images/default-avatar.jpg'"
                    :alt="user.name"
                    class="w-10 h-10 rounded-full object-cover"
                  />
                  <div>
                    <p class="font-medium text-canvas-900">{{ user.name }}</p>
                    <p class="text-sm text-canvas-500">{{ user.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <select
                  :value="user.roles[0]?.name"
                  @change="updateRole(user.id, ($event.target as HTMLSelectElement).value)"
                  class="px-3 py-1 border border-canvas-300 rounded text-sm"
                >
                  <option value="collector">Collector</option>
                  <option value="painter">Painter</option>
                  <option value="admin">Admin</option>
                </select>
              </td>
              <td class="px-6 py-4 text-sm text-canvas-500">
                {{ new Date(user.created_at).toLocaleDateString() }}
              </td>
              <td class="px-6 py-4 text-right">
                <button
                  @click="deleteUser(user.id)"
                  class="text-red-600 hover:text-red-700 text-sm font-medium"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="mt-6 flex justify-center">
        <button
          v-if="hasMore"
          @click="loadMore"
          class="px-6 py-2 border border-canvas-300 rounded-lg text-sm font-medium hover:border-primary-400 transition"
        >
          Load More
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin' })

const config = useRuntimeConfig()
const search = ref('')
const roleFilter = ref('')
const users = ref<any[]>([])
const page = ref(1)
const hasMore = ref(true)

const fetchUsers = async (reset = true) => {
  if (reset) {
    page.value = 1
    users.value = []
  }

  const params = new URLSearchParams()
  params.append('page', page.value.toString())
  if (search.value) params.append('search', search.value)
  if (roleFilter.value) params.append('role', roleFilter.value)

  const data = await $fetch<any>(
    `${config.public.apiUrl}/admin/users?${params}`,
    { withCredentials: true }
  )

  if (reset) {
    users.value = data.data
  } else {
    users.value.push(...data.data)
  }

  hasMore.value = data.next_page_url !== null
}

const loadMore = () => {
  page.value++
  fetchUsers(false)
}

const updateRole = async (userId: number, role: string) => {
  await $fetch(
    `${config.public.apiUrl}/admin/users/${userId}/role`,
    {
      method: 'PUT',
      body: { role },
      withCredentials: true,
    }
  )
  await fetchUsers()
}

const deleteUser = async (userId: number) => {
  if (!confirm('Are you sure you want to delete this user?')) return

  await $fetch(
    `${config.public.apiUrl}/admin/users/${userId}`,
    { method: 'DELETE', withCredentials: true }
  )
  await fetchUsers()
}

watch([search, roleFilter], () => fetchUsers())

await fetchUsers()
</script>
```

---

## Verification Checklist

- [ ] Admin stats display correctly
- [ ] User list loads with pagination
- [ ] User role can be changed
- [ ] User can be deleted
- [ ] Revenue chart renders
- [ ] Only admins can access

---

## Next Steps

Proceed to [Skill 10: Deployment](./10-deployment.md) for production checklist.
