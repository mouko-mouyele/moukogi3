<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Tableau de bord</h1>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-gray-500 text-sm font-medium">Total Produits</h3>
        <p class="text-3xl font-bold text-gray-900">{{ statistics.total_products }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-gray-500 text-sm font-medium">Valeur du Stock</h3>
        <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(statistics.total_value) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-gray-500 text-sm font-medium">Stock Faible</h3>
        <p class="text-3xl font-bold text-red-600">{{ statistics.low_stock_products }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-gray-500 text-sm font-medium">Alertes Actives</h3>
        <p class="text-3xl font-bold text-yellow-600">{{ statistics.active_alerts }}</p>
      </div>
    </div>

    <!-- Produits proches de la rupture -->
    <div class="bg-white rounded-lg shadow mb-8">
      <div class="p-6">
        <h2 class="text-xl font-bold mb-4">Produits proches de la rupture</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Actuel</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Minimum</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="product in productsNearRupture" :key="product.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <router-link :to="`/products/${product.id}`" class="text-indigo-600 hover:text-indigo-900">
                    {{ product.name }}
                  </router-link>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ product.current_stock }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ product.stock_minimum }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ product.category?.name || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Alertes récentes -->
    <div class="bg-white rounded-lg shadow mb-8">
      <div class="p-6">
        <h2 class="text-xl font-bold mb-4">Alertes récentes</h2>
        <div class="space-y-4">
          <div v-for="alert in alerts" :key="alert.id" class="border-l-4 border-yellow-400 bg-yellow-50 p-4">
            <div class="flex">
              <div class="flex-1">
                <h3 class="text-sm font-medium text-yellow-800">{{ alert.title }}</h3>
                <p class="text-sm text-yellow-700">{{ alert.message }}</p>
                <p class="text-xs text-yellow-600 mt-1">{{ alert.product?.name }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mouvements récents -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6">
        <h2 class="text-xl font-bold mb-4">Mouvements récents</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="movement in recentMovements" :key="movement.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(movement.movement_date) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ movement.product?.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="movement.type === 'entree' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 text-xs font-semibold rounded-full">
                    {{ movement.type === 'entree' ? 'Entrée' : 'Sortie' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ movement.quantity }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ movement.user?.name }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';

export default {
  name: 'Dashboard',
  setup() {
    const statistics = ref({
      total_products: 0,
      total_value: 0,
      low_stock_products: 0,
      active_alerts: 0,
      rotation_rate: 0
    });
    const productsNearRupture = ref([]);
    const recentMovements = ref([]);
    const alerts = ref([]);

    const loadDashboard = async () => {
      try {
        const response = await axios.get('/dashboard');
        statistics.value = response.data.statistics;
        productsNearRupture.value = response.data.products_near_rupture;
        recentMovements.value = response.data.recent_movements;
        alerts.value = response.data.alerts;
      } catch (error) {
        console.error('Erreur lors du chargement du tableau de bord:', error);
      }
    };

    const formatCurrency = (value) => {
      return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
    };

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR');
    };

    onMounted(() => {
      loadDashboard();
    });

    return {
      statistics,
      productsNearRupture,
      recentMovements,
      alerts,
      formatCurrency,
      formatDate
    };
  }
}
</script>
