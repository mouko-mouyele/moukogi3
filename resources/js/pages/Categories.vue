<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Catégories</h1>
      <button
        v-if="canEdit"
        @click="showModal = true"
        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
      >
        Ajouter une catégorie
      </button>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div v-for="category in categories" :key="category.id" class="mb-4 p-4 border rounded">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-bold">{{ category.name }}</h3>
            <p class="text-sm text-gray-600">{{ category.description || '-' }}</p>
            <p v-if="category.parent" class="text-xs text-gray-500">Parent: {{ category.parent.name }}</p>
          </div>
          <div v-if="canEdit">
            <button @click="editCategory(category)" class="text-indigo-600 hover:text-indigo-900 mr-3">Modifier</button>
            <button @click="deleteCategory(category)" class="text-red-600 hover:text-red-900">Supprimer</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold mb-4">{{ editingCategory ? 'Modifier' : 'Ajouter' }} une catégorie</h3>
        <form @submit.prevent="saveCategory">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Nom</label>
            <input v-model="form.name" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea v-model="form.description" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catégorie parente</label>
            <select v-model="form.parent_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
              <option value="">Aucune</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id" v-if="!editingCategory || cat.id !== editingCategory.id">{{ cat.name }}</option>
            </select>
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
  name: 'Categories',
  setup() {
    const categories = ref([]);
    const showModal = ref(false);
    const editingCategory = ref(null);
    const form = ref({
      name: '',
      description: '',
      parent_id: ''
    });

    const user = computed(() => {
      const userData = localStorage.getItem('user');
      return userData ? JSON.parse(userData) : null;
    });

    const canEdit = computed(() => {
      return user.value && ['admin', 'gestionnaire'].includes(user.value.role);
    });

    const loadCategories = async () => {
      try {
        const response = await axios.get('/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Erreur lors du chargement des catégories:', error);
      }
    };

    const saveCategory = async () => {
      try {
        if (editingCategory.value) {
          await axios.put(`/categories/${editingCategory.value.id}`, form.value);
        } else {
          await axios.post('/categories', form.value);
        }
        closeModal();
        loadCategories();
      } catch (error) {
        console.error('Erreur lors de l\'enregistrement:', error);
        alert('Erreur lors de l\'enregistrement');
      }
    };

    const editCategory = (category) => {
      editingCategory.value = category;
      form.value = { ...category };
      showModal.value = true;
    };

    const deleteCategory = async (category) => {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer ${category.name}?`)) return;
      
      try {
        await axios.delete(`/categories/${category.id}`);
        loadCategories();
      } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        alert('Erreur lors de la suppression');
      }
    };

    const closeModal = () => {
      showModal.value = false;
      editingCategory.value = null;
      form.value = {
        name: '',
        description: '',
        parent_id: ''
      };
    };

    onMounted(() => {
      loadCategories();
    });

    return {
      categories,
      showModal,
      editingCategory,
      form,
      canEdit,
      loadCategories,
      saveCategory,
      editCategory,
      deleteCategory,
      closeModal
    };
  }
}
</script>
