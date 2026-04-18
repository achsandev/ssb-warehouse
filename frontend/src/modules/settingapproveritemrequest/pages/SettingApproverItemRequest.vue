<script lang="ts" setup>
import { ref, onMounted, computed } from 'vue'
import { VAlert } from 'vuetify/components'
import { useSettingApproverItemRequestStore } from '@/stores/setting_approver_item_request'
import { storeToRefs } from 'pinia'
import { useTranslate } from '@/composables/useTranslate'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'

const t = useTranslate()
const store = useSettingApproverItemRequestStore()
const { items, roles, loading, selected } = storeToRefs(store)

const dialog = ref(false)
const isEdit = ref(false)
type RoleOption = { id: any; name: any } | null
const form = ref<{ requester_role: RoleOption; approver_role: RoleOption }>({ requester_role: null, approver_role: null })
const search = ref('')

onMounted(() => {
  store.fetchAll()
  store.fetchRoles()
})

const filteredItems = computed(() => {
  const arr = Array.isArray(items.value) ? items.value : [];
  if (!search.value) return arr;
  return arr.filter(item =>
    (item.requester_role_name || '').toLowerCase().includes(search.value.toLowerCase()) ||
    (item.approver_role_name || '').toLowerCase().includes(search.value.toLowerCase())
  )
})

const formTitle = computed(() => 
    isEdit.value === false
        ? t('createDataDialogTitle')
        : t('updateDataDialogTitle')
)

const openCreate = () => {
  isEdit.value = false
  form.value = { requester_role: null, approver_role: null }
  dialog.value = true
}

const openEdit = (item: any) => {
  isEdit.value = true
  form.value = {
    requester_role: { id: item.requester_role_id, name: item.requester_role_name },
    approver_role: { id: item.approver_role_id, name: item.approver_role_name }
  }
  // Jika ada field lain di form, isi juga default value-nya dari item
  // Contoh: jika ada uid, tambahkan: uid: item.uid
  store.select(item)
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  form.value = { requester_role: null, approver_role: null }
  store.clearSelected()
}

const errorMsg = ref('')
const save = async () => {
  errorMsg.value = ''
  const req = form.value.requester_role;
  const app = form.value.approver_role;
  if (
    !req || !app ||
    req.id === undefined || req.id === null || req.name === undefined || req.name === null || req.name === '' ||
    app.id === undefined || app.id === null || app.name === undefined || app.name === null || app.name === ''
  ) {
    errorMsg.value = t('fieldRequired')
    return
  }
  const payload = {
    requester_role_id: form.value.requester_role?.id,
    requester_role_name: form.value.requester_role?.name,
    approver_role_id: form.value.approver_role?.id,
    approver_role_name: form.value.approver_role?.name
  }
  try {
    if (isEdit.value && selected.value?.uid) {
      await store.update(selected.value.uid, payload)
    } else {
      await store.create(payload)
    }
    closeDialog()
  } catch (e: any) {
    if (e?.response?.data?.message) {
      errorMsg.value = e.response.data.message
    } else {
      errorMsg.value = 'Failed to save data.'
    }
  }
}

const remove = async (item: any) => {
  await store.remove(item.uid)
}

const getActionMenus = () => ['update', 'delete']
const components = { VAlert }
</script>

<template>
  <component :is="components.VAlert" v-if="false" style="display:none" />
  <v-card>
    <v-card-title class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center">
      <base-create-button :loading="loading" :disabled="loading" @click="openCreate" />
      <div class="d-flex align-center">
        <base-search-input :loading="loading" v-model="search" />
        <base-refresh-button :loading="loading" :disabled="loading" @click="store.fetchAll()" />
      </div>
    </v-card-title>
    <v-card-text>
      <v-data-table :items="filteredItems || []" :loading="loading" :headers="[
        { title: t('actions'), value: 'actions', sortable: false, width: 20 },
        { title: t('requesterRoleName'), value: 'requester_role_name' },
        { title: t('approverRoleName'), value: 'approver_role_name' }
      ]">
        <template #item.actions="{ item }">
          <actions-menu :menus="getActionMenus()" :data="item" @click="action => action === 'update' ? openEdit(item) : remove(item)" />
        </template>
      </v-data-table>
    </v-card-text>
    </v-card>
    <v-dialog v-model="dialog" max-width="500px">
    <v-card>
      <v-card-title>{{ formTitle }}</v-card-title>
      <v-card-text>
        <v-alert v-if="errorMsg" type="error" class="mb-4">{{ errorMsg }}</v-alert>
        <v-select
          v-model="form.requester_role"
          variant="outlined"
          :items="Array.isArray(roles) ? roles : []"
          item-title="name"
          item-value="id"
          :label="t('requesterRole')"
          return-object
          required
        />
        <v-select
          v-model="form.approver_role"
          variant="outlined"
          :items="Array.isArray(roles) ? roles : []"
          item-title="name"
          item-value="id"
          :label="t('approverRole')"
          return-object
          required
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="closeDialog">Cancel</v-btn>
        <v-btn color="primary" @click="save">Save</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped></style>
