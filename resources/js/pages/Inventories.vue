<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Inventaires</h1>
    <div v-if="canEdit" class="mb-6">
      <button
        @click.prevent="toggleModal"
        type="button"
        :disabled="loading"
        class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white font-bold py-2 px-4 rounded transition"
      >
        Nouvel inventaire
      </button>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div v-if="inventories.data.length === 0" class="text-center py-4 text-gray-500">
        Aucun inventaire pour le moment
      </div>
      <div v-for="inventory in inventories.data" :key="inventory.id" class="mb-4 p-4 border rounded">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-bold">{{ inventory.reference }}</h3>
            <p class="text-sm text-gray-600">Date: {{ formatDate(inventory.inventory_date) }}</p>
            <p class="text-sm text-gray-600">Statut:
              <span :class="getStatusClass(inventory.status)">{{ inventory.status }}</span>
            </p>
          </div>
          <div>
            <router-link :to="`/inventories/${inventory.id}`" class="text-indigo-600 hover:text-indigo-900">
              Voir détails
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal d'ajout d'inventaire -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold mb-4">Créer un nouvel inventaire</h3>
        <form @submit.prevent="createInventory">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Date d'inventaire</label>
            <input
              v-model="form.inventory_date"
              type="date"
              required
              class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
            />
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea
              v-model="form.notes"
              class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
              rows="3"
            ></textarea>
          </div>

          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Articles à inventorier</label>
            <div class="max-h-64 overflow-y-auto border border-gray-300 rounded">
              <div v-for="(item, index) in form.items" :key="index" class="p-3 border-b flex gap-2">
                <div class="flex-1">
                  <select
                    v-model="item.product_id"
                    required
                    class="w-full border border-gray-300 rounded px-2 py-1 text-sm"
                  >
                    <option value="">Sélectionner un produit</option>
                    <option v-for="product in products" :key="product.id" :value="product.id">
                      {{ product.name }} (Stock: {{ product.current_stock }})
                    </option>
                  </select>
                </div>
                <div class="w-24">
                  <input
                    v-model.number="item.actual_quantity"
                    type="number"
                    min="0"
                    placeholder="Qté"
                    class="w-full border border-gray-300 rounded px-2 py-1 text-sm"
                  />
                </div>
                <div class="flex-1">
                  <input
                    v-model="item.justification"
                    type="text"
                    placeholder="Justification (optionnel)"
                    class="w-full border border-gray-300 rounded px-2 py-1 text-sm"
                  />
                </div>
                <button
                  type="button"
                  @click="removeItem(index)"
                  class="text-red-600 hover:text-red-900"
                >
                  ✕
                </button>
              </div>
            </div>
            <button
              type="button"
              @click="addItem"
              class="mt-2 text-indigo-600 hover:text-indigo-900 text-sm font-medium"
            >
              + Ajouter un article
            </button>
          </div>

          <div class="flex justify-end space-x-2">
            <button
              type="button"
              @click="closeModal"
              class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="loading || form.items.length === 0"
              class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
            >
              {{ loading ? 'Création en cours...' : 'Créer' }}
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
  name: 'Inventories',
  setup() {
    const inventories = ref({ data: [] });
    const products = ref([]);
    const showModal = ref(false);
    const loading = ref(false);
    const form = ref({
      inventory_date: new Date().toISOString().split('T')[0],
      notes: '',
      items: [
        { product_id: '', actual_quantity: 0, justification: '' }
      ]
    });

    const user = computed(() => {
      const userData = localStorage.getItem('user');
      return userData ? JSON.parse(userData) : null;
    });

    const canEdit = computed(() => {
      return user.value && ['admin', 'gestionnaire'].includes(user.value.role);
    });

    const loadInventories = async () => {
      try {
        const response = await axios.get('/inventories');
        inventories.value = response.data;
      } catch (error) {
        console.error('Erreur lors du chargement des inventaires:', error);
      }
    };

    const loadProducts = async () => {
      try {
        const response = await axios.get('/products');
        products.value = response.data.data || response.data;
      } catch (error) {
        console.error('Erreur lors du chargement des produits:', error);
      }
    };

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR');
    };

    const getStatusClass = (status) => {
      const classes = {
        'en_cours': 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs',
        'termine': 'bg-green-100 text-green-800 px-2 py-1 rounded text-xs',
        'archive': 'bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs'
      };
      return classes[status] || 'px-2 py-1 rounded text-xs';
    };

    const addItem = () => {
      form.value.items.push({
        product_id: '',
        actual_quantity: 0,
        justification: ''
      });
    };

    const removeItem = (index) => {
      if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
      }
    };

    const toggleModal = () => {
      if (!loading.value) {
        showModal.value = !showModal.value;
      }
    };

    const createInventory = async () => {
      // Vérifier que tous les articles ont un produit sélectionné
      if (form.value.items.some(item => !item.product_id)) {
        alert('Veuillez sélectionner un produit pour chaque article');
        return;
      }

      loading.value = true;
      try {
        const response = await axios.post('/inventories', {
          inventory_date: form.value.inventory_date,
          notes: form.value.notes,
          items: form.value.items.map(item => ({
            product_id: parseInt(item.product_id),
            actual_quantity: item.actual_quantity,
            justification: item.justification
          }))
        });

        console.log('Inventaire créé:', response.data);

        // Réinitialiser le formulaire
        form.value = {
          inventory_date: new Date().toISOString().split('T')[0],
          notes: '',
          items: [{ product_id: '', actual_quantity: 0, justification: '' }]
        };

        showModal.value = false;

        // Recharger la liste
        await loadInventories();

        alert('Inventaire créé avec succès !');
      } catch (error) {
        console.error('Erreur lors de la création de l\'inventaire:', error);
        alert('Erreur: ' + (error.response?.data?.message || error.message));
      } finally {
        loading.value = false;
      }
    };

    const closeModal = () => {
      showModal.value = false;
      form.value = {
        inventory_date: new Date().toISOString().split('T')[0],
        notes: '',
        items: [{ product_id: '', actual_quantity: 0, justification: '' }]
      };
    };

    onMounted(() => {
      loadInventories();
      loadProducts();
    });

    return {
      inventories,
      products,
      showModal,
      loading,
      form,
      canEdit,
      loadInventories,
      formatDate,
      getStatusClass,
      addItem,
      removeItem,
      toggleModal,
      createInventory,
      closeModal
    };
  }
}
</script>
