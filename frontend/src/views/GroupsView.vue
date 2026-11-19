<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { api } from '../api'
import { auth } from '../auth'
import { groups } from '../groups'
import { GROUP_TYPES } from '../labels'
import { toast, toastError } from '../toast'

const form = reactive({ name: '', type: 'family' })
const inviteEmail = ref('')
const details = ref(null) // GET /groups/{id} - z członkami

async function loadDetails() {
  details.value = null
  if (!groups.activeId) return
  try {
    details.value = (await api.get(`/groups/${groups.activeId}`)).data
  } catch (e) {
    toastError(e)
  }
}
watch(() => groups.activeId, loadDetails, { immediate: true })

const isOwner = computed(() =>
  details.value?.members?.some((m) => m.id === auth.user?.id && m.member_role === 'owner'))

async function createGroup() {
  try {
    const res = await api.post('/groups', form)
    form.name = ''
    toast('Grupa utworzona')
    await groups.load()
    groups.setActive(res.data.id)
  } catch (e) {
    const first = Object.values(e.errors || {})[0]?.[0]
    toast(first || e.message, true) // np. 409 "Grupa o takiej nazwie już istnieje."
  }
}

async function deleteGroup(group) {
  if (!confirm(`Usunąć grupę „${group.name}” razem z listą zakupów?`)) return
  try {
    await api.delete(`/groups/${group.id}`)
    toast('Grupa usunięta')
    await groups.load()
  } catch (e) {
    toastError(e)
  }
}

async function invite() {
  try {
    details.value = (await api.post(`/groups/${groups.activeId}/members`, { email: inviteEmail.value })).data
    inviteEmail.value = ''
    toast('Dodano członka grupy')
    await groups.load()
  } catch (e) {
    const first = Object.values(e.errors || {})[0]?.[0]
    toast(first || e.message, true)
  }
}

async function removeMember(member) {
  if (!confirm(`Usunąć ${member.name} z grupy?`)) return
  try {
    details.value = (await api.delete(`/groups/${groups.activeId}/members/${member.id}`)).data
    toast('Usunięto członka')
    await groups.load()
  } catch (e) {
    toastError(e)
  }
}
</script>

<template>
  <div class="page">
    <div class="page-head">
      <h1 class="page-title">Twoje <span class="accent">grupy</span></h1>
      <p class="page-sub">Grupa ma wspólną listę zakupów. Utwórz grupę, zaproś osoby po e-mailu i przełączaj się między grupami.</p>
    </div>

    <div class="groups-grid">
      <div>
        <div class="card create-group">
          <h4>Nowa grupa</h4>
          <div class="cg-row">
            <input v-model="form.name" type="text" maxlength="100" placeholder="Nazwa grupy (np. Rodzina Nowaków)" />
            <select v-model="form.type">
              <option v-for="(meta, key) in GROUP_TYPES" :key="key" :value="key">{{ meta.label }}</option>
            </select>
          </div>
          <button class="btn-orange" @click="createGroup"><i class="fa-solid fa-plus"></i> Utwórz grupę</button>
        </div>

        <div class="list-head" style="margin-top:24px;">
          <h3>Moje grupy</h3>
          <span class="pill">{{ groups.list.length }}</span>
        </div>

        <div v-if="groups.list.length === 0" class="empty-meal">
          <i class="fa-solid fa-users"></i> Nie należysz jeszcze do żadnej grupy. Utwórz ją powyżej.
        </div>

        <div v-for="g in groups.list" :key="g.id" class="group-card" :class="{ active: g.id === groups.activeId }">
          <div class="gc-icon"><i class="fa-solid" :class="GROUP_TYPES[g.type]?.icon || 'fa-users'"></i></div>
          <div class="gc-info">
            <div class="gc-name">
              {{ g.name }}
              <span v-if="g.id === groups.activeId" class="pill">aktywna</span>
            </div>
            <div class="gc-meta">
              {{ GROUP_TYPES[g.type]?.label || g.type }} · członkowie: {{ g.members_count }} ·
              {{ g.my_role === 'owner' ? 'jesteś właścicielem' : 'jesteś członkiem' }}
            </div>
          </div>
          <div class="gc-actions">
            <button v-if="g.id !== groups.activeId" class="btn-ghost" @click="groups.setActive(g.id)">
              <i class="fa-solid fa-right-left"></i> Przełącz
            </button>
            <button v-if="g.my_role === 'owner'" class="icon-btn danger" title="Usuń grupę" @click="deleteGroup(g)">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="card members-card">
        <h4>Członkowie{{ details ? ' · ' + details.name : '' }}</h4>

        <div v-if="!details" class="sel-empty">Brak aktywnej grupy.</div>
        <template v-else>
          <div v-for="m in details.members" :key="m.id" class="member-row">
            <div class="avatar">{{ m.name.charAt(0).toUpperCase() }}</div>
            <div class="mr-info">
              <div class="mr-name">
                {{ m.name }}
                <span v-if="m.id === auth.user?.id" class="pill">Ty</span>
              </div>
              <div class="mr-email">{{ m.email }}</div>
            </div>
            <span class="badge" :class="m.member_role === 'owner' ? 'admin' : 'user'">
              {{ m.member_role === 'owner' ? 'Właściciel' : 'Członek' }}
            </span>
            <button v-if="isOwner && m.member_role !== 'owner'" class="icon-btn danger" title="Usuń z grupy" @click="removeMember(m)">
              <i class="fa-solid fa-user-minus"></i>
            </button>
          </div>

          <div v-if="isOwner" class="invite-box">
            <div class="section-label">Zaproś po e-mailu</div>
            <div class="cg-row">
              <input v-model="inviteEmail" type="email" placeholder="np. mike@mealcore.com" />
              <button class="btn-orange" @click="invite"><i class="fa-solid fa-user-plus"></i> Dodaj</button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
