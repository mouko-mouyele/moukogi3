<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Produits</h1>
      <button
        v-if="canEdit"
        @click="showModal = true"
        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
      >
        Ajouter un produit
      </button>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input
          v-model="filters.search"
          type="text"
          placeholder="Rechercher..."
          class="border border-gray-300 rounded-md px-3 py-2"
        />
        <select v-model="filters.category_id" class="border border-gray-300 rounded-md px-3 py-2">
          <option value="">Toutes les catégories</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
        <button
          @click="loadProducts"
          class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
        >
          Filtrer
        </button>
      </div>
    </div>

    <!-- Liste des produits -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code-barres</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
            <th v-if="canEdit" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="product in products.data" :key="product.id">
            <td class="px-6 py-4 whitespace-nowrap">
              <router-link :to="`/products/${product.id}`" class="text-indigo-600 hover:text-indigo-900">
                {{ product.name }}
              </router-link>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ product.barcode || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStockClass(product)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ product.current_stock }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatCurrency(product.price) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ product.category?.name || '-' }}</td>
            <td v-if="canEdit" class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <button @click="editProduct(product)" class="text-indigo-600 hover:text-indigo-900 mr-3">Modifier</button>
              <button @click="deleteProduct(product)" class="text-red-600 hover:text-red-900">Supprimer</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal d'ajout/modification -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold mb-4">{{ editingProduct ? 'Modifier' : 'Ajouter' }} un produit</h3>
        <form @submit.prevent="saveProduct">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Nom</label>
            <input v-model="form.name" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea v-model="form.description" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Code-barres</label>
            <input v-model="form.barcode" type="text" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Prix</label>
            <input v-model="form.price" type="number" step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catégorie</label>
            <select v-model="form.category_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
              <option value="">Aucune</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Stock minimum</label>
            <input v-model="form.stock_minimum" type="number" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Stock optimal</label>
            <input v-model="form.stock_optimal" type="number" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" />
          </div>
          <div class="flex justify-end space-x-2">
            <button type="button" @click="closeModal" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
              Annuler
            </button>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
              Enregistrer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

export default {
  name: 'Products',
  setup() {
    const products = ref({ data: [] });
    const categories = ref([]);
    const showModal = ref(false);
    const editingProduct = ref(null);
    const filters = ref({
      search: '',
      category_id: ''
    });
    const form = ref({
      name: '',
      description: '',
      barcode: '',
      price: 0,
      category_id: '',
      stock_minimum: 0,
      stock_optimal: 0
    });

    const user = computed(() => {
      const userData = localStorage.getItem('user');
      return userData ? JSON.parse(userData) : null;
    });

    const canEdit = computed(() => {
      return user.value && ['admin', 'gestionnaire'].includes(user.value.role);
    });

    const loadProducts = async () => {
      try {
        const params = {};
        if (filters.value.search) params.search = filters.value.search;
        if (filters.value.category_id) params.category_id = filters.value.category_id;

        const response = await axios.get('/products', { params });
        products.value = response.data;
      } catch (error) {
        console.error('Erreur lors du chargement des produits:', error);
      }
    };

    const loadCategories = async () => {
      try {
        const response = await axios.get('/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Erreur lors du chargement des catégories:', error);
      }
    };

    const saveProduct = async () => {
      try {
        if (editingProduct.value) {
          await axios.put(`/products/${editingProduct.value.id}`, form.value);
        } else {
          await axios.post('/products', form.value);
        }
        closeModal();
        loadProducts();
      } catch (error) {
        console.error('Erreur lors de l\'enregistrement:', error);
        alert('Erreur lors de l\'enregistrement');
      }
    };

    const editProduct = (product) => {
      editingProduct.value = product;
      form.value = { ...product };
      showModal.value = true;
    };

    const deleteProduct = async (product) => {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer ${product.name}?`)) return;

      try {
        await axios.delete(`/products/${product.id}`);
        loadProducts();
      } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        alert('Erreur lors de la suppression');
      }
    };

    const closeModal = () => {
      showModal.value = false;
      editingProduct.value = null;
      form.value = {
        name: '',
        description: '',
        barcode: '',
        price: 0,
        category_id: '',
        stock_minimum: 0,
        stock_optimal: 0
      };
    };

    const getStockClass = (product) => {
      if (product.current_stock <= product.stock_minimum) return 'bg-red-100 text-red-800';
      if (product.current_stock <= product.stock_optimal) return 'bg-yellow-100 text-yellow-800';
      return 'bg-green-100 text-green-800';
    };

    const formatCurrency = (value) => {
      return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
    };

    onMounted(() => {
      loadProducts();
      loadCategories();
    });

    return {
      products,
      categories,
      showModal,
      editingProduct,
      filters,
      form,
      canEdit,
      loadProducts,
      saveProduct,
      editProduct,
      deleteProduct,
      closeModal,
      getStockClass,
      formatCurrency
    };
  }
}
</script>
